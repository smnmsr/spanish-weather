<script setup lang="ts">
import {
    Stepper,
    StepperDescription,
    StepperIndicator,
    StepperItem,
    StepperSeparator,
    StepperTitle,
    StepperTrigger,
} from '@/components/ui/stepper';
import AppLayout from '@/layouts/AppLayout.vue';
import DataOptionsStep from '@/pages/Stations/Tool/DataOptionsStep.vue';
import MapStep from '@/pages/Stations/Tool/MapStep.vue';
import ResultsSection from '@/pages/Stations/Tool/ResultsSection.vue';
import WelcomeStep from '@/pages/Stations/Tool/WelcomeStep.vue';
import type { BreadcrumbItemType } from '@/types';
import type { DataQueryType } from '@/types/data-query';
import { router } from '@inertiajs/vue3';
import { ChartBar, Database, Home, Map } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

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

// Stepper steps
const steps = [
    {
        step: 1,
        title: 'Willkommen',
        description: 'Start',
        icon: Home,
    },
    {
        step: 2,
        title: 'Karte',
        description: 'Stationen wählen',
        icon: Map,
    },
    {
        step: 3,
        title: 'Datenart',
        description: 'Analyse wählen',
        icon: Database,
    },
    {
        step: 4,
        title: 'Resultate',
        description: 'Ergebnisse',
        icon: ChartBar,
    },
];

// Current step; set from URL on mount to avoid SSR "window" issues
const currentStep = ref<'welcome' | 'map' | 'data-options' | 'results'>(
    'welcome',
);
const previousStep = ref<'welcome' | 'map' | 'data-options' | 'results' | null>(
    null,
);
const stepsOrder: Array<'welcome' | 'map' | 'data-options' | 'results'> = [
    'welcome',
    'map',
    'data-options',
    'results',
];
const currentStepIndex = computed(
    () => stepsOrder.indexOf(currentStep.value) + 1,
);
const stepperModelValue = ref(currentStepIndex.value);

// Watch for changes in stepperModelValue and update the current step
watch(stepperModelValue, (newIndex) => {
    const stepName = stepsOrder[newIndex - 1];
    if (stepName && stepName !== currentStep.value) {
        goToStep(newIndex);
    }
});

// Keep stepperModelValue in sync with currentStepIndex
watch(currentStepIndex, (newIndex) => {
    stepperModelValue.value = newIndex;
});

// Computed property to check if a step should be disabled
const isStepDisabled = computed(() => (stepNumber: number) => {
    // Step 1 (welcome) and 2 (map) are always enabled
    if (stepNumber <= 2) return false;

    // Step 3 (data-options) requires at least one station selected
    if (stepNumber === 3) return selectedCount.value === 0;

    // Step 4 (results) requires query results
    if (stepNumber === 4) return !queryResults.value;

    return false;
});

const isSlidingLeft = computed(() => {
    if (!previousStep.value) return false;
    return (
        stepsOrder.indexOf(currentStep.value) >
        stepsOrder.indexOf(previousStep.value)
    );
});
const isMapInitialized = ref(false);

