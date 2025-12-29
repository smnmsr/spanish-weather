<script setup lang="ts">
import ChartLegend from '@/components/ChartLegend.vue';
import type { ChartDataPoint } from '@/types';
import {
    VisArea,
    VisAxis,
    VisCrosshair,
    VisGroupedBar,
    VisLine,
    VisXYContainer,
} from '@unovis/vue';
import { computed } from 'vue';

interface Props {
    dimension:
        | 'temperature'
        | 'precipitation'
        | 'humidity'
        | 'wind'
        | 'windDirection'
        | 'pressure'
        | 'sunshine';
    data: Record<string, ChartDataPoint[]>;
    stations: Record<string, { name?: string; provincia?: string | null }>;
    tickFormatter?: (value: number) => string;
    isMonthlyYearly?: boolean;
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

// Generate colors for each station
const stationColors = [
    '#ef4444', // red
    '#3b82f6', // blue
    '#10b981', // green
    '#f59e0b', // amber
    '#8b5cf6', // purple
    '#ec4899', // pink
    '#14b8a6', // teal
    '#f97316', // orange
    '#06b6d4', // cyan
    '#84cc16', // lime
];

const dimensionConfig = {
    temperature: {
        label: 'Temperatur',
        unit: '°C',
        color: '#ef4444',
        type: 'line' as const,
        hasMinMax: true,
    },
    precipitation: {
        label: 'Niederschlag',
        unit: 'mm',
        color: '#3b82f6',
        type: 'bar' as const,
        hasMinMax: false,
    },
    humidity: {
        label: 'Luftfeuchtigkeit',
        unit: '%',
        color: '#eab308',
        type: 'line' as const,
        hasMinMax: true,
    },
    wind: {
        label: 'Wind',
        unit: 'km/h',
        color: '#d946ef',
        type: 'line' as const,
        hasMinMax: false,
    },
    windDirection: {
        label: 'Windrichtung',
        unit: '°',
        color: '#22c55e',
        type: 'line' as const,
        hasMinMax: false,
    },
    pressure: {
        label: 'Luftdruck',
        unit: 'hPa',
        color: '#0ea5e9',
        type: 'line' as const,
        hasMinMax: true,
    },
    sunshine: {
        label: 'Sonnenscheindauer',
        unit: 'h',
        color: '#f59e0b',
        type: 'bar' as const,
        hasMinMax: false,
    },
};

const config = computed(() => dimensionConfig[props.dimension]);

const chartMargin = { top: 8, bottom: 8, left: 8, right: 8 };

const axisTickFormatter = computed(
    () =>
        props.tickFormatter ??
        ((d: number) => {
            const date = new Date(d);
            try {
                return date.toLocaleDateString('de-DE', {
                    day: '2-digit',
                    month: '2-digit',
                    hour: '2-digit',
                });
            } catch {
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const hour = String(date.getHours()).padStart(2, '0');
                return `${day}.${month} ${hour}`;
            }
        }),
);

// Get original station order to maintain consistent colors
const allStationIds = computed(() => Object.keys(props.data));

// Helper to get consistent color for a station based on its original position
const getStationColor = (stationId: string) => {
    const originalIndex = allStationIds.value.indexOf(stationId);
    return stationColors[originalIndex % stationColors.length];
};

// Convert a hex color (e.g. #3b82f6) to rgba with alpha
const hexToRgba = (hex: string, alpha = 0.25): string => {
    const sanitized = hex.replace('#', '');
    const r = parseInt(sanitized.substring(0, 2), 16);
    const g = parseInt(sanitized.substring(2, 4), 16);
    const b = parseInt(sanitized.substring(4, 6), 16);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
};

// Filter stations to only include those with valid data for this dimension
const stationsWithValidData = computed(() => {
    return Object.keys(props.data).filter((stationId) => {
        const stationData = props.data[stationId];
        return stationData?.some((point) => point[props.dimension] != null);
    });
});

// Extract unique years from data for explicit tick positioning in monthly-yearly trends
const explicitTicks = computed(() => {
    if (!props.isMonthlyYearly) {
        return undefined;
    }

    const years = new Set<number>();

    // Collect all unique years from the data
    stationsWithValidData.value.forEach((stationId) => {
        props.data[stationId]?.forEach((point) => {
            const year = new Date(point.time).getFullYear();
            years.add(year);
        });
    });

    if (years.size === 0) {
        return undefined;
    }

    // Convert to sorted array of timestamp values representing Jan 1 of each year
    return Array.from(years)
        .sort((a, b) => a - b)
        .map((year) => new Date(year, 0, 1).getTime());
});

// Transform data for visualization
const chartData = computed(() => {
    const allTimePoints = new Set<number>();

    // Collect all unique time points from stations with valid data
    stationsWithValidData.value.forEach((stationId) => {
        props.data[stationId]?.forEach((point) => {
            // point.time is already a Unix timestamp in milliseconds
            allTimePoints.add(point.time);
        });
    });

    // Sort time points
    const sortedTimes = Array.from(allTimePoints).sort((a, b) => a - b);

    // Helper to get dimension-specific field names
    const getFieldName = (base: string) => {
        if (props.dimension === 'temperature') {
            if (base === 'max') return 'temperatureMax';
            if (base === 'min') return 'temperatureMin';
            return 'temperature';
        }
        if (props.dimension === 'humidity') {
            if (base === 'max') return 'humidityMax';
            if (base === 'min') return 'humidityMin';
            return 'humidity';
        }
        if (props.dimension === 'pressure') {
            if (base === 'max') return 'pressureMax';
            if (base === 'min') return 'pressureMin';
            return 'pressure';
        }
        return props.dimension;
    };

    // Create data points with only stations that have data for this dimension
    return sortedTimes.map((timestamp) => {
        const dataPoint: any = {
            time: new Date(timestamp),
        };

        stationsWithValidData.value.forEach((stationId) => {
            const point = props.data[stationId]?.find(
                (p) => p.time === timestamp,
            );

            // Main value (mean or only value)
            const value =
                point?.[getFieldName('mean') as keyof ChartDataPoint] ?? null;
            dataPoint[`station_${stationId}`] = value;

            // Min/Max values if available
            if (config.value.hasMinMax) {
                const maxField = getFieldName('max') as keyof ChartDataPoint;
                const minField = getFieldName('min') as keyof ChartDataPoint;
                const maxValue = point?.[maxField] ?? null;
                const minValue = point?.[minField] ?? null;

                dataPoint[`station_${stationId}_max`] = maxValue;
                dataPoint[`station_${stationId}_min`] = minValue;
            }

            // Include gust values for wind dimension
            if (props.dimension === 'wind') {
                const gustValue = point?.windGust ?? null;
                dataPoint[`station_${stationId}_gust`] = gustValue;
            }
        });

        return dataPoint;
    });
});

const legendItems = computed(() => {
    return stationsWithValidData.value.map((stationId) => ({
        label: props.stations[stationId]?.name || stationId,
        color: getStationColor(stationId),
        shape:
            config.value.type === 'line'
                ? ('circle' as const)
                : ('square' as const),
    }));
});

// Y accessors for grouped bars
const barYAccessors = computed(() => {
    if (config.value.type !== 'bar') return [];
    return stationsWithValidData.value.map(
        (stationId) => (d: any) => d[`station_${stationId}`],
    );
});

// Color accessor for grouped bars
const barColorAccessor = computed(() => {
    if (config.value.type !== 'bar') return undefined;
    return (d: any, i: number) => {
        const stationId = stationsWithValidData.value[i];
        return stationId ? getStationColor(stationId) : '#000';
    };
});

// Calculate Y-axis domain with margin
const yDomain = computed(() => {
    const values: number[] = [];

    stationsWithValidData.value.forEach((stationId) => {
        props.data[stationId]?.forEach((point) => {
            const value = point[props.dimension];
            if (value != null) {
                values.push(value);
            }

            // Include min/max values if available
            if (config.value.hasMinMax) {
                if (props.dimension === 'temperature') {
                    const tMin = point.temperatureMin;
                    const tMax = point.temperatureMax;
                    if (tMin != null) values.push(tMin);
                    if (tMax != null) values.push(tMax);
                } else if (props.dimension === 'humidity') {
                    const hMin = point.humidityMin;
                    const hMax = point.humidityMax;
                    if (hMin != null) values.push(hMin);
                    if (hMax != null) values.push(hMax);
                } else if (props.dimension === 'pressure') {
                    const pMin = point.pressureMin;
                    const pMax = point.pressureMax;
                    if (pMin != null) values.push(pMin);
                    if (pMax != null) values.push(pMax);
                }
            }

            // For wind, include gust values in domain
            if (props.dimension === 'wind') {
                const gust = (point as any).windGust;
                if (gust != null) values.push(gust as number);
            }
        });
    });

    if (values.length === 0) {
        return [0, 100];
    }

    const min = Math.min(...values);
    const max = Math.max(...values);
    const range = max - min;
    const margin = range * 0.1; // 10% margin

    let domainMin = min - margin;
    let domainMax = max + margin;

    // Apply dimension-specific limits
    if (props.dimension === 'humidity') {
        // Humidity must be between 0-100%
        domainMax = Math.min(domainMax, 100);
        domainMin = Math.max(domainMin, 0);
    }

    return [domainMin, domainMax];
});
</script>

<template>
    <div class="flex h-full min-h-0 w-full flex-col">
        <!-- Skeleton loading state -->
        <div v-if="loading" class="min-h-0 w-full flex-1 animate-pulse">
            <div
                class="h-full w-full rounded-lg bg-slate-200 dark:bg-slate-800"
            ></div>
        </div>
        <div v-else class="min-h-0 w-full flex-1">
            <VisXYContainer
                :data="chartData"
                :margin="chartMargin"
                :y-domain="yDomain"
                class="h-full"
            >
                <!-- Grouped bars for precipitation/sunshine (single component with array of Y accessors) -->
                <VisGroupedBar
                    v-if="config.type === 'bar'"
                    :x="(d: any) => d.time"
                    :y="barYAccessors"
                    :color="barColorAccessor"
                    :opacity="0.7"
                />

