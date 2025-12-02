<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { DataQueryType } from '@/types/data-query';
import { DATA_QUERY_OPTIONS } from '@/types/data-query';
import {
    AlertCircle,
    BarChart,
    Calendar,
    Clock,
    TrendingUp,
} from 'lucide-vue-next';

const iconComponents: Record<string, any> = {
    clock: Clock,
    calendar: Calendar,
    'trending-up': TrendingUp,
    'alert-circle': AlertCircle,
    'bar-chart': BarChart,
};

interface Props {
    selectedCount: number;
    selectedDataQuery: DataQueryType | null;
    isLoadingResults: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'select-data-query', type: DataQueryType): void;
    (e: 'go-to-map'): void;
    (e: 'proceed-with-data-query'): void;
}>();
</script>

<template>
    <section class="min-h-screen bg-slate-50 p-8 dark:bg-slate-950">
        <div class="mx-auto max-w-7xl">
            <div class="mb-8">
                <h2 class="mb-2 text-3xl font-bold">
                    Welche Daten möchten Sie abfragen?
                </h2>
                <p class="text-slate-600 dark:text-slate-400">
                    Wählen Sie die Art der Daten aus, die Sie für Ihre
                    {{ selectedCount }} ausgewählte{{
                        selectedCount !== 1 ? 'n' : ''
                    }}
                    Station{{ selectedCount !== 1 ? 'en' : '' }} abfragen
                    möchten.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="option in DATA_QUERY_OPTIONS"
                    :key="option.type"
                    :class="[
                        'cursor-pointer transition-all hover:shadow-lg',
                        props.selectedDataQuery === option.type
                            ? 'ring-2 ring-blue-500 dark:ring-blue-400'
                            : '',
                    ]"
                    @click="emit('select-data-query', option.type)"
                >
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <component
                                :is="iconComponents[option.icon]"
                                class="h-8 w-8 text-blue-600 dark:text-blue-400"
                            />
                            <div
                                v-if="option.quickWin"
                                class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400"
                            >
                                Quick Win
                            </div>
                        </div>
                        <CardTitle class="mt-4">{{ option.title }}</CardTitle>
                        <CardDescription>{{
                            option.description
                        }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div
                            class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400"
                        >
                            <Clock class="h-4 w-4" />
                            <span>{{ option.estimatedTime }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div
                v-if="
                    props.selectedDataQuery &&
                    DATA_QUERY_OPTIONS.find(
                        (o) => o.type === props.selectedDataQuery,
                    )?.requiresDateRange
                "
                class="mt-8"
            >
                <Card>
                    <CardHeader>
                        <CardTitle>Zeitraum auswählen</CardTitle>
                        <CardDescription
                            >Wählen Sie den gewünschten Zeitraum für Ihre
                            Datenabfrage.</CardDescription
                        >
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="start-date">Startdatum</Label>
                                <input
                                    id="start-date"
                                    type="date"
                                    class="w-full rounded-md border border-slate-200 p-2 dark:border-slate-800 dark:bg-slate-900"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="end-date">Enddatum</Label>
                                <input
                                    id="end-date"
                                    type="date"
                                    class="w-full rounded-md border border-slate-200 p-2 dark:border-slate-800 dark:bg-slate-900"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="mt-8 flex justify-end gap-4">
                <Button variant="outline" @click="emit('go-to-map')"
                    >Zurück zur Auswahl</Button
                >
                <Button
                    @click="emit('proceed-with-data-query')"
                    :disabled="
                        !props.selectedDataQuery || props.isLoadingResults
                    "
                >
                    <Spinner
                        v-if="props.isLoadingResults"
                        class="mr-2 h-4 w-4"
                    />
                    {{ props.isLoadingResults ? 'Lädt...' : 'Daten abfragen' }}
                </Button>
            </div>
        </div>
    </section>
</template>
