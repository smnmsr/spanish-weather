<script setup lang="ts">
import {
    Stepper,
    StepperIndicator,
    StepperItem,
    StepperTitle,
    StepperTrigger,
} from '@/components/ui/stepper';
import AppLayout from '@/layouts/AppLayout.vue';
import DataOptionsStep from '@/pages/Stations/Tool/DataOptionsStep.vue';
import MapStep from '@/pages/Stations/Tool/MapStep.vue';
import ResultsSection from '@/pages/Stations/Tool/ResultsSection.vue';
import WelcomeStep from '@/pages/Stations/Tool/WelcomeStep.vue';
import type { BreadcrumbItemType } from '@/types';
import type {
    DataQueryType,
    DateRangeSelection,
    MonthYearRange,
} from '@/types/data-query';
import type { Station } from '@/types/station';
import { ChartBar, Database, Home, Map } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

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
const selectedDateRange = ref<DateRangeSelection | null>(null);
const selectedMonthYearRange = ref<MonthYearRange | null>(null);
const selectedMunicipalityIds = ref<string[]>([]);
const queryResults = ref<any>(null);
const isLoadingResults = ref(false);
const resultsSectionRef = ref<HTMLElement | null>(null);
const batchId = ref<string | null>(null);
const batchProgress = ref<number>(0);
const batchStatus = ref<string>('idle');
const pollingIntervalId = ref<number | null>(null);
let scrollTimeout: number | null = null;

const selectedCount = computed(() => selectedIds.value.size);

const selectedStationsWithCoords = computed(() => {
    return Array.from(selectedIds.value)
        .map((id) => {
            const station = props.stations.find((s) => s.id === id);
            if (!station) return null;
            return {
                id: station.id ?? '',
                latitude:
                    typeof station.lat === 'number'
                        ? station.lat
                        : parseFloat(station.lat),
                longitude:
                    typeof station.lon === 'number'
                        ? station.lon
                        : parseFloat(station.lon),
                nombre: station.name,
            };
        })
        .filter((s) => s !== null);
});