function updateUrlStep(step: 'welcome' | 'map' | 'data-options' | 'results') {
    previousStep.value = currentStep.value;
    const url = new URL(window.location.href);
    if (step === 'map') {
        url.searchParams.set('step', 'map');
    } else if (step === 'data-options') {
        url.searchParams.set('step', 'data-options');
    } else if (step === 'results') {
        url.searchParams.set('step', 'results');
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

function goToStep(stepIndex: number) {
    const stepName = stepsOrder[stepIndex - 1];
    if (!stepName) return;

    updateUrlStep(stepName);

    // Scroll to the corresponding section
    if (stepName === 'welcome') {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (stepName === 'map') {
        mapSectionRef.value?.scrollIntoView({ behavior: 'smooth' });
    } else if (stepName === 'data-options') {
        dataOptionsRef.value?.scrollIntoView({ behavior: 'smooth' });
    } else if (stepName === 'results') {
        resultsSectionRef.value?.scrollIntoView({ behavior: 'smooth' });
    }
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
    } catch (error: any) {
        console.error('Error fetching data:', error);
        // Notify UI about outage if 500 or network
        const status =
            error?.status ??
            (error instanceof Response ? error.status : undefined);
        if (status === 500) {
            window.dispatchEvent(
                new CustomEvent('aemet:outage', {
                    detail: {
                        status,
                        type: 'server_error',
                    },
                }),
            );
        }
        // Gracefully clear current results
        queryResults.value = null;
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
    } else if (stepParam === 'results') {
        currentStep.value = 'results';
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
        <div class="flex min-h-[calc(100vh-64px)] flex-col">
            <!-- Top Stepper -->
            <div class="sticky top-0 z-20 border-b bg-background">
                <div class="mx-auto max-w-6xl px-4 py-4">
                    <Stepper
                        v-model="stepperModelValue"
                        :linear="false"
                        class="flex w-full items-start gap-2"
                    >
                        <StepperItem
                            v-for="item in steps"
                            :key="item.step"
                            :step="item.step"
                            :disabled="isStepDisabled(item.step)"
                            class="relative flex w-full flex-col items-center justify-center"
                        >
                            <StepperTrigger>
                                <StepperIndicator
                                    v-slot="{ step }"
                                    class="bg-muted"
                                >
                                    <template v-if="item.icon">
                                        <component
                                            :is="item.icon"
                                            class="h-4 w-4"
                                        />
                                    </template>
                                    <span v-else>{{ step }}</span>
                                </StepperIndicator>
                            </StepperTrigger>
                            <StepperSeparator
                                v-if="
                                    item.step !== steps[steps.length - 1]?.step
                                "
                                class="absolute top-5 right-[calc(-50%+10px)] left-[calc(50%+20px)] block h-0.5 shrink-0 rounded-full bg-muted group-data-[state=completed]:bg-primary"
                            />
                            <div class="flex flex-col items-center">
                                <StepperTitle>
                                    {{ item.title }}
                                </StepperTitle>
                                <StepperDescription>
                                    {{ item.description }}
                                </StepperDescription>
                            </div>
                        </StepperItem>
                    </Stepper>
                </div>
            </div>

            <!-- Slideshow Container -->
            <div class="relative flex-1 overflow-hidden">
                <transition
                    :name="isSlidingLeft ? 'slide-left' : 'slide-right'"
                    mode="out-in"
                >
                    <section :key="currentStep" class="absolute inset-0 h-full">
                        <template v-if="currentStep === 'welcome'">
                            <WelcomeStep :breadcrumbs="breadcrumbs" />
                        </template>
                        <template v-else-if="currentStep === 'map'">
                            <div ref="mapSectionRef" class="h-full">
                                <MapStep
                                    :stations="stations"
                                    :selected-ids="selectedIds"
                                    @toggle-station="toggleStation"
                                    @reset-selection="resetSelection"
                                    @map-ready="handleMapReady"
                                    @invalidate-map="() => {}"
                                />
                            </div>
                        </template>
                        <template v-else-if="currentStep === 'data-options'">
                            <div ref="dataOptionsRef" class="h-full">
                                <DataOptionsStep
                                    :selected-count="selectedCount"
                                    :selected-data-query="selectedDataQuery"
                                    :is-loading-results="isLoadingResults"
                                    @select-data-query="selectDataQuery"
                                    @proceed-with-data-query="
                                        proceedWithDataQuery
                                    "
                                />
                            </div>
                        </template>
                        <template v-else-if="currentStep === 'results'">
                            <div
                                ref="resultsSectionRef"
                                class="h-full overflow-auto"
                            >
                                <ResultsSection
                                    :results="queryResults"
                                    :stations-with-data="stationsWithData"
                                    :stations-without-data="stationsWithoutData"
                                    :chart-data-by-station="chartDataByStation"
                                    :query-type-title="queryTypeTitle"
                                    @clear-results="() => (queryResults = null)"
                                />
                            </div>
                        </template>
                    </section>
                </transition>
            </div>
            <!-- No bottom navigation per request -->
        </div>
    </AppLayout>
</template>

<style scoped>
.custom-cluster-icon {
    background: transparent !important;
    border: none !important;
}

.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active {
    transition:
        transform 300ms ease,
        opacity 300ms ease;
}
.slide-left-enter-from {
    transform: translateX(100%);
    opacity: 0.3;
}
.slide-left-leave-to {
    transform: translateX(-100%);
    opacity: 0.3;
}
.slide-right-enter-from {
    transform: translateX(-100%);
    opacity: 0.3;
}
.slide-right-leave-to {
    transform: translateX(100%);
    opacity: 0.3;
}
</style>
