<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DataOptionsStep from '@/pages/Stations/Tool/DataOptionsStep.vue';
import MapStep from '@/pages/Stations/Tool/MapStep.vue';
import ResultsSection from '@/pages/Stations/Tool/ResultsSection.vue';
import WelcomeStep from '@/pages/Stations/Tool/WelcomeStep.vue';
import type { BreadcrumbItemType } from '@/types';
import type { DataQueryType } from '@/types/data-query';
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

const mapSectionRef = ref<HTMLElement | null>(null);
const dataOptionsRef = ref<HTMLElement | null>(null);
const selectedIds = ref<Set<string>>(new Set(props.selectedStations));
const selectedDataQuery = ref<DataQueryType | null>(null);
const queryResults = ref<any>(null);
const isLoadingResults = ref(false);
const resultsSectionRef = ref<HTMLElement | null>(null);
let scrollTimeout: number | null = null;

const selectedCount = computed(() => selectedIds.value.size);

const groupedObservations = computed(() => {
    if (!queryResults.value?.observations) return {};

    const grouped: Record<string, any[]> = {};
    queryResults.value.observations.forEach((obs: any) => {
        const stationId = obs.idema;
        if (!grouped[stationId]) {
            grouped[stationId] = [];
        }
        grouped[stationId].push(obs);
    });

    // Sort each station's observations by time (newest first)
    Object.keys(grouped).forEach((stationId) => {
        grouped[stationId].sort((a, b) => {
            const timeA = a.fint || '';
            const timeB = b.fint || '';
            return timeB.localeCompare(timeA);
        });
    });

    return grouped;
});

const queryTypeTitle = computed(() => {
    if (!queryResults.value?.queryType) return '';

    switch (queryResults.value.queryType) {
        case 'current-observations':
            return 'Aktuelle Beobachtungen (24h)';
        case 'daily-values':
            return 'Tageswerte';
        case 'monthly-yearly-trends':
            return 'Monatliche/Jährliche Trends';
        case 'extreme-values':
            return 'Extremwerte';
        case 'climatological-normals':
            return 'Klimanormale (1991-2020)';
        default:
            return 'Datenabfrage';
    }
});

const stationsWithData = computed(() => {
    if (!queryResults.value?.selectedStationIds) return [];
    return queryResults.value.selectedStationIds.filter(
        (stationId: string) => groupedObservations.value[stationId]?.length > 0,
    );
});

const stationsWithoutData = computed(() => {
    if (!queryResults.value?.selectedStationIds) return [];
    return queryResults.value.selectedStationIds.filter(
        (stationId: string) =>
            !groupedObservations.value[stationId] ||
            groupedObservations.value[stationId].length === 0,
    );
});

const chartDataByStation = computed(() => {
    const result: Record<string, any[]> = {};

    Object.keys(groupedObservations.value).forEach((stationId) => {
        const observations = groupedObservations.value[stationId];

        // Sort by time (oldest first for chart)
        const sorted = [...observations].sort((a, b) => {
            const timeA = a.fint || '';
            const timeB = b.fint || '';
            return timeA.localeCompare(timeB);
        });

        // Convert to chart format
        result[stationId] = sorted.map((obs: any) => ({
            time: obs.fint ? new Date(obs.fint) : new Date(),
            temperature: obs.ta !== undefined ? Number(obs.ta) : null,
            precipitation: obs.prec !== undefined ? Number(obs.prec) : null,
            humidity: obs.hr !== undefined ? Number(obs.hr) : null,
            wind: obs.vv !== undefined ? Number(obs.vv) : null,
        }));
    });

    return result;
});

// Icons moved to DataOptionsStep

// Current step; set from URL on mount to avoid SSR "window" issues
const currentStep = ref<'welcome' | 'map' | 'data-options'>('welcome');
const isMapInitialized = ref(false);