const groupedObservations = computed(() => {
    if (!queryResults.value?.observations) return {};
    if (!Array.isArray(queryResults.value.observations)) {
        return {};
    }

    const isDaily = queryResults.value.queryType === 'daily-values';
    const isForecast = queryResults.value.queryType === 'forecast';
    const grouped: Record<string, any[]> = {};

    queryResults.value.observations.forEach((obs: any) => {
        const stationId = obs.idema ?? obs.indicativo;
        if (!stationId) {
            console.warn('Observation missing station ID:', obs);
            return;
        }
        if (!grouped[stationId]) {
            grouped[stationId] = [];
        }
        grouped[stationId].push(obs);
    });

    Object.keys(grouped).forEach((stationId) => {
        grouped[stationId].sort((a, b) => {
            const timeA = isDaily || isForecast ? a.fecha || '' : a.fint || '';
            const timeB = isDaily || isForecast ? b.fecha || '' : b.fint || '';
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
        case 'forecast':
            return 'Vorhersage (7 Tage)';
        default:
            return 'Datenabfrage';
    }
});

const stationsWithData = computed(() => {
    if (!queryResults.value?.selectedStationIds) return [];
    const result = queryResults.value.selectedStationIds.filter(
        (stationId: string) => groupedObservations.value[stationId]?.length > 0,
    );
    return result;
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

    const isDaily = queryResults.value?.queryType === 'daily-values';
    const isMonthlyYearly =
        queryResults.value?.queryType === 'monthly-yearly-trends';
    const isNormals =
        queryResults.value?.queryType === 'climatological-normals';
    const isForecast = queryResults.value?.queryType === 'forecast';

    Object.keys(groupedObservations.value).forEach((stationId) => {
        const observations = groupedObservations.value[stationId];

        const sorted = [...observations].sort((a, b) => {
            const timeA =
                isDaily || isNormals || isForecast
                    ? a.fecha || ''
                    : a.fint || '';
            const timeB =
                isDaily || isNormals || isForecast
                    ? b.fecha || ''
                    : b.fint || '';
            return timeA.localeCompare(timeB);
        });

        result[stationId] = sorted.map((obs: any, index: number) => {
            // Get time value based on query type
            let timeValue: string | undefined;
            if (isDaily) {
                timeValue = obs.fecha; // Daily data format: YYYY-MM-DD
            } else if (isMonthlyYearly) {
                // Monthly data format: YYYY-MM (need to add day for valid Date parsing)
                timeValue = obs.fecha ? `${obs.fecha}-01` : undefined;
            } else if (isNormals) {
                // Normals use canonical YYYY-MM-DD built in controller
                timeValue = obs.fecha;
            } else if (isForecast) {
                // Forecast data format: YYYY-MM-DDTHH:MM:SS
                timeValue = obs.fecha;
            } else {
                timeValue = obs.fint; // Hourly observation format: YYYY-MM-DDTHH:MM:SS
            }

            const date = timeValue ? new Date(timeValue) : new Date();

            // Debug: Log first observation to see available fields (only in dev mode)
            if (index === 0 && import.meta.env.DEV) {
                console.log(
                    'Observation fields for station',
                    stationId,
                    '(type:',
                    queryResults.value?.queryType,
                    '):',
                    Object.keys(obs),
                );
            }

            // Helper to parse comma-separated decimal strings (e.g., "19,5" -> 19.5)
            const parseValue = (val: any): number | null => {
                if (val === undefined || val === null || val === '')
                    return null;
                const str = String(val).replace(',', '.');
                const num = Number(str);
                return isNaN(num) ? null : num;
            };

            if (isDaily) {
                // Daily climate data has: tmed, tmax, tmin, hrMedia, hrMax, hrMin, prec, velmedia, racha, dir, presMin, presMax
                // Note: velmedia and racha are in m/s, need conversion to km/h
                const windMeanMs = parseValue(obs.velmedia);
                const windGustMs = parseValue(obs.racha);
                const presMinVal = parseValue(obs.presMin);
                const presMaxVal = parseValue(obs.presMax);

                return {
                    time: date.getTime(),
                    temperature: parseValue(obs.tmed),
                    temperatureMax: parseValue(obs.tmax),
                    temperatureMin: parseValue(obs.tmin),
                    humidity: parseValue(obs.hrMedia),
                    humidityMax: parseValue(obs.hrMax),
                    humidityMin: parseValue(obs.hrMin),
                    precipitation: parseValue(obs.prec),
                    wind: windMeanMs != null ? windMeanMs * 3.6 : null, // m/s → km/h
                    windGust: windGustMs != null ? windGustMs * 3.6 : null, // m/s → km/h
                    windDirection: parseValue(obs.dir),
                    pressure:
                        presMinVal != null && presMaxVal != null
                            ? (presMinVal + presMaxVal) / 2
                            : null, // Mean of min/max
                    pressureMin: presMinVal,
                    pressureMax: presMaxVal,
                    sunshine: null, // Not available in daily values API
                };
            } else if (isMonthlyYearly) {
                // Monthly/yearly climate data: tm_mes, tm_max, tm_min, p_mes, hr, w_med, q_med, q_min, q_max, inso, n_des, n_cub, n_llu
                // Parse pressure min/max from format "value(day)" e.g., "950.5(04)"
                const parseValueWithDay = (
                    str: string | undefined,
                ): number | null => {
                    if (!str) return null;
                    const match = str.match(/^([\d,]+)\(/);
                    return match ? parseValue(match[1]) : null;
                };

                return {
                    time: date.getTime(),
                    temperature: parseValue(obs.tm_mes),
                    temperatureMax: parseValue(obs.tm_max),
                    temperatureMin: parseValue(obs.tm_min),
                    humidity: parseValue(obs.hr),
                    humidityMax: null,
                    humidityMin: null,
                    precipitation: parseValue(obs.p_mes),
                    wind: parseValue(obs.w_med),
                    windGust: null,
                    windDirection: null, // Not available as mean direction in monthly data
                    pressure: parseValue(obs.q_med),
                    pressureMin: parseValueWithDay(obs.q_min),
                    pressureMax: parseValueWithDay(obs.q_max),
                    sunshine: parseValue(obs.inso),
                    clearDays: parseValue(obs.n_des),
                    overcastDays: parseValue(obs.n_cub),
                    rainyDays: parseValue(obs.n_llu),
                };
            } else if (isNormals) {
                // Climatological normals: use monthly means across 1991–2020
                // Prefer *_md (mean) fields if available
                return {
                    time: date.getTime(),
                    temperature: parseValue(obs.tm_mes_md ?? obs.tm_mes),
                    temperatureMax: parseValue(obs.tm_max_md ?? obs.tm_max),
                    temperatureMin: parseValue(obs.tm_min_md ?? obs.tm_min),
                    humidity: parseValue(obs.hr_md ?? obs.hr),
                    humidityMax: null,
                    humidityMin: null,
                    precipitation: parseValue(obs.p_mes_md ?? obs.p_mes),
                    wind: null,
                    sunshine: null,
                };
            } else if (isForecast) {
                // Municipal daily forecast (7 days)
                return {
                    time: date.getTime(),
                    temperature: parseValue(
                        obs.tempAvg ?? obs.tempMax ?? obs.tempMin,
                    ),
                    temperatureMax: parseValue(obs.tempMax),
                    temperatureMin: parseValue(obs.tempMin),
                    humidity: parseValue(obs.humidityAvg),
                    humidityMax: null,
                    humidityMin: null,
                    precipitation: parseValue(obs.precipitationProb),
                    wind: parseValue(obs.windSpeedAvg),
                    sunshine: null,
                };
            } else {
                // Hourly observation data: ta, hr, prec, vv
                return {
                    time: date.getTime(),
                    temperature: parseValue(obs.ta),
                    temperatureMax: null,
                    temperatureMin: null,
                    humidity: parseValue(obs.hr),
                    humidityMax: null,
                    humidityMin: null,
                    precipitation: parseValue(obs.prec),
                    wind: parseValue(obs.vv),
                    windGust: parseValue(obs.vmax),
                    windDirection: parseValue(obs.dv),
                    pressure: parseValue(obs.pres),
                    sunshine: (() => {
                        const v = parseValue(obs.inso ?? null);
                        return v == null ? null : v / 60; // convert minutes → hours
                    })(),
                };
            }
        });
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

function formatDateInput(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function computePresetRange(
    preset: 'last7' | 'last30' | 'thisMonth',
): DateRangeSelection {
    const today = new Date();
    const end = formatDateInput(today);

    if (preset === 'thisMonth') {
        const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        return {
            startDate: formatDateInput(startOfMonth),
            endDate: end,
        };
    }

    const start = new Date(today);
    const days = preset === 'last7' ? 6 : 29;
    start.setDate(today.getDate() - days);

    return {
        startDate: formatDateInput(start),
        endDate: end,
    };
}

function computeMonthYearPresetRange(
    preset: 'last5' | 'last10' | 'last20',
): MonthYearRange {
    const currentYear = new Date().getFullYear();
    const yearSpan = preset === 'last5' ? 5 : preset === 'last10' ? 10 : 20;

    return {
        month: 1, // Always use Januar (January) as default
        startYear: currentYear - yearSpan + 1,
        endYear: currentYear,
    };
}

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

    // Step 3 (data-options) stays accessible to allow forecast without station selection
    if (stepNumber === 3) return false;

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

    // Preserve analysis and date range parameters when navigating
    if (selectedDataQuery.value) {
        url.searchParams.set('analysis', String(selectedDataQuery.value));
    } else {
        url.searchParams.delete('analysis');
    }

    if (
        selectedDataQuery.value === 'daily-values' &&
        selectedDateRange.value?.startDate &&
        selectedDateRange.value?.endDate
    ) {
        url.searchParams.set('start', selectedDateRange.value.startDate);
        url.searchParams.set('end', selectedDateRange.value.endDate);
    } else {
        url.searchParams.delete('start');
        url.searchParams.delete('end');
    }

    if (
        selectedDataQuery.value === 'monthly-yearly-trends' &&
        selectedMonthYearRange.value?.month &&
        selectedMonthYearRange.value?.startYear &&
        selectedMonthYearRange.value?.endYear
    ) {
        url.searchParams.set(
            'month',
            String(selectedMonthYearRange.value.month),
        );
        url.searchParams.set(
            'startYear',
            String(selectedMonthYearRange.value.startYear),
        );
        url.searchParams.set(
            'endYear',
            String(selectedMonthYearRange.value.endYear),
        );
    } else {
        url.searchParams.delete('month');
        url.searchParams.delete('startYear');
        url.searchParams.delete('endYear');
    }

    if (
        selectedDataQuery.value === 'forecast' &&
        selectedMunicipalityIds.value.length > 0
    ) {
        url.searchParams.set(
            'municipalities',
            selectedMunicipalityIds.value.join(','),
        );
    } else {
        url.searchParams.delete('municipalities');
    }

    window.history.replaceState({}, '', url.toString());
    currentStep.value = step;
}

function updateUrlSelectionAndAnalysis() {
    const url = new URL(window.location.href);
    const stationsParam = Array.from(selectedIds.value).join(',');
    if (stationsParam.length > 0) {
        url.searchParams.set('stations', stationsParam);
    } else {
        url.searchParams.delete('stations');
    }

    if (selectedDataQuery.value) {
        url.searchParams.set('analysis', String(selectedDataQuery.value));
    } else {
        url.searchParams.delete('analysis');
    }

    if (
        selectedDataQuery.value === 'daily-values' &&
        selectedDateRange.value?.startDate &&
        selectedDateRange.value?.endDate
    ) {
        url.searchParams.set('start', selectedDateRange.value.startDate);
        url.searchParams.set('end', selectedDateRange.value.endDate);
    } else {
        url.searchParams.delete('start');
        url.searchParams.delete('end');
    }

    if (
        selectedDataQuery.value === 'monthly-yearly-trends' &&
        selectedMonthYearRange.value?.month &&
        selectedMonthYearRange.value?.startYear &&
        selectedMonthYearRange.value?.endYear
    ) {
        url.searchParams.set(
            'month',
            String(selectedMonthYearRange.value.month),
        );
        url.searchParams.set(
            'startYear',
            String(selectedMonthYearRange.value.startYear),
        );
        url.searchParams.set(
            'endYear',
            String(selectedMonthYearRange.value.endYear),
        );
    } else {
        url.searchParams.delete('month');
        url.searchParams.delete('startYear');
        url.searchParams.delete('endYear');
    }

    if (
        selectedDataQuery.value === 'forecast' &&
        selectedMunicipalityIds.value.length > 0
    ) {
        url.searchParams.set(
            'municipalities',
            selectedMunicipalityIds.value.join(','),
        );
    } else {
        url.searchParams.delete('municipalities');
    }

    window.history.replaceState({}, '', url.toString());
}

function handleScroll() {
    if (scrollTimeout) {
        clearTimeout(scrollTimeout);
    }

    scrollTimeout = window.setTimeout(() => {
        const mapSection = mapSectionRef.value;
        const dataOptionsSection = dataOptionsRef.value;

        if (!mapSection) return;

        // Check data options section first (bottom) - only if stations are selected or forecast is active
        if (
            dataOptionsSection &&
            (selectedCount.value > 0 || selectedDataQuery.value === 'forecast')
        ) {
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

function updateDateRange(range: DateRangeSelection) {
    selectedDateRange.value = range;
    updateUrlSelectionAndAnalysis();
}

function updateMonthYearRange(range: MonthYearRange) {
    selectedMonthYearRange.value = range;
    updateUrlSelectionAndAnalysis();
}

function updateMunicipalityIds(ids: string[]) {
    selectedMunicipalityIds.value = ids;
    updateUrlSelectionAndAnalysis();
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

    // Auto-populate date range for daily-values
    if (queryType === 'daily-values' && !selectedDateRange.value) {
        selectedDateRange.value = computePresetRange('last7');
    }

    // Auto-populate month/year range for monthly-yearly-trends
    if (
        queryType === 'monthly-yearly-trends' &&
        !selectedMonthYearRange.value
    ) {
        selectedMonthYearRange.value = computeMonthYearPresetRange('last5');
    }

    // Clear date range for types that don't need it
    if (queryType !== 'daily-values') {
        selectedDateRange.value = null;
    }

    // Clear month/year range for types that don't need it
    if (queryType !== 'monthly-yearly-trends') {
        selectedMonthYearRange.value = null;
    }

    if (queryType !== 'forecast') {
        selectedMunicipalityIds.value = [];
    }

    updateUrlSelectionAndAnalysis();
}

function applyDatePreset(presetKey: string) {
    if (
        presetKey === 'last7' ||
        presetKey === 'last30' ||
        presetKey === 'thisMonth'
    ) {
        selectedDateRange.value = computePresetRange(presetKey);
        updateUrlSelectionAndAnalysis();
    }
}

function toggleStation(stationId: string) {
    const MAX_STATIONS = 5;

    if (selectedIds.value.has(stationId)) {
        selectedIds.value.delete(stationId);
    } else {
        // Prevent adding more than MAX_STATIONS
        if (selectedIds.value.size >= MAX_STATIONS) {
            return;
        }
        selectedIds.value.add(stationId);
    }

    // Persist selection in URL
    updateUrlSelectionAndAnalysis();
}

async function proceedWithDataQuery() {
    if (!selectedDataQuery.value) {
        return;
    }

    const isForecastQuery = selectedDataQuery.value === 'forecast';

    if (!isForecastQuery && selectedIds.value.size === 0) {
        return;
    }

    if (isForecastQuery && selectedMunicipalityIds.value.length === 0) {
        return;
    }

    // Validate daily-values has date range
    if (
        selectedDataQuery.value === 'daily-values' &&
        (!selectedDateRange.value?.startDate ||
            !selectedDateRange.value?.endDate)
    ) {
        return;
    }

    // Validate monthly-yearly-trends has month/year range
    if (
        selectedDataQuery.value === 'monthly-yearly-trends' &&
        (!selectedMonthYearRange.value?.month ||
            !selectedMonthYearRange.value?.startYear ||
            !selectedMonthYearRange.value?.endYear)
    ) {
        return;
    }

    isLoadingResults.value = true;
    queryResults.value = null;
    batchProgress.value = 0;
    batchStatus.value = 'starting';

    try {
        const csrfToken =
            document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                ?.content || '';

        // Step 1: Start the batch query
        const startResponse = await fetch('/api/batch/start', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
            body: JSON.stringify({
                type: selectedDataQuery.value,
                stationIds: isForecastQuery
                    ? []
                    : Array.from(selectedIds.value),
                municipalityIds: isForecastQuery
                    ? selectedMunicipalityIds.value
                    : undefined,
                dateRange: selectedDateRange.value ?? undefined,
                monthYearRange: selectedMonthYearRange.value ?? undefined,
            }),
        });

        if (!startResponse.ok) {
            throw new Error(`Failed to start batch: ${startResponse.status}`);
        }

        const startData = await startResponse.json();
        batchId.value = startData.batchId;
        batchStatus.value = 'queued';

        // Navigate to results step immediately
        updateUrlStep('results');
        updateUrlSelectionAndAnalysis();

        setTimeout(() => {
            resultsSectionRef.value?.scrollIntoView({ behavior: 'smooth' });
        }, 300);

        // Step 2: Start polling for progress
        pollBatchProgress();
    } catch (error: any) {
        console.error('Error starting batch query:', error);
        batchStatus.value = 'error';
        queryResults.value = null;
        isLoadingResults.value = false;
    }
}

async function pollBatchProgress() {
    if (!batchId.value) return;

    // Clear existing polling interval if present
    if (pollingIntervalId.value) {
        clearInterval(pollingIntervalId.value);
    }

    // Poll every 500ms
    pollingIntervalId.value = window.setInterval(async () => {
        try {
            const response = await fetch(
                `/api/batch/${batchId.value}/progress`,
            );

            if (!response.ok) {
                throw new Error(`Progress fetch failed: ${response.status}`);
            }

            const data = await response.json();
            console.log(
                'pollBatchProgress: status =',
                data.status,
                'percent =',
                data.percent,
            );

            batchStatus.value = data.status;
            batchProgress.value = data.percent;

            // Check for AEMET outage
            if (data.aemetOutage) {
                console.log(
                    'pollBatchProgress: AEMET outage detected, dispatching event',
                );
                window.dispatchEvent(
                    new CustomEvent('aemet:outage', {
                        detail: {
                            message: data.error || 'AEMET API nicht erreichbar',
                        },
                    }),
                );

                // Stop polling
                if (pollingIntervalId.value) {
                    clearInterval(pollingIntervalId.value);
                    pollingIntervalId.value = null;
                }
                isLoadingResults.value = false;
                return;
            }

            // If batch is complete or failed, fetch results
            if (
                data.status === 'completed' ||
                data.status === 'failed' ||
                data.status === 'cancelled'
            ) {
                console.log(
                    'pollBatchProgress: Batch reached terminal state:',
                    data.status,
                );

                if (pollingIntervalId.value) {
                    clearInterval(pollingIntervalId.value);
                    pollingIntervalId.value = null;
                }

                if (data.status === 'completed') {
                    console.log(
                        'pollBatchProgress: Batch completed, calling fetchBatchResults',
                    );
                    await fetchBatchResults();
                } else {
                    batchStatus.value = data.status;
                    isLoadingResults.value = false;
                }
            }
        } catch (error: any) {
            console.error('Error polling batch progress:', error);
            // Continue polling despite errors
        }
    }, 500);
}

async function fetchBatchResults() {
    if (!batchId.value) return;

    console.log(
        'fetchBatchResults: Starting fetch for batchId:',
        batchId.value,
    );

    try {
        const response = await fetch(`/api/batch/${batchId.value}/results`);
        console.log('fetchBatchResults: Response status:', response.status);

        if (response.status === 202) {
            // Still processing, continue polling
            console.log(
                'fetchBatchResults: Still processing (202), retrying in 1s',
            );
            setTimeout(pollBatchProgress, 1000);
            return;
        }

        if (!response.ok) {
            throw new Error(`Results fetch failed: ${response.status}`);
        }

        const data = await response.json();
        console.log('fetchBatchResults: Received data:', data);
        console.log(
            'fetchBatchResults: Setting queryResults to:',
            data.results,
        );

        queryResults.value = data.results;
        console.log(
            'fetchBatchResults: queryResults.value is now:',
            queryResults.value,
        );

        batchStatus.value = 'completed';

        // Scroll to results
        setTimeout(() => {
            resultsSectionRef.value?.scrollIntoView({
                behavior: 'smooth',
            });
        }, 300);
    } catch (error: any) {
        console.error('Error fetching batch results:', error);
        batchStatus.value = 'error';
    } finally {
        isLoadingResults.value = false;
    }
}

function cancelBatchQuery() {
    if (!batchId.value) return;

    if (pollingIntervalId.value) {
        clearInterval(pollingIntervalId.value);
        pollingIntervalId.value = null;
    }

    fetch(`/api/batch/${batchId.value}/cancel`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN':
                document.querySelector<HTMLMetaElement>(
                    'meta[name="csrf-token"]',
                )?.content || '',
        },
    }).catch((e) => console.error('Error cancelling batch:', e));

    batchId.value = null;
    batchStatus.value = 'cancelled';
    isLoadingResults.value = false;
}

function resetSelection() {
    selectedIds.value.clear();
    queryResults.value = null;
    selectedDataQuery.value = null;
    selectedDateRange.value = null;
    selectedMonthYearRange.value = null;
    selectedMunicipalityIds.value = [];
    updateUrlSelectionAndAnalysis();
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

    // Initialize selection from URL if provided
    const stationsParam = params.get('stations');
    if (stationsParam) {
        const ids = stationsParam
            .split(',')
            .map((s) => s.trim())
            .filter((s) => s.length > 0);
        selectedIds.value = new Set(ids);
    }

    // Initialize analysis from URL if provided
    const analysisParam = params.get('analysis') as DataQueryType | null;
    if (analysisParam) {
        selectedDataQuery.value = analysisParam as DataQueryType;
    }

    const municipalitiesParam = params.get('municipalities');
    if (municipalitiesParam) {
        selectedMunicipalityIds.value = municipalitiesParam
            .split(',')
            .map((s) => s.trim())
            .filter((s) => s.length > 0);
    }

    const startParam = params.get('start');
    const endParam = params.get('end');
    if (startParam && endParam) {
        selectedDateRange.value = {
            startDate: startParam,
            endDate: endParam,
        };
    }

    const monthParam = params.get('month');
    const startYearParam = params.get('startYear');
    const endYearParam = params.get('endYear');
    if (monthParam && startYearParam && endYearParam) {
        selectedMonthYearRange.value = {
            month: parseInt(monthParam, 10),
            startYear: parseInt(startYearParam, 10),
            endYear: parseInt(endYearParam, 10),
        };
    }

    if (
        selectedDataQuery.value === 'daily-values' &&
        !selectedDateRange.value
    ) {
        selectedDateRange.value = computePresetRange('last7');
    }

    if (
        selectedDataQuery.value === 'monthly-yearly-trends' &&
        !selectedMonthYearRange.value
    ) {
        selectedMonthYearRange.value = computeMonthYearPresetRange('last5');
    }

    // Auto-trigger query if step=results and we have stations + analysis
    if (
        stepParam === 'results' &&
        selectedDataQuery.value &&
        (selectedIds.value.size > 0 || selectedMunicipalityIds.value.length > 0)
    ) {
        proceedWithDataQuery();
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
    if (pollingIntervalId.value) {
        clearInterval(pollingIntervalId.value);
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-screen flex-col">
            <!-- Slideshow Container -->
            <div class="relative flex-1 overflow-hidden">
                <transition
                    :name="isSlidingLeft ? 'slide-left' : 'slide-right'"
                    mode="out-in"
                >
                    <section :key="currentStep" class="absolute inset-0 h-full">
                        <template v-if="currentStep === 'welcome'">
                            <WelcomeStep
                                :breadcrumbs="breadcrumbs"
                                @go-to-map="goToStep(2)"
                            />
                        </template>
                        <template v-else-if="currentStep === 'map'">
                            <div ref="mapSectionRef" class="h-full">
                                <MapStep
                                    :stations="stations"
                                    :selected-ids="selectedIds"
                                    @toggle-station="toggleStation"
                                    @reset-selection="resetSelection"
                                    @go-to-data-options="goToStep(3)"
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
                                    :date-range="selectedDateRange"
                                    :month-year-range="selectedMonthYearRange"
                                    :municipality-ids="selectedMunicipalityIds"
                                    :selected-stations="
                                        selectedStationsWithCoords
                                    "
                                    @go-to-map="goToStep(2)"
                                    @select-data-query="selectDataQuery"
                                    @proceed-with-data-query="
                                        proceedWithDataQuery
                                    "
                                    @update-date-range="updateDateRange"
                                    @apply-date-preset="applyDatePreset"
                                    @update-month-year-range="
                                        updateMonthYearRange
                                    "
                                    @update-municipality-ids="
                                        updateMunicipalityIds
                                    "
                                />
                            </div>
                        </template>
                        <template v-else-if="currentStep === 'results'">
                            <div
                                ref="resultsSectionRef"
                                class="relative h-full"
                            >
                                <ResultsSection
                                    :results="queryResults"
                                    :stations-with-data="stationsWithData"
                                    :stations-without-data="stationsWithoutData"
                                    :chart-data-by-station="chartDataByStation"
                                    :query-type-title="queryTypeTitle"
                                    :is-loading="
                                        isLoadingResults &&
                                        batchStatus !== 'completed'
                                    "
                                    @go-back="goToStep(3)"
                                />

                                <!-- Full-screen backdrop -->
                                <div
                                    v-if="
                                        isLoadingResults &&
                                        batchStatus !== 'completed'
                                    "
                                    class="fixed inset-0 z-40 backdrop-blur-sm backdrop-brightness-70"
                                ></div>

                                <!-- Progress overlay while loading -->
                                <div
                                    v-if="
                                        isLoadingResults &&
                                        batchStatus !== 'completed'
                                    "
                                    class="pointer-events-none fixed inset-0 z-50 flex items-center justify-center"
                                >
                                    <div
                                        class="pointer-events-auto flex flex-col items-center gap-3 rounded-lg bg-white/95 p-6 shadow-lg dark:bg-slate-900/95"
                                    >
                                        <div
                                            class="h-10 w-10 animate-spin rounded-full border-4 border-slate-200 border-t-blue-500 dark:border-slate-700 dark:border-t-blue-400"
                                        ></div>
                                        <p
                                            class="text-sm font-medium text-slate-700 dark:text-slate-300"
                                        >
                                            Daten werden abgerufen...
                                        </p>
                                        <div class="w-48">
                                            <div
                                                class="mb-1 flex justify-between"
                                            >
                                                <span
                                                    class="text-xs text-slate-600 dark:text-slate-400"
                                                >
                                                    Fortschritt
                                                </span>
                                                <span
                                                    class="text-xs font-semibold text-slate-600 dark:text-slate-400"
                                                >
                                                    {{ batchProgress }}%
                                                </span>
                                            </div>
                                            <div
                                                class="h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700"
                                            >
                                                <div
                                                    class="h-full bg-blue-500 transition-all duration-300"
                                                    :style="{
                                                        width: `${batchProgress}%`,
                                                    }"
                                                ></div>
                                            </div>
                                        </div>
                                        <button
                                            @click="cancelBatchQuery"
                                            class="mt-2 px-4 py-2 text-sm text-slate-600 underline hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
                                        >
                                            Abbrechen
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </section>
                </transition>
            </div>

            <!-- Bottom Stepper - Compact -->
            <div class="border-t">
                <div class="mx-auto max-w-6xl px-3 py-2 sm:px-4 sm:py-3">
                    <Stepper
                        v-model="stepperModelValue"
                        :linear="false"
                        class="flex w-full items-start gap-1 sm:gap-2"
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
                                    class="h-8 w-8 bg-muted sm:h-10 sm:w-10"
                                >
                                    <template v-if="item.icon">
                                        <component
                                            :is="item.icon"
                                            class="h-3 w-3 sm:h-4 sm:w-4"
                                        />
                                    </template>
                                    <span v-else class="text-xs">{{
                                        step
                                    }}</span>
                                </StepperIndicator>
                            </StepperTrigger>
                            <StepperSeparator
                                v-if="
                                    item.step !== steps[steps.length - 1]?.step
                                "
                                class="absolute top-5 right-[calc(-50%+10px)] left-[calc(50%+20px)] block h-0.5 shrink-0 rounded-full bg-muted group-data-[state=completed]:bg-primary sm:top-6"
                            />
                            <div
                                class="mt-1 hidden flex-col items-center sm:flex"
                            >
                                <StepperTitle class="text-xs">
                                    {{ item.title }}
                                </StepperTitle>
                            </div>
                        </StepperItem>
                    </Stepper>
                </div>
            </div>
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
