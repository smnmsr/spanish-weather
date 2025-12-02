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

const selectedStations = computed(() => {
    return props.stations.filter(
        (station) => station.id && selectedIds.value.has(station.id),
    );
});

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
    mapComponentRef.value?.resetView();
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
                            Wetterstationen auswählen
                        </h2>
                        <p class="text-slate-600 dark:text-slate-400">
                            Klicken Sie auf die Marker, um Wetterstationen
                            auszuwählen oder abzuwählen.
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
                        <!-- Map Section -->
                        <div class="lg:col-span-2">
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
                        </div>

                        <!-- Selected Stations List -->
                        <div class="lg:col-span-1">
                            <div
                                class="rounded-lg border border-slate-200 bg-white p-6 shadow-lg dark:border-slate-800 dark:bg-slate-900"
                            >
                                <h3 class="mb-4 text-xl font-semibold">
                                    Ausgewählte Stationen
                                </h3>

                                <div
                                    v-if="selectedStations.length === 0"
                                    class="text-center text-slate-500 dark:text-slate-400"
                                >
                                    <p class="text-sm">
                                        Keine Stationen ausgewählt
                                    </p>
                                    <p class="mt-2 text-xs">
                                        Klicken Sie auf die Marker auf der
                                        Karte, um Stationen auszuwählen.
                                    </p>
                                </div>

                                <div
                                    v-else
                                    class="max-h-[60vh] space-y-2 overflow-y-auto"
                                >
                                    <div
                                        v-for="station in selectedStations"
                                        :key="station.id"
                                        class="group flex items-start justify-between rounded-md border border-slate-200 bg-slate-50 p-3 transition-colors hover:border-blue-300 hover:bg-blue-50 dark:border-slate-700 dark:bg-slate-800 dark:hover:border-blue-700 dark:hover:bg-slate-700"
                                    >
                                        <div class="flex-1">
                                            <p
                                                class="font-medium text-slate-900 dark:text-slate-100"
                                            >
                                                {{ station.name }}
                                            </p>
                                            <p
                                                v-if="station.provincia"
                                                class="text-sm text-slate-600 dark:text-slate-400"
                                            >
                                                {{ station.provincia }}
                                            </p>
                                        </div>
                                        <button
                                            @click="toggleStation(station.id!)"
                                            class="ml-2 rounded p-1 text-slate-400 transition-colors hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400"
                                            title="Entfernen"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-5 w-5"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

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