function updateUrlStep(step: 'welcome' | 'map' | 'data-options') {
    const url = new URL(window.location.href);
    if (step === 'map') {
        url.searchParams.set('step', 'map');
    } else if (step === 'data-options') {
        url.searchParams.set('step', 'data-options');
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
        const dataOptionsSection = dataOptionsRef.value;

        if (!mapSection) return;

        // Check data options section first (bottom) - only if stations are selected
        if (dataOptionsSection && selectedCount.value > 0) {
            const dataRect = dataOptionsSection.getBoundingClientRect();
            const isDataVisible =
                dataRect.top < window.innerHeight && dataRect.bottom > 0;

            if (isDataVisible && currentStep.value !== 'data-options') {
                updateUrlStep('data-options');
                return;
            }
        }

        // Then check map section
        const mapRect = mapSection.getBoundingClientRect();
        const isMapVisible =
            mapRect.top < window.innerHeight && mapRect.bottom > 0;

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
        // invalidate handled in MapStep
    }, 600);
}

function goToDataOptions() {
    updateUrlStep('data-options');
    dataOptionsRef.value?.scrollIntoView({ behavior: 'smooth' });
}

function selectDataQuery(queryType: DataQueryType) {
    selectedDataQuery.value = queryType;
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

async function proceedWithDataQuery() {
    if (!selectedDataQuery.value) {
        return;
    }

    isLoadingResults.value = true;
    queryResults.value = null;

    try {
        const csrfToken =
            document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                ?.content || '';

        const response = await fetch('/query-data', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
            body: JSON.stringify({
                type: selectedDataQuery.value,
                stationIds: Array.from(selectedIds.value),
            }),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        queryResults.value = await response.json();

        // Scroll to results after a brief delay
        setTimeout(() => {
            resultsSectionRef.value?.scrollIntoView({ behavior: 'smooth' });
        }, 300);
    } catch (error) {
        console.error('Error fetching data:', error);
        // TODO: Show error message to user
    } finally {
        isLoadingResults.value = false;
    }
}

function resetSelection() {
    selectedIds.value.clear();
    queryResults.value = null;
    selectedDataQuery.value = null;
    saveSelection();
}

function toggleStation(stationId: string) {
    if (selectedIds.value.has(stationId)) {
        selectedIds.value.delete(stationId);
    } else {
        selectedIds.value.add(stationId);
    }

    // If we have results and user changes selection, re-query automatically
    if (queryResults.value && selectedDataQuery.value) {
        proceedWithDataQuery();
    }
}

onMounted(() => {
    // Determine step from URL safely in browser
    const params = new URLSearchParams(window.location.search);
    const stepParam = params.get('step');
    if (stepParam === 'map') {
        currentStep.value = 'map';
    } else if (stepParam === 'data-options') {
        currentStep.value = 'data-options';
    }

    // Add scroll listener
    window.addEventListener('scroll', handleScroll, { passive: true });
    // Invalidate on window resize (delegated)
    window.addEventListener(
        'resize',
        () => {
            // MapStep emits invalidate when needed
        },
        { passive: true },
    );

    // If step is map or data-options, scroll to it
    if (currentStep.value === 'map') {
        setTimeout(() => {
            mapSectionRef.value?.scrollIntoView({ behavior: 'auto' });
            // MapStep will handle invalidate
        }, 100);
    } else if (currentStep.value === 'data-options') {
        setTimeout(() => {
            dataOptionsRef.value?.scrollIntoView({ behavior: 'auto' });
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
            <WelcomeStep :breadcrumbs="breadcrumbs" @go-to-map="goToMap" />

            <section ref="mapSectionRef">
                <MapStep
                    :stations="stations"
                    :selected-ids="selectedIds"
                    @toggle-station="toggleStation"
                    @reset-selection="resetSelection"
                    @go-to-data-options="goToDataOptions"
                    @map-ready="handleMapReady"
                    @invalidate-map="() => {}"
                />
            </section>

            <section v-if="selectedCount > 0" ref="dataOptionsRef">
                <DataOptionsStep
                    :selected-count="selectedCount"
                    :selected-data-query="selectedDataQuery"
                    :is-loading-results="isLoadingResults"
                    @select-data-query="selectDataQuery"
                    @go-to-map="goToMap"
                    @proceed-with-data-query="proceedWithDataQuery"
                />
            </section>

            <section v-if="queryResults" ref="resultsSectionRef">
                <ResultsSection
                    :results="queryResults"
                    :stations-with-data="stationsWithData"
                    :stations-without-data="stationsWithoutData"
                    :chart-data-by-station="chartDataByStation"
                    :query-type-title="queryTypeTitle"
                    @clear-results="() => (queryResults = null)"
                />
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
