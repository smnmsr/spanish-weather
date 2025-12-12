<script setup lang="ts">
import ChartLegend from '@/components/ChartLegend.vue';
import type { ChartDataPoint } from '@/types/station';
import { Tooltip } from '@unovis/ts';
import { VisAxis, VisLine, VisStackedBar, VisXYContainer } from '@unovis/vue';
import { computed } from 'vue';

interface Props {
    data: ChartDataPoint[];
    showTemperature?: boolean;
    showPrecipitation?: boolean;
    showHumidity?: boolean;
    showWind?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showTemperature: true,
    showPrecipitation: true,
    showHumidity: false,
    showWind: false,
});

const legendItems = [
    {
        label: 'Temperatur (°C)',
        color: '#ef4444',
        shape: 'circle' as const,
    },
    {
        label: 'Niederschlag (mm)',
        color: '#3b82f6',
        shape: 'square' as const,
        opacity: 0.6,
    },
    {
        label: 'Luftfeuchtigkeit (%)',
        color: '#eab308',
        shape: 'circle' as const,
        opacity: 0.5,
    },
    {
        label: 'Wind (km/h)',
        color: '#d946ef',
        shape: 'circle' as const,
        opacity: 0.7,
    },
];

const filteredLegendItems = computed(() => {
    return legendItems.filter((item) => {
        if (item.label === 'Temperatur (°C)' && !props.showTemperature)
            return false;
        if (item.label === 'Niederschlag (mm)' && !props.showPrecipitation)
            return false;
        if (item.label === 'Luftfeuchtigkeit (%)' && !props.showHumidity)
            return false;
        if (item.label === 'Wind (km/h)' && !props.showWind) return false;
        return true;
    });
});

// Tooltip to show data on hover
const tooltip = new Tooltip((d: ChartDataPoint) => {
    const parts = [];
    if (props.showTemperature && d.temperature != null) {
        parts.push(`Temp: ${d.temperature.toFixed(1)}°C`);
    }
    if (props.showPrecipitation && d.precipitation != null) {
        parts.push(`Niederschlag: ${d.precipitation.toFixed(1)}mm`);
    }
    if (props.showHumidity && d.humidity != null) {
        parts.push(`Luftfeuchtigkeit: ${d.humidity.toFixed(1)}%`);
    }
    if (props.showWind && d.wind != null) {
        parts.push(`Wind: ${d.wind.toFixed(1)}km/h`);
    }
    return parts.join('<br/>');
});
</script>

<template>
    <div class="w-full">
        <VisXYContainer
            :data="data"
            :height="400"
            :tooltip="tooltip"
            :margin="{ top: 5, bottom: 20, left: 80, right: 80 }"
        >
            <!-- Temperature Line -->
            <VisLine
                v-if="showTemperature"
                :x="(d: ChartDataPoint) => d.time"
                :y="(d: ChartDataPoint) => d.temperature"
                color="#ef4444"
                :line-width="2"
            />

            <!-- Precipitation Stacked Bar -->
            <VisStackedBar
                v-if="showPrecipitation"
                :x="(d: ChartDataPoint) => d.time"
                :y="(d: ChartDataPoint) => d.precipitation"
                color="#3b82f6"
                :opacity="0.6"
            />

            <!-- Humidity Line -->
            <VisLine
                v-if="showHumidity"
                :x="(d: ChartDataPoint) => d.time"
                :y="(d: ChartDataPoint) => d.humidity"
                color="#eab308"
                :line-width="2"
                :opacity="0.7"
            />

            <!-- Wind Line -->
            <VisLine
                v-if="showWind"
                :x="(d: ChartDataPoint) => d.time"
                :y="(d: ChartDataPoint) => d.wind"
                color="#d946ef"
                :line-width="2"
                :opacity="0.7"
            />

            <!-- X Axis -->
            <VisAxis
                type="x"
                :x="(d: ChartDataPoint) => d.time"
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

            <!-- Temperature Y Axis (Left) -->
            <VisAxis
                v-if="showTemperature"
                type="y"
                :grid-line="true"
                :tick-line="false"
                :domain-line="false"
                label="Temperatur (°C)"
            />

            <!-- Humidity Y Axis (Left, second) -->
            <VisAxis
                v-if="showHumidity && !showTemperature"
                type="y"
                :grid-line="true"
                :tick-line="false"
                :domain-line="false"
                label="Luftfeuchtigkeit (%)"
            />

            <!-- Precipitation Y Axis (Right) -->
            <VisAxis
                v-if="showPrecipitation"
                type="y"
                :position="'right'"
                :grid-line="false"
                :tick-line="false"
                :domain-line="false"
                label="Niederschlag (mm)"
            />

            <!-- Wind Y Axis (Right, second) -->
            <VisAxis
                v-if="showWind && !showPrecipitation"
                type="y"
                :position="'right'"
                :grid-line="false"
                :tick-line="false"
                :domain-line="false"
                label="Wind (km/h)"
            />
        </VisXYContainer>

        <ChartLegend :items="filteredLegendItems" />
    </div>
</template>
