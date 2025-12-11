<script setup lang="ts">
import {
    VisArea,
    VisAxis,
    VisLine,
    VisStackedBar,
    VisXYContainer,
} from '@unovis/vue';
import type { ChartDataPoint } from '@/types/station';
import ChartLegend from '@/components/ChartLegend.vue';

interface Props {
    data: ChartDataPoint[];
}

defineProps<Props>();

const legendItems = [
    { label: 'Temperatur (°C)', color: '#ef4444', shape: 'circle' as const },
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
    { label: 'Wind (km/h)', color: '#d946ef', shape: 'circle' as const, opacity: 0.7 },
];
</script>

<template>
    <div class="h-[400px] w-full">
        <VisXYContainer :data="data" :height="400">
            <VisLine
                :x="(d: any) => d.time"
                :y="(d: any) => d.temperature"
                color="#ef4444"
                :line-width="2"
            />
            <VisStackedBar
                :x="(d: any) => d.time"
                :y="(d: any) => d.precipitation"
                color="#3b82f6"
                :opacity="0.6"
            />
            <VisArea
                :x="(d: any) => d.time"
                :y="(d: any) => d.humidity"
                color="#eab308"
                :opacity="0.3"
            />
            <VisLine
                :x="(d: any) => d.time"
                :y="(d: any) => d.wind"
                color="#d946ef"
                :line-width="2"
                :opacity="0.7"
            />
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
            <VisAxis
                type="y"
                :grid-line="true"
                :tick-line="false"
                :domain-line="false"
            />
        </VisXYContainer>
    </div>
    <ChartLegend :items="legendItems" />
</template>
