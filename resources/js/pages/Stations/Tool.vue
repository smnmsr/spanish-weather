<script setup lang="ts">
import StationsMap from '@/components/StationsMap.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Station {
    id: string | null;
    name: string;
    lat: string | number;
    lon: string | number;
    provincia?: string | null;
}

interface Props {
    stations: Station[];
    selectedStations?: string[];
}

const props = withDefaults(defineProps<Props>(), {
    selectedStations: () => [],
});

const breadcrumbs = ref<BreadcrumbItemType[]>([
    { title: 'Home', href: '/' },
    { title: 'Stations Tool' },
]);

const welcomeRef = ref<HTMLElement | null>(null);
const mapSectionRef = ref<HTMLElement | null>(null);
const mapComponentRef = ref<InstanceType<typeof StationsMap> | null>(null);
const selectedIds = ref<Set<string>>(new Set(props.selectedStations));
let scrollTimeout: number | null = null;

const selectedCount = computed(() => selectedIds.value.size);

// Current step; set from URL on mount to avoid SSR "window" issues
const currentStep = ref<'welcome' | 'map'>('welcome');
const isMapInitialized = ref(false);

function updateUrlStep(step: 'welcome' | 'map') {
    const url = new URL(window.location.href);
    if (step === 'map') {
        url.searchParams.set('step', 'map');
    } else {
        url.searchParams.delete('step');
    }
    window.history.replaceState({}, '', url.toString());
    currentStep.value = step;
}

function handleScroll() {
    if (scrollTimeout) {
        clearTimeout(scrollTimeout);
    }

    scrollTimeout = window.setTimeout(() => {
        const mapSection = mapSectionRef.value;
        if (!mapSection) return;

        const rect = mapSection.getBoundingClientRect();
        // Consider the map visible if any part intersects the viewport
        const isMapVisible = rect.top < window.innerHeight && rect.bottom > 0;

        if (isMapVisible && currentStep.value === 'welcome') {
            updateUrlStep('map');
        } else if (!isMapVisible && currentStep.value === 'map') {
            updateUrlStep('welcome');
        }
    }, 100);
}

function handleMapReady() {
    isMapInitialized.value = true;
}

function goToMap() {
    updateUrlStep('map');
    mapSectionRef.value?.scrollIntoView({ behavior: 'smooth' });
    setTimeout(() => {
        mapComponentRef.value?.invalidateSize();
    }, 600);
}

function saveSelection() {
    router.post(
        '/save-selection',
        {
            stations: Array.from(selectedIds.value),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                // Show success message
            },
        },
    );
}

function resetSelection() {
    selectedIds.value.clear();
    saveSelection();
}

function toggleStation(stationId: string) {
    if (selectedIds.value.has(stationId)) {
        selectedIds.value.delete(stationId);
    } else {
        selectedIds.value.add(stationId);
    }
}

onMounted(() => {
    // Determine step from URL safely in browser
    const params = new URLSearchParams(window.location.search);
    if (params.get('step') === 'map') {
        currentStep.value = 'map';
    }

    // Add scroll listener
    window.addEventListener('scroll', handleScroll, { passive: true });
    // Invalidate on window resize
    window.addEventListener(
        'resize',
        () => {
            mapComponentRef.value?.invalidateSize();
        },
        { passive: true },
    );

    // If step is map, scroll to it
    if (currentStep.value === 'map') {
        setTimeout(() => {
            mapSectionRef.value?.scrollIntoView({ behavior: 'auto' });
            setTimeout(() => {
                mapComponentRef.value?.invalidateSize();
            }, 100);
        }, 100);
    }

    // Evaluate initial visibility without requiring a user scroll
    handleScroll();
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    if (scrollTimeout) {
        clearTimeout(scrollTimeout);
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col">
            <!-- Welcome Section -->
            <section
                ref="welcomeRef"
                class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-b from-slate-50 to-white p-8 dark:from-slate-950 dark:to-slate-900"
            >
                <div class="max-w-2xl text-center">
                    <h1 class="mb-6 text-5xl font-bold">
                        Willkommen zum Stations-Tool
                    </h1>
                    <p class="mb-8 text-lg text-slate-600 dark:text-slate-400">
                        Dieses Tool hilft Ihnen, Wetterstationen aus dem
                        AEMET-Netzwerk auszuwählen und relevante Variablen zu
                        speichern. Wählen Sie die Stationen aus, die für Ihre
                        Analyse wichtig sind.
                    </p>
                    <Button size="lg" @click="goToMap">
                        Weiter zur Karte
                    </Button>
                </div>
            </section>

            <!-- Map Section -->
            <section ref="mapSectionRef" class="min-h-screen p-8">
                <div class="mx-auto max-w-7xl">
                    <div class="mb-6">
                        <h2 class="mb-2 text-3xl font-bold">
                            Stationen auswählen
                        </h2>
                        <p class="text-slate-600 dark:text-slate-400">
                            Klicken Sie auf die Marker, um Stationen auszuwählen
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

                    <StationsMap
                        ref="mapComponentRef"
                        :stations="stations"
                        :selectable="true"
                        :selected-station-ids="selectedIds"
                        :show-coverage-on-hover="true"
                        class="rounded-lg border border-slate-200 shadow-lg dark:border-slate-800"
                        @station-click="toggleStation"
                        @map-ready="handleMapReady"
                    />

                    <div class="mt-6 flex justify-end gap-4">
                        <Button
                            variant="outline"
                            @click="resetSelection"
                            :disabled="selectedCount === 0"
                        >
                            Auswahl zurücksetzen
                        </Button>
                        <Button
                            @click="saveSelection"
                            :disabled="selectedCount === 0"
                        >
                            {{ selectedCount }} Station{{
                                selectedCount !== 1 ? 'en' : ''
                            }}
                            speichern
                        </Button>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
.custom-cluster-icon {
    background: transparent !important;
    border: none !important;
}
</style>
