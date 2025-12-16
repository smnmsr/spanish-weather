<script setup lang="ts">
import ChartByDimension from '@/components/ChartByDimension.vue';
import InfoDrawer from '@/components/InfoDrawer.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    type CarouselApi,
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from '@/components/ui/carousel';
import type { ChartDataPoint, DimensionKey } from '@/types';
import type { QueryResults } from '@/types/station';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

interface Props {
    results: QueryResults;
    stationsWithData: string[];
    stationsWithoutData: string[];
    chartDataByStation: Record<string, any[]>;
    queryTypeTitle: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'go-back'): void;
}>();

// Filter data to only include stations with data
const filteredChartData = computed(() => {
    const filtered: Record<string, ChartDataPoint[]> = {};
    props.stationsWithData.forEach((stationId) => {
        if (props.chartDataByStation[stationId]) {
            filtered[stationId] = props.chartDataByStation[stationId];
        }
    });
    return filtered;
});

const isDailyQuery = computed(
    () => props.results?.queryType === 'daily-values',
);

const isMonthlyYearlyQuery = computed(
    () => props.results?.queryType === 'monthly-yearly-trends',
);

const tickFormatter = computed(() => (value: number) => {
    const date = new Date(value);
    if (isDailyQuery.value) {
        try {
            return date.toLocaleDateString('de-DE', {
                day: '2-digit',
                month: '2-digit',
            });
        } catch {
            // Fallback for environments with limited Intl support
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            return `${day}.${month}`;
        }
    }

    if (isMonthlyYearlyQuery.value) {
        // For monthly-yearly trends, show only 2-digit year (95, 96, 97, etc.)
        const year = date.getFullYear();
        return String(year).slice(-2);
    }

    try {
        return date.toLocaleDateString('de-DE', {
            day: '2-digit',
            month: '2-digit',
            hour: '2-digit',
        });
    } catch {
        // Fallback for environments with limited Intl support
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const hour = String(date.getHours()).padStart(2, '0');
        return `${day}.${month} ${hour}`;
    }
});

// Detect stations with partial data
const partialDataInfo = computed(() => {
    const dimensions = [
        'temperature',
        'precipitation',
        'humidity',
        'wind',
        'sunshine',
    ];
    const stationMissingDimensions: Record<string, string[]> = {};

    props.stationsWithData.forEach((stationId) => {
        const data = props.chartDataByStation[stationId];
        if (!data) return;

        const missingDimensions: string[] = [];
        dimensions.forEach((dimension) => {
            const hasData = data.some(
                (point: ChartDataPoint) => point[dimension] != null,
            );
            if (!hasData) {
                const labels: Record<string, string> = {
                    temperature: 'Temperatur',
                    precipitation: 'Niederschlag',
                    humidity: 'Luftfeuchtigkeit',
                    wind: 'Wind',
                    sunshine: 'Sonnenschein',
                };
                missingDimensions.push(labels[dimension]);
            }
        });

        if (missingDimensions.length > 0 && missingDimensions.length < 5) {
            stationMissingDimensions[stationId] = missingDimensions;
        }
    });

    return stationMissingDimensions;
});

const hasPartialData = computed(() => {
    return Object.keys(partialDataInfo.value).length > 0;
});

// German month names
const monthNames = [
    'Januar',
    'Februar',
    'März',
    'April',
    'Mai',
    'Juni',
    'Juli',
    'August',
    'September',
    'Oktober',
    'November',
    'Dezember',
];

const chartSlides = computed(() => {
    const baseTitles: Record<
        DimensionKey,
        { title: string; description: string }
    > = {
        temperature: {
            title: 'Temperatur',
            description:
                'Temperatur (°C) – Mittelwert (Linie), Min/Max (Bereich)',
        },
        precipitation: {
            title: 'Niederschlag',
            description: 'Niederschlag (mm) für alle Stationen',
        },
        humidity: {
            title: 'Luftfeuchtigkeit',
            description:
                'Luftfeuchtigkeit (%) – Mittelwert (Linie), Min/Max (Bereich)',
        },
        wind: {
            title: 'Wind',
            description: 'Windgeschwindigkeit (km/h) – Mittelwert',
        },
        sunshine: {
            title: 'Sonnenschein',
            description: 'Sonnenscheindauer (h) für alle Stationen',
        },
    };

    return (
        [
            'temperature',
            'precipitation',
            'humidity',
            'wind',
            'sunshine',
        ] as const
    ).map((dimension) => {
        let title = baseTitles[dimension].title;

        // Add month and year range for monthly-yearly trends
        if (isMonthlyYearlyQuery.value && props.results?.monthYearRange) {
            const { month, startYear, endYear } = props.results.monthYearRange;
            const monthName = monthNames[month - 1];
            const adjective = getAdjectiveForDimension(dimension);
            title = `${adjective} im ${monthName}, ${startYear} - ${endYear}`;
        }

        return {
            key: dimension,
            title,
            description: baseTitles[dimension].description,
        };
    });
});

