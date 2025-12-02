<script setup lang="ts">
import StationsMap from '@/components/StationsMap.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import type { DataQueryType } from '@/types/data-query';
import { DATA_QUERY_OPTIONS } from '@/types/data-query';
import { router } from '@inertiajs/vue3';
import {
    VisArea,
    VisAxis,
    VisLine,
    VisStackedBar,
    VisXYContainer,
} from '@unovis/vue';
import {
    AlertCircle,
    BarChart,
    Calendar,
    Clock,
    Download,
    TrendingUp,
} from 'lucide-vue-next';
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
const dataOptionsRef = ref<HTMLElement | null>(null);
const mapComponentRef = ref<InstanceType<typeof StationsMap> | null>(null);
const selectedIds = ref<Set<string>>(new Set(props.selectedStations));
const selectedDataQuery = ref<DataQueryType | null>(null);
const queryResults = ref<any>(null);
const isLoadingResults = ref(false);
const resultsSectionRef = ref<HTMLElement | null>(null);
let scrollTimeout: number | null = null;

const selectedCount = computed(() => selectedIds.value.size);

const selectedStations = computed(() => {
    return props.stations.filter(
        (station) => station.id && selectedIds.value.has(station.id),
    );
});

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

const iconComponents: Record<string, any> = {
    clock: Clock,
    calendar: Calendar,
    'trending-up': TrendingUp,
    'alert-circle': AlertCircle,
    'bar-chart': BarChart,
};

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
        mapComponentRef.value?.invalidateSize();
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
    mapComponentRef.value?.resetView();
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
    // Invalidate on window resize
    window.addEventListener(
        'resize',
        () => {
            mapComponentRef.value?.invalidateSize();
        },
        { passive: true },
    );

    // If step is map or data-options, scroll to it
    if (currentStep.value === 'map') {
        setTimeout(() => {
            mapSectionRef.value?.scrollIntoView({ behavior: 'auto' });
            setTimeout(() => {
                mapComponentRef.value?.invalidateSize();
            }, 100);
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
                                        :key="station.id ?? ''"
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
                            @click="goToDataOptions"
                            :disabled="selectedCount === 0"
                        >
                            Weiter: Daten auswählen ({{
                                selectedCount
                            }}
                            Station{{ selectedCount !== 1 ? 'en' : '' }})
                        </Button>
                    </div>
                </div>
            </section>

            <!-- Data Options Section -->
            <section
                v-if="selectedCount > 0"
                ref="dataOptionsRef"
                class="min-h-screen bg-slate-50 p-8 dark:bg-slate-950"
            >
                <div class="mx-auto max-w-7xl">
                    <div class="mb-8">
                        <h2 class="mb-2 text-3xl font-bold">
                            Welche Daten möchten Sie abfragen?
                        </h2>
                        <p class="text-slate-600 dark:text-slate-400">
                            Wählen Sie die Art der Daten aus, die Sie für Ihre
                            {{ selectedCount }} ausgewählte{{
                                selectedCount !== 1 ? 'n' : ''
                            }}
                            Station{{ selectedCount !== 1 ? 'en' : '' }}
                            abfragen möchten.
                        </p>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
                    >
                        <Card
                            v-for="option in DATA_QUERY_OPTIONS"
                            :key="option.type"
                            :class="[
                                'cursor-pointer transition-all hover:shadow-lg',
                                selectedDataQuery === option.type
                                    ? 'ring-2 ring-blue-500 dark:ring-blue-400'
                                    : '',
                            ]"
                            @click="selectDataQuery(option.type)"
                        >
                            <CardHeader>
                                <div class="flex items-start justify-between">
                                    <component
                                        :is="iconComponents[option.icon]"
                                        class="h-8 w-8 text-blue-600 dark:text-blue-400"
                                    />
                                    <div
                                        v-if="option.quickWin"
                                        class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400"
                                    >
                                        Quick Win
                                    </div>
                                </div>
                                <CardTitle class="mt-4">
                                    {{ option.title }}
                                </CardTitle>
                                <CardDescription>
                                    {{ option.description }}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div
                                    class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400"
                                >
                                    <Clock class="h-4 w-4" />
                                    <span>{{ option.estimatedTime }}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Date Range Selection (conditionally shown) -->
                    <div
                        v-if="
                            selectedDataQuery &&
                            DATA_QUERY_OPTIONS.find(
                                (o) => o.type === selectedDataQuery,
                            )?.requiresDateRange
                        "
                        class="mt-8"
                    >
                        <Card>
                            <CardHeader>
                                <CardTitle>Zeitraum auswählen</CardTitle>
                                <CardDescription>
                                    Wählen Sie den gewünschten Zeitraum für Ihre
                                    Datenabfrage.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div
                                    class="grid grid-cols-1 gap-4 md:grid-cols-2"
                                >
                                    <div class="space-y-2">
                                        <Label for="start-date"
                                            >Startdatum</Label
                                        >
                                        <!-- TODO: Add date picker component -->
                                        <input
                                            id="start-date"
                                            type="date"
                                            class="w-full rounded-md border border-slate-200 p-2 dark:border-slate-800 dark:bg-slate-900"
                                        />
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="end-date">Enddatum</Label>
                                        <!-- TODO: Add date picker component -->
                                        <input
                                            id="end-date"
                                            type="date"
                                            class="w-full rounded-md border border-slate-200 p-2 dark:border-slate-800 dark:bg-slate-900"
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div class="mt-8 flex justify-end gap-4">
                        <Button variant="outline" @click="goToMap">
                            Zurück zur Auswahl
                        </Button>
                        <Button
                            @click="proceedWithDataQuery"
                            :disabled="!selectedDataQuery || isLoadingResults"
                        >
                            <Spinner
                                v-if="isLoadingResults"
                                class="mr-2 h-4 w-4"
                            />
                            {{
                                isLoadingResults ? 'Lädt...' : 'Daten abfragen'
                            }}
                        </Button>
                    </div>
                </div>
            </section>

            <!-- Results Section -->
            <section
                v-if="queryResults"
                ref="resultsSectionRef"
                class="min-h-screen bg-white p-8 dark:bg-slate-900"
            >
                <div class="mx-auto max-w-7xl">
                    <!-- Header -->
                    <div class="mb-8 flex items-center justify-between">
                        <div>
                            <h2 class="mb-2 text-3xl font-bold">
                                {{ queryTypeTitle }}
                            </h2>
                            <p class="text-slate-600 dark:text-slate-400">
                                Ergebnisse für
                                {{
                                    queryResults.selectedStationIds?.length || 0
                                }}
                                Station{{
                                    (queryResults.selectedStationIds?.length ||
                                        0) !== 1
                                        ? 'en'
                                        : ''
                                }}
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <Button
                                variant="outline"
                                @click="queryResults = null"
                            >
                                Ergebnisse ausblenden
                            </Button>
                            <Button>
                                <Download class="mr-2 h-4 w-4" />
                                Daten exportieren
                            </Button>
                        </div>
                    </div>

                    <!-- No data message -->
                    <div
                        v-if="
                            !queryResults.observations ||
                            queryResults.observations.length === 0
                        "
                        class="rounded-lg border border-slate-200 bg-white p-12 text-center dark:border-slate-800 dark:bg-slate-900"
                    >
                        <p class="text-lg text-slate-600 dark:text-slate-400">
                            Keine Daten verfügbar für die ausgewählten
                            Stationen.
                        </p>
                    </div>

                    <!-- Results by Station -->
                    <div class="space-y-8">
                        <!-- Alert for stations without data -->
                        <Alert v-if="stationsWithoutData.length > 0">
                            <AlertCircle class="h-4 w-4" />
                            <AlertTitle>Fehlende Daten</AlertTitle>
                            <AlertDescription>
                                <strong>{{
                                    stationsWithoutData.length
                                }}</strong>
                                von
                                <strong>{{
                                    queryResults.selectedStationIds?.length || 0
                                }}</strong>
                                Station{{
                                    (queryResults.selectedStationIds?.length ||
                                        0) !== 1
                                        ? 'en'
                                        : ''
                                }}
                                {{
                                    stationsWithoutData.length === 1
                                        ? 'hat'
                                        : 'haben'
                                }}
                                keine Daten für den ausgewählten Zeitraum:
                                <span class="font-medium">
                                    {{
                                        stationsWithoutData
                                            .map(
                                                (id: string) =>
                                                    queryResults.stations[id]
                                                        ?.name || id,
                                            )
                                            .join(', ')
                                    }}
                                </span>
                            </AlertDescription>
                        </Alert>

                        <!-- Stations WITH data -->
                        <Card
                            v-for="stationId in stationsWithData"
                            :key="stationId"
                        >
                            <CardHeader>
                                <CardTitle>
                                    {{
                                        queryResults.stations[stationId]
                                            ?.name || stationId
                                    }}
                                </CardTitle>
                                <CardDescription
                                    v-if="
                                        queryResults.stations[stationId]
                                            ?.provincia
                                    "
                                >
                                    {{
                                        queryResults.stations[stationId]
                                            .provincia
                                    }}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="h-[400px] w-full">
                                    <VisXYContainer
                                        :data="chartDataByStation[stationId]"
                                        :height="400"
                                    >
                                        <!-- Temperature Line (Red) -->
                                        <VisLine
                                            :x="(d: any) => d.time"
                                            :y="(d: any) => d.temperature"
                                            color="#ef4444"
                                            :line-width="2"
                                        />

                                        <!-- Precipitation Bars (Blue) -->
                                        <VisStackedBar
                                            :x="(d: any) => d.time"
                                            :y="(d: any) => d.precipitation"
                                            color="#3b82f6"
                                            :opacity="0.6"
                                        />

                                        <!-- Humidity Area (Yellowish) -->
                                        <VisArea
                                            :x="(d: any) => d.time"
                                            :y="(d: any) => d.humidity"
                                            color="#eab308"
                                            :opacity="0.3"
                                        />

                                        <!-- Wind Line (Magentaish) -->
                                        <VisLine
                                            :x="(d: any) => d.time"
                                            :y="(d: any) => d.wind"
                                            color="#d946ef"
                                            :line-width="2"
                                            :opacity="0.7"
                                        />

                                        <!-- X Axis -->
                                        <VisAxis
                                            type="x"
                                            :x="(d: any) => d.time"
                                            :tick-format="
                                                (d: number) => {
                                                    const date = new Date(d);
                                                    return date.toLocaleDateString(
                                                        'de-DE',
                                                        {
                                                            day: '2-digit',
                                                            month: '2-digit',
                                                            hour: '2-digit',
                                                        },
                                                    );
                                                }
                                            "
                                            :grid-line="false"
                                            :tick-line="false"
                                        />

                                        <!-- Y Axis -->
                                        <VisAxis
                                            type="y"
                                            :grid-line="true"
                                            :tick-line="false"
                                            :domain-line="false"
                                        />
                                    </VisXYContainer>
                                </div>

                                <!-- Legend -->
                                <div
                                    class="mt-4 flex flex-wrap justify-center gap-4 text-sm"
                                >
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="h-3 w-3 rounded-full bg-[#ef4444]"
                                        ></div>
                                        <span>Temperatur (°C)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="h-3 w-3 rounded-sm bg-[#3b82f6] opacity-60"
                                        ></div>
                                        <span>Niederschlag (mm)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="h-3 w-3 rounded-full bg-[#eab308] opacity-50"
                                        ></div>
                                        <span>Luftfeuchtigkeit (%)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="h-3 w-3 rounded-full bg-[#d946ef] opacity-70"
                                        ></div>
                                        <span>Wind (km/h)</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Stations WITHOUT data -->
                        <Card
                            v-for="stationId in stationsWithoutData"
                            :key="'no-data-' + stationId"
                            class="border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900/50"
                        >
                            <CardHeader>
                                <div class="flex items-start gap-3">
                                    <AlertCircle
                                        class="mt-1 h-5 w-5 flex-shrink-0 text-amber-600 dark:text-amber-500"
                                    />
                                    <div class="flex-1">
                                        <CardTitle
                                            class="text-slate-700 dark:text-slate-300"
                                        >
                                            {{
                                                queryResults.stations[stationId]
                                                    ?.name || stationId
                                            }}
                                        </CardTitle>
                                        <CardDescription
                                            v-if="
                                                queryResults.stations[stationId]
                                                    ?.provincia
                                            "
                                        >
                                            {{
                                                queryResults.stations[stationId]
                                                    .provincia
                                            }}
                                        </CardDescription>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <p
                                    class="text-sm text-slate-600 dark:text-slate-400"
                                >
                                    Für diese Station sind keine Daten für den
                                    ausgewählten Zeitraum verfügbar. Dies kann
                                    bedeuten, dass die Station diese Art von
                                    Messungen nicht durchführt oder die Daten
                                    derzeit nicht verfügbar sind.
                                </p>
                            </CardContent>
                        </Card>
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
