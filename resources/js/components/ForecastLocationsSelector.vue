<script setup lang="ts">
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import type { Municipality } from '@/types';
import { X } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface Props {
    modelValue: string[];
    selectedStations?: Array<{
        id: string;
        latitude: number;
        longitude: number;
        nombre: string;
    }>;
}

const props = withDefaults(defineProps<Props>(), {
    selectedStations: () => [],
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string[]): void;
}>();

const municipalities = ref<Municipality[]>([]);
const isLoading = ref(true);
const selectedProvince = ref<string>('');
const selectedMunicipalityId = ref<string>('');

// Get unique provinces and sort them
const provinces = computed(() => {
    const uniqueProvinces = new Set(
        municipalities.value.map((m) => m.provincia).filter(Boolean),
    );
    return Array.from(uniqueProvinces).sort();
});

// Get municipalities for selected province
const municipalitiesInProvince = computed(() => {
    if (!selectedProvince.value) return [];
    return municipalities.value
        .filter((m) => m.provincia === selectedProvince.value)
        .sort((a, b) => a.nombre.localeCompare(b.nombre));
});

// Calculate distance between two points (haversine formula)
function calculateDistance(
    lat1: number,
    lon1: number,
    lat2: number,
    lon2: number,
): number {
    const R = 6371; // Earth radius in km
    const dLat = ((lat2 - lat1) * Math.PI) / 180;
    const dLon = ((lon2 - lon1) * Math.PI) / 180;
    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos((lat1 * Math.PI) / 180) *
            Math.cos((lat2 * Math.PI) / 180) *
            Math.sin(dLon / 2) *
            Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

// Find closest municipalities for selected stations
function getClosestMunicipalities(): string[] {
    if (!props.selectedStations || props.selectedStations.length === 0)
        return [];

    const closest = new Set<string>();

    props.selectedStations.forEach((station) => {
        let closestMunicipality: Municipality | null = null;
        let minDistance = Infinity;

        municipalities.value.forEach((mun) => {
            if (!mun.latitud_dec || !mun.longitud_dec) return;

            const distance = calculateDistance(
                station.latitude,
                station.longitude,
                parseFloat(mun.latitud_dec),
                parseFloat(mun.longitud_dec),
            );

            if (distance < minDistance) {
                minDistance = distance;
                closestMunicipality = mun;
            }
        });

        if (closestMunicipality) {
            closest.add(closestMunicipality.id);
        }
    });

    return Array.from(closest);
}

async function fetchMunicipalities() {
    try {
        isLoading.value = true;
        const response = await fetch('/api/municipalities');
        const result = await response.json();

        if (result.success && Array.isArray(result.data)) {
            municipalities.value = result.data;

            // Auto-select closest municipalities for the first time
            if (
                props.modelValue.length === 0 &&
                props.selectedStations &&
                props.selectedStations.length > 0
            ) {
                const closest = getClosestMunicipalities();
                if (closest.length > 0) {
                    emit('update:modelValue', closest.slice(0, 5));
                }
            }
        } else {
            console.error('Failed to fetch municipalities:', result);
        }
    } catch (error) {
        console.error('Error fetching municipalities:', error);
    } finally {
        isLoading.value = false;
    }
}

function addMunicipality() {
    if (
        !selectedMunicipalityId.value ||
        props.modelValue.includes(selectedMunicipalityId.value) ||
        props.modelValue.length >= 5
    ) {
        return;
    }

    emit('update:modelValue', [
        ...props.modelValue,
        selectedMunicipalityId.value,
    ]);

    // Reset selectors
    selectedMunicipalityId.value = '';
    selectedProvince.value = '';
}

function removeMunicipality(municipalityId: string) {
    emit(
        'update:modelValue',
        props.modelValue.filter((id) => id !== municipalityId),
    );
}

// Get municipality details by ID
function getMunicipalityDetails(id: string): Municipality | undefined {
    return municipalities.value.find((m) => m.id === id);
}

onMounted(() => {
    fetchMunicipalities();
});
</script>

<template>
    <div class="space-y-4">
        <!-- Auto-selected info -->
        <div
            v-if="selectedStations.length > 0 && modelValue.length > 0"
            class="rounded-lg border border-border bg-muted/30 p-3"
        >
            <p class="text-sm text-muted-foreground">
                Automatisch nächstgelegene Gemeinden basierend auf
                {{ selectedStations.length }} ausgewählte{{
                    selectedStations.length !== 1 ? 'n' : ''
                }}
                Station{{ selectedStations.length !== 1 ? 'en' : '' }}
            </p>
        </div>

        <!-- Selected municipalities tags -->
        <div v-if="modelValue.length > 0" class="space-y-2">
            <p class="text-sm font-semibold">
                Ausgewählte Gemeinden ({{ modelValue.length }}/5)
            </p>
            <div class="flex flex-wrap gap-2">
                <div
                    v-for="muniId in modelValue"
                    :key="muniId"
                    class="flex items-center gap-2 rounded-full bg-primary px-3 py-1 text-sm text-primary-foreground"
                >
                    <span>
                        {{
                            getMunicipalityDetails(muniId)?.nombre
                                ? getMunicipalityDetails(muniId)?.nombre +
                                  (getMunicipalityDetails(muniId)?.provincia
                                      ? ` (${getMunicipalityDetails(muniId)?.provincia})`
                                      : '')
                                : muniId
                        }}
                    </span>
                    <button
                        type="button"
                        class="hover:opacity-70"
                        aria-label="Entfernen"
                        @click="removeMunicipality(muniId)"
                    >
                        <X class="h-3 w-3" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Two-step selector -->
        <div v-if="!isLoading && modelValue.length < 5" class="space-y-3">
            <p class="text-sm font-semibold">Weitere Gemeinde hinzufügen</p>

            <div>
                <label class="text-sm text-muted-foreground">
                    1. Provinz auswählen
                </label>
                <Select v-model="selectedProvince">
                    <SelectTrigger class="mt-1">
                        <SelectValue placeholder="Provinz wählen..." />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="province in provinces"
                            :key="province"
                            :value="province"
                        >
                            {{ province }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div v-if="selectedProvince">
                <label class="text-sm text-muted-foreground">
                    2. Gemeinde auswählen
                </label>
                <Select
                    v-model="selectedMunicipalityId"
                    @update:model-value="addMunicipality"
                >
                    <SelectTrigger class="mt-1">
                        <SelectValue placeholder="Gemeinde wählen..." />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="municipality in municipalitiesInProvince"
                            :key="municipality.id"
                            :value="municipality.id"
                            :disabled="modelValue.includes(municipality.id)"
                        >
                            {{ municipality.nombre }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div
            v-else-if="isLoading"
            class="flex items-center justify-center gap-2 text-sm text-muted-foreground"
        >
            <Spinner class="h-4 w-4" />
            <span>Lade Gemeinden...</span>
        </div>

        <div
            v-else-if="modelValue.length >= 5"
            class="text-sm text-muted-foreground"
        >
            Maximale Anzahl von 5 Gemeinden erreicht.
        </div>
    </div>
</template>