const getAdjectiveForDimension = (dimension: DimensionKey): string => {
    const adjectives: Record<DimensionKey, string> = {
        temperature: 'Temperaturen',
        precipitation: 'Niederschläge',
        humidity: 'Luftfeuchtigkeiten',
        wind: 'Windgeschwindigkeiten',
        sunshine: 'Sonnenscheindauer',
    };
    return adjectives[dimension];
};

const infoDrawerOpen = ref(false);
const hasInfo = computed(
    () =>
        props.stationsWithoutData.length > 0 ||
        Object.keys(partialDataInfo.value).length > 0,
);

const viewportWidth = ref(
    typeof window !== 'undefined' ? window.innerWidth : 0,
);
const viewportHeight = ref(
    typeof window !== 'undefined' ? window.innerHeight : 0,
);

const updateViewport = () => {
    if (typeof window === 'undefined') return;
    viewportWidth.value = window.innerWidth;
    viewportHeight.value = window.innerHeight;
};

onMounted(() => {
    updateViewport();
    if (typeof window === 'undefined') return;
    window.addEventListener('resize', updateViewport);
});

onBeforeUnmount(() => {
    teardownCarouselListeners();
    clearIdleNudge();
    if (typeof window === 'undefined') return;
    window.removeEventListener('resize', updateViewport);
});

const showSideArrows = computed(() => viewportWidth.value > 640);
const showBottomButtons = computed(
    () => viewportHeight.value > 700 && !showSideArrows.value,
);
const chartPaddingClass = computed(() =>
    showSideArrows.value ? 'px-12 sm:px-16' : 'px-0',
);
const carouselHeightStyle = computed(() => {
    // Reserve room for header and bottom controls; clamp to keep stable across breakpoints.
    const reserved = showBottomButtons.value ? 260 : 250;
    const available =
        viewportHeight.value > 0 ? viewportHeight.value - reserved : 0;
    const heightPx = Math.max(260, Math.min(available, 1000));
    return {
        height: `${heightPx}px`,
        maxHeight: 'calc(100vh - 180px)',
    };
});

const carouselApi = ref<CarouselApi | null>(null);
const idleNudgeTimeoutId = ref<number | null>(null);
const hasUserInteractedWithCarousel = ref(false);
const hasCarouselNudged = ref(false);

const clearIdleNudge = () => {
    if (idleNudgeTimeoutId.value !== null && typeof window !== 'undefined') {
        window.clearTimeout(idleNudgeTimeoutId.value);
    }
    idleNudgeTimeoutId.value = null;
};

const markCarouselInteraction = () => {
    hasUserInteractedWithCarousel.value = true;
    clearIdleNudge();
};

const runCarouselNudge = () => {
    const api = carouselApi.value;
    if (
        !api ||
        hasUserInteractedWithCarousel.value ||
        hasCarouselNudged.value ||
        (!api.canScrollNext() && !api.canScrollPrev())
    ) {
        return;
    }

    hasCarouselNudged.value = true;
    clearIdleNudge();
    api.scrollNext();

    if (typeof window === 'undefined') return;

    window.setTimeout(() => {
        api.scrollPrev();
    }, 450);
};

const scheduleCarouselNudge = () => {
    if (typeof window === 'undefined') return;
    if (hasUserInteractedWithCarousel.value || hasCarouselNudged.value) return;

    clearIdleNudge();
    idleNudgeTimeoutId.value = window.setTimeout(runCarouselNudge, 5000);
};

const teardownCarouselListeners = () => {
    const api = carouselApi.value;
    if (!api) return;

    (api as any).off?.('pointerDown', markCarouselInteraction);
    (api as any).off?.('scroll', markCarouselInteraction);
    (api as any).off?.('select', markCarouselInteraction);
};
const formatMissing = (arr: string[]): string => arr.join(', ');

const handleCarouselInit = (api: CarouselApi) => {
    carouselApi.value = api;

    api.on('pointerDown', markCarouselInteraction);
    api.on('scroll', markCarouselInteraction);
    api.on('select', markCarouselInteraction);

    scheduleCarouselNudge();
};
</script>

