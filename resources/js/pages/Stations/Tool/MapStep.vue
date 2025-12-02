<script setup lang="ts">
import SelectedStationsList from '@/components/SelectedStationsList.vue';
import StationsMap from '@/components/StationsMap.vue';
import { Button } from '@/components/ui/button';
import { computed, ref } from 'vue';

interface Station {
    id: string | null;
    name: string;
    lat: string | number;
    lon: string | number;
    provincia?: string | null;
}

interface Props {
    stations: Station[];
    selectedIds: Set<string>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'toggle-station', id: string): void;
    (e: 'reset-selection'): void;
    (e: 'go-to-data-options'): void;
    (e: 'map-ready'): void;
    (e: 'invalidate-map'): void;
}>();

const selectedCount = computed(() => props.selectedIds.size);

const selectedStations = computed(() => {
    return props.stations.filter(
        (station) => station.id && props.selectedIds.has(station.id),
    );
});

const mapComponentRef = ref<InstanceType<typeof StationsMap> | null>(null);
</script>

<template>
    <section class="min-h-screen p-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-6">
                <h2 class="mb-2 text-3xl font-bold">
                    Wetterstationen auswählen
                </h2>
                <p class="text-slate-600 dark:text-slate-400">
                    Klicken Sie auf die Marker, um Wetterstationen auszuwählen
                    oder abzuwählen.
                    <span
                        v-if="selectedCount > 0"
                        class="font-semibold text-blue-600 dark:text-blue-400"
                    >
                        {{ selectedCount }} Station{{
                            selectedCount !== 1 ? 'en' : ''
                        }}
                        ausgewählt
                    </span>
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <StationsMap
                        ref="mapComponentRef"
                        :stations="stations"
                        :selectable="true"
                        :selected-station-ids="selectedIds"
                        :show-coverage-on-hover="true"
                        class="rounded-lg border border-slate-200 shadow-lg dark:border-slate-800"
                        @station-click="(id) => emit('toggle-station', id)"
                        @map-ready="() => emit('map-ready')"
                    />
                </div>
                <div class="lg:col-span-1">
                    <SelectedStationsList
                        :stations="selectedStations"
                        @remove="(id) => emit('toggle-station', id)"
                    />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-4">
                <Button
                    variant="outline"
                    @click="emit('reset-selection')"
                    :disabled="selectedCount === 0"
                    >Auswahl zurücksetzen</Button
                >
                <Button
                    @click="emit('go-to-data-options')"
                    :disabled="selectedCount === 0"
                >
                    Weiter: Daten auswählen ({{ selectedCount }} Station{{
                        selectedCount !== 1 ? 'en' : ''
                    }})
                </Button>
            </div>
        </div>
    </section>
</template>
