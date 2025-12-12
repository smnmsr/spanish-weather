<script setup lang="ts">
import ChartLegend from '@/components/ChartLegend.vue';
import type { ChartDataPoint } from '@/types/station';
import {
    VisAxis,
    VisCrosshair,
    VisLine,
    VisStackedBar,
    VisXYContainer,
} from '@unovis/vue';
import { computed } from 'vue';

interface Props {
    dimension: 'temperature' | 'precipitation' | 'humidity' | 'wind';
    data: Record<string, ChartDataPoint[]>;
    stations: Record<string, { name?: string; provincia?: string | null }>;
}

const props = defineProps<Props>();

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
    },
    precipitation: {
        label: 'Niederschlag',
        unit: 'mm',
        color: '#3b82f6',
        type: 'bar' as const,
    },
    humidity: {
        label: 'Luftfeuchtigkeit',
        unit: '%',
        color: '#eab308',
        type: 'line' as const,
    },
    wind: {
        label: 'Wind',
        unit: 'km/h',
        color: '#d946ef',
        type: 'line' as const,
    },
};

const config = computed(() => dimensionConfig[props.dimension]);

const chartMargin = { top: 8, bottom: 8, left: 8, right: 8 };

// Get original station order to maintain consistent colors
const allStationIds = computed(() => Object.keys(props.data));

// Helper to get consistent color for a station based on its original position
const getStationColor = (stationId: string) => {
    const originalIndex = allStationIds.value.indexOf(stationId);
    return stationColors[originalIndex % stationColors.length];
};

// Filter stations to only include those with valid data for this dimension
const stationsWithValidData = computed(() => {
    return Object.keys(props.data).filter((stationId) => {
        const stationData = props.data[stationId];
        return stationData?.some((point) => point[props.dimension] != null);
    });
});

// Transform data for visualization
const chartData = computed(() => {
    const allTimePoints = new Set<number>();

    // Collect all unique time points from stations with valid data
    stationsWithValidData.value.forEach((stationId) => {
        props.data[stationId]?.forEach((point) => {
            allTimePoints.add(point.time.getTime());
        });
    });

    // Sort time points
    const sortedTimes = Array.from(allTimePoints).sort((a, b) => a - b);

    // Create data points with only stations that have data for this dimension
    return sortedTimes.map((timestamp) => {
        const dataPoint: any = {
            time: new Date(timestamp),
        };

        stationsWithValidData.value.forEach((stationId) => {
            const point = props.data[stationId]?.find(
                (p) => p.time.getTime() === timestamp,
            );
            const value = point?.[props.dimension] ?? null;
            dataPoint[`station_${stationId}`] = value;
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

// Calculate Y-axis domain with margin
const yDomain = computed(() => {
    const values: number[] = [];

    stationsWithValidData.value.forEach((stationId) => {
        props.data[stationId]?.forEach((point) => {
            const value = point[props.dimension];
            if (value != null) {
                values.push(value);
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

    return [Math.max(0, min - margin), max + margin];
});
</script>

<template>
    <div class="flex h-full min-h-0 w-full flex-col">
        <div class="min-h-0 w-full flex-1">
            <VisXYContainer
                :data="chartData"
                :margin="chartMargin"
                class="h-full"
            >
                <!-- Render lines or bars for each station -->
                <template
                    v-for="stationId in stationsWithValidData"
                    :key="stationId"
                >
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
                    <VisStackedBar
                        v-else
                        :x="(d: any) => d.time"
                        :y="
                            (d: any) => {
                                const value = d[`station_${stationId}`];
                                return value != null ? value : undefined;
                            }
                        "
                        :color="getStationColor(stationId)"
                        :opacity="0.7"
                    />
                </template>

                <!-- X Axis -->
                <VisAxis
                    type="x"
                    :x="(d: any) => d.time"
                    :tick-format="
                        (d: number) => {
                            const date = new Date(d);
                            return date.toLocaleDateString('de-DE', {
                                day: '2-digit',
                                month: '2-digit',
                                hour: '2-digit',
                            });
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
                    :label="`${config.label} (${config.unit})`"
                    :domain="yDomain"
                />

                <!-- Crosshair -->
                <VisCrosshair :color="'#64748b'" />
            </VisXYContainer>
        </div>

        <ChartLegend :items="legendItems" class="flex-shrink-0 pt-2 sm:pt-3" />
    </div>
</template>