                <!-- Lines and areas for temperature/humidity/wind (one per station) -->
                <template
                    v-for="stationId in stationsWithValidData"
                    :key="stationId"
                >
                    <!-- Min/Max band using stacked area: [min, max-min] -->
                    <VisArea
                        v-if="config.hasMinMax && config.type === 'line'"
                        :x="(d: any) => d.time"
                        :y="[
                            (d: any) => {
                                const v = d[`station_${stationId}_min`];
                                return v != null ? v : undefined;
                            },
                            (d: any) => {
                                const minV = d[`station_${stationId}_min`];
                                const maxV = d[`station_${stationId}_max`];
                                if (minV == null || maxV == null)
                                    return undefined;
                                return Math.max(0, maxV - minV);
                            },
                        ]"
                        :color="
                            (_d: any, i: number) =>
                                i === 0
                                    ? 'rgba(0,0,0,0)'
                                    : hexToRgba(
                                          getStationColor(stationId),
                                          0.15,
                                      )
                        "
                        :interpolate-missing-data="true"
                    />

                    <!-- Mean line (solid) -->
                    <VisLine
                        v-if="config.type === 'line'"
                        :x="(d: any) => d.time"
                        :y="
                            (d: any) => {
                                const value = d[`station_${stationId}`];
                                return value != null ? value : undefined;
                            }
                        "
                        :color="getStationColor(stationId)"
                        :line-width="2"
                        :interpolate-missing-data="true"
                    />

                    <!-- Wind gust overlay area: [mean, gust-mean] -->
                    <VisArea
                        v-if="props.dimension === 'wind'"
                        :x="(d: any) => d.time"
                        :y="[
                            (d: any) => {
                                const meanV = d[`station_${stationId}`];
                                return meanV != null ? meanV : undefined;
                            },
                            (d: any) => {
                                const meanV = d[`station_${stationId}`];
                                const gustV = d[`station_${stationId}_gust`];
                                if (meanV == null || gustV == null)
                                    return undefined;
                                return Math.max(0, gustV - meanV);
                            },
                        ]"
                        :color="
                            (_d: any, i: number) =>
                                i === 0
                                    ? 'rgba(0,0,0,0)'
                                    : hexToRgba(getStationColor(stationId), 0.2)
                        "
                        :interpolate-missing-data="true"
                    />
                </template>

                <!-- X Axis -->
                <VisAxis
                    type="x"
                    :x="(d: any) => d.time"
                    :tick-format="axisTickFormatter"
                    :ticks="explicitTicks"
                    :grid-line="false"
                    :tick-line="false"
                />

                <!-- Y Axis -->
                <VisAxis
                    type="y"
                    :grid-line="true"
                    :tick-line="false"
                    :label="`${config.label} (${config.unit})`"
                />

                <!-- Crosshair -->
                <VisCrosshair :color="'#64748b'" />
            </VisXYContainer>
        </div>

        <!-- Skeleton legend when loading -->
        <div v-if="loading" class="flex-shrink-0 animate-pulse pt-2 sm:pt-3">
            <div class="flex flex-wrap gap-3">
                <div v-for="i in 3" :key="i" class="flex items-center gap-2">
                    <div
                        class="h-3 w-3 rounded-full bg-slate-300 dark:bg-slate-700"
                    ></div>
                    <div
                        class="h-4 w-24 rounded bg-slate-300 dark:bg-slate-700"
                    ></div>
                </div>
            </div>
        </div>
        <ChartLegend
            v-else
            :items="legendItems"
            class="flex-shrink-0 pt-2 sm:pt-3"
        />
    </div>
</template>
