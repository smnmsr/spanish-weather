<script setup lang="ts">
import ChartByStation from '@/components/ChartByStation.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

interface StationInfo {
    name?: string;
    provincia?: string | null;
}

interface Props {
    title: string;
    station: StationInfo;
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
