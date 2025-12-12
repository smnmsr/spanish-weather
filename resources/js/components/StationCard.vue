<script setup lang="ts">
import ChartByStation from '@/components/ChartByStation.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { ChartDataPoint, StationInfo } from '@/types/station';

interface Props {
    title: string;
    station: StationInfo;
    data: ChartDataPoint[];
    showTemperature?: boolean;
    showPrecipitation?: boolean;
    showHumidity?: boolean;
    showWind?: boolean;
}

defineProps<Props>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ station?.name ?? title }}</CardTitle>
            <CardDescription v-if="station?.provincia">{{
                station.provincia
            }}</CardDescription>
        </CardHeader>
        <CardContent>
            <ChartByStation
                :data="data"
                :show-temperature="showTemperature ?? true"
                :show-precipitation="showPrecipitation ?? true"
                :show-humidity="showHumidity ?? false"
                :show-wind="showWind ?? false"
            />
        </CardContent>
    </Card>
</template>