<template>
    <section class="flex h-full flex-col px-4 py-4 sm:px-8 sm:py-6">
        <div
            class="mx-auto flex w-full max-w-7xl flex-1 flex-col overflow-hidden"
        >
            <div
                class="mb-4 flex flex-shrink-0 flex-wrap items-start justify-between gap-3 sm:mb-6"
            >
                <div class="min-w-0">
                    <h2
                        class="mb-1 overflow-hidden text-xl leading-tight font-bold text-ellipsis whitespace-nowrap sm:text-2xl lg:text-3xl"
                        :title="queryTypeTitle"
                    >
                        {{ queryTypeTitle }}
                    </h2>
                    <p
                        class="text-xs text-slate-600 sm:text-sm dark:text-slate-400"
                    >
                        Ergebnisse für
                        {{ props.results.selectedStationIds?.length || 0 }}
                        Station{{
                            (props.results.selectedStationIds?.length || 0) !==
                            1
                                ? 'en'
                                : ''
                        }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <InfoDrawer
                        v-if="hasInfo"
                        v-model:open="infoDrawerOpen"
                        title="Datenhinweise"
                        description="Details zu fehlenden oder teilweise vorhandenen Daten."
                    >
                        <div
                            v-if="stationsWithoutData.length > 0"
                            class="space-y-2"
                        >
                            <h3 class="text-base font-semibold">
                                Fehlende Daten
                            </h3>
                            <p
                                class="text-sm text-slate-600 dark:text-slate-400"
                            >
                                <strong>{{
                                    stationsWithoutData.length
                                }}</strong>
                                von
                                <strong>{{
                                    props.results.selectedStationIds?.length ||
                                    0
                                }}</strong>
                                Station{{
                                    (props.results.selectedStationIds?.length ||
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
                            </p>
                            <ul
                                class="list-inside list-disc text-sm text-slate-700 dark:text-slate-300"
                            >
                                <li v-for="id in stationsWithoutData" :key="id">
                                    {{ props.results.stations[id]?.name || id }}
                                </li>
                            </ul>
                        </div>

                        <div v-if="hasPartialData" class="space-y-2">
                            <h3 class="text-base font-semibold">
                                Teilweise Daten
                            </h3>
                            <p
                                class="text-sm text-slate-600 dark:text-slate-400"
                            >
                                {{
                                    Object.keys(partialDataInfo).length === 1
                                        ? '1 Station liefert nur teilweise Daten:'
                                        : `${Object.keys(partialDataInfo).length} Stationen liefern nur teilweise Daten:`
                                }}
                            </p>
                            <ul
                                class="list-inside list-disc space-y-1 text-sm text-slate-700 dark:text-slate-300"
                            >
                                <li
                                    v-for="(
                                        dimensions, stationId
                                    ) in partialDataInfo"
                                    :key="stationId"
                                >
                                    <span class="font-medium">
                                        {{
                                            props.results.stations[stationId]
                                                ?.name || stationId
                                        }}
                                    </span>
                                    - fehlend:
                                    {{ formatMissing(dimensions) }}
                                </li>
                            </ul>
                        </div>
                    </InfoDrawer>

                    <Button
                        variant="outline"
                        size="sm"
                        class="h-9 px-3"
                        @click="emit('go-back')"
                    >
                        Zurück
                    </Button>
                </div>
            </div>

            <div
                v-if="
                    !props.results.observations ||
                    props.results.observations.length === 0
                "
                class="rounded-lg border border-slate-200 bg-white p-8 text-center dark:border-slate-800 dark:bg-slate-900"
            >
                <p class="text-lg text-slate-600 dark:text-slate-400">
                    Keine Daten verfügbar für die ausgewählten Stationen.
                </p>
            </div>

            <div class="flex-1 overflow-visible">
                <div v-if="stationsWithData.length > 0" class="h-full w-full">
                    <Carousel
                        class="w-full"
                        :style="carouselHeightStyle"
                        :opts="{ align: 'start', loop: true }"
                        @init-api="handleCarouselInit"
                    >
                        <div class="relative h-full" :class="chartPaddingClass">
                            <CarouselContent class="h-full gap-4 sm:gap-6">
                                <CarouselItem
                                    v-for="slide in chartSlides"
                                    :key="slide.key"
                                    class="h-full basis-full"
                                >
                                    <Card class="flex h-full w-full flex-col">
                                        <CardHeader
                                            class="flex-shrink-0 p-3 pb-2 sm:p-6 sm:pb-3"
                                        >
                                            <CardTitle
                                                class="text-lg sm:text-xl"
                                            >
                                                {{ slide.title }}
                                            </CardTitle>
                                            <CardDescription
                                                class="hidden text-sm text-slate-600 sm:block dark:text-slate-400"
                                            >
                                                {{ slide.description }}
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent
                                            class="flex min-h-0 flex-1 flex-col p-3 pt-1 sm:p-6 sm:pt-3"
                                        >
                                            <ChartByDimension
                                                :dimension="slide.key"
                                                :data="filteredChartData"
                                                :stations="
                                                    props.results.stations
                                                "
                                                :tick-formatter="tickFormatter"
                                                :is-monthly-yearly="
                                                    isMonthlyYearlyQuery
                                                "
                                            />
                                        </CardContent>
                                    </Card>
                                </CarouselItem>
                            </CarouselContent>

                            <CarouselPrevious
                                v-if="showSideArrows"
                                class="top-1/2 left-3 z-10 -translate-y-1/2 sm:left-5"
                            />
                            <CarouselNext
                                v-if="showSideArrows"
                                class="top-1/2 right-3 z-10 -translate-y-1/2 sm:right-5"
                            />
                        </div>

                        <div
                            v-if="showBottomButtons"
                            class="mt-8 flex justify-center gap-3 pb-2"
                        >
                            <CarouselPrevious class="static h-10 w-10" />
                            <CarouselNext class="static h-10 w-10" />
                        </div>
                    </Carousel>
                </div>
            </div>
        </div>
    </section>
</template>
