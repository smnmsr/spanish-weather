<script setup lang="ts">
import {
    VisArea,
    VisAxis,
    VisLine,
    VisStackedBar,
    VisXYContainer,
} from '@unovis/vue';

interface Props {
    data: Array<{
        time: Date;
        temperature: number | null;
        precipitation: number | null;
        humidity: number | null;
        wind: number | null;
    }>;
}

defineProps<Props>();
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
    <div class="mt-4 flex flex-wrap justify-center gap-4 text-sm">
        <div class="flex items-center gap-2">
            <div class="h-3 w-3 rounded-full bg-[#ef4444]"></div>
            <span>Temperatur (°C)</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="h-3 w-3 rounded-sm bg-[#3b82f6] opacity-60"></div>
            <span>Niederschlag (mm)</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="h-3 w-3 rounded-full bg-[#eab308] opacity-50"></div>
            <span>Luftfeuchtigkeit (%)</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="h-3 w-3 rounded-full bg-[#d946ef] opacity-70"></div>
            <span>Wind (km/h)</span>
        </div>
    </div>
</template>
