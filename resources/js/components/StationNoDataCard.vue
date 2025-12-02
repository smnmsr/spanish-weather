<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { AlertCircle } from 'lucide-vue-next';

interface StationInfo {
    name?: string;
    provincia?: string | null;
}

interface Props {
    title: string;
    station: StationInfo;
}

defineProps<Props>();
</script>

<template>
    <Card
        class="border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900/50"
    >
        <CardHeader>
            <div class="flex items-start gap-3">
                <AlertCircle
                    class="mt-1 h-5 w-5 flex-shrink-0 text-amber-600 dark:text-amber-500"
                />
                <div class="flex-1">
                    <CardTitle class="text-slate-700 dark:text-slate-300">{{
                        station?.name ?? title
                    }}</CardTitle>
                    <CardDescription v-if="station?.provincia">{{
                        station.provincia
                    }}</CardDescription>
                </div>
            </div>
        </CardHeader>
        <CardContent>
            <p class="text-sm text-slate-600 dark:text-slate-400">
                Für diese Station sind keine Daten für den ausgewählten Zeitraum
                verfügbar. Dies kann bedeuten, dass die Station diese Art von
                Messungen nicht durchführt oder die Daten derzeit nicht
                verfügbar sind.
            </p>
        </CardContent>
    </Card>
</template>
