<script setup lang="ts">
import ChartByStation from '@/components/ChartByStation.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { StationInfo, ChartDataPoint } from '@/types/station';

interface Props {
    title: string;
    station: StationInfo;
    data: ChartDataPoint[];
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
            <ChartByStation :data="data" />
        </CardContent>
    </Card>
</template>
