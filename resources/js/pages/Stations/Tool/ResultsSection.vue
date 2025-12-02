<script setup lang="ts">
import StationCard from '@/components/StationCard.vue';
import StationNoDataCard from '@/components/StationNoDataCard.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { AlertCircle, Download } from 'lucide-vue-next';

interface QueryResults {
    queryType?: string;
    selectedStationIds?: string[];
    stations: Record<string, { name?: string; provincia?: string | null }>;
    observations?: any[];
}

interface Props {
    results: QueryResults;
    stationsWithData: string[];
    stationsWithoutData: string[];
    chartDataByStation: Record<string, any[]>;
    queryTypeTitle: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'clear-results'): void;
}>();
</script>

<template>
    <section class="min-h-screen bg-white p-8 dark:bg-slate-900">
        <div class="mx-auto max-w-7xl">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h2 class="mb-2 text-3xl font-bold">
                        {{ queryTypeTitle }}
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400">
                        Ergebnisse für
                        {{ props.results.selectedStationIds?.length || 0 }}
                        Station{{
                            (props.results.selectedStationIds?.length || 0) !==
                            1
                                ? 'en'
                                : ''
                        }}
                    </p>
                </div>
                <div class="flex gap-3">
                    <Button variant="outline" @click="emit('clear-results')"
                        >Ergebnisse ausblenden</Button
                    >
                    <Button
                        ><Download class="mr-2 h-4 w-4" />Daten
                        exportieren</Button
                    >
                </div>
            </div>

            <div
                v-if="
                    !props.results.observations ||
                    props.results.observations.length === 0
                "
                class="rounded-lg border border-slate-200 bg-white p-12 text-center dark:border-slate-800 dark:bg-slate-900"
            >
                <p class="text-lg text-slate-600 dark:text-slate-400">
                    Keine Daten verfügbar für die ausgewählten Stationen.
                </p>
            </div>

            <div class="space-y-8">
                <Alert v-if="stationsWithoutData.length > 0">
                    <AlertCircle class="h-4 w-4" />
                    <AlertTitle>Fehlende Daten</AlertTitle>
                    <AlertDescription>
                        <strong>{{ stationsWithoutData.length }}</strong> von
                        <strong>{{
                            props.results.selectedStationIds?.length || 0
                        }}</strong>
                        Station{{
                            (props.results.selectedStationIds?.length || 0) !==
                            1
                                ? 'en'
                                : ''
                        }}
                        {{ stationsWithoutData.length === 1 ? 'hat' : 'haben' }}
                        keine Daten für den ausgewählten Zeitraum:
                        <span class="font-medium">
                            {{
                                stationsWithoutData
                                    .map(
                                        (id: string) =>
                                            props.results.stations[id]?.name ||
                                            id,
                                    )
                                    .join(', ')
                            }}
                        </span>
                    </AlertDescription>
                </Alert>

                <StationCard
                    v-for="stationId in stationsWithData"
                    :key="stationId"
                    :title="stationId"
                    :station="props.results.stations[stationId] ?? {}"
                    :data="props.chartDataByStation[stationId]"
                />

                <StationNoDataCard
                    v-for="stationId in stationsWithoutData"
                    :key="'no-data-' + stationId"
                    :title="stationId"
                    :station="props.results.stations[stationId] ?? {}"
                />
            </div>
        </div>
    </section>
</template>
