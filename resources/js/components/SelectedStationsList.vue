<script setup lang="ts">
import { MapPin, Mountain, X } from 'lucide-vue-next';

interface Station {
    id: string | null;
    name: string;
    lat: string | number;
    lon: string | number;
    provincia?: string | null;
    altitude?: number | null;
}

interface Props {
    stations: Station[];
}

defineProps<Props>();

const emit = defineEmits<{
    (e: 'remove', id: string): void;
}>();

function formatCoordinate(lat: string | number, lon: string | number): string {
    const latNum = typeof lat === 'number' ? lat : parseFloat(String(lat));
    const lonNum = typeof lon === 'number' ? lon : parseFloat(String(lon));
    return `${latNum.toFixed(4)}°, ${lonNum.toFixed(4)}°`;
}
</script>

<template>
    <div
        v-if="stations.length === 0"
        class="py-8 text-center text-muted-foreground"
    >
        <p class="text-sm">Keine Stationen ausgewählt</p>
        <p class="mt-2 text-xs">
            Klicken Sie auf die Marker auf der Karte, um Stationen auszuwählen.
        </p>
    </div>

    <div v-else class="grid gap-3 sm:grid-cols-2">
        <div
            v-for="station in stations"
            :key="station.id ?? ''"
            class="group relative overflow-hidden rounded-lg border bg-card transition-all hover:border-primary/50 hover:shadow-md"
        >
            <div class="flex items-start gap-3 p-4">
                <div class="min-w-0 flex-1 space-y-2">
                    <div>
                        <h4 class="truncate leading-tight font-semibold">
                            {{ station.name }}
                        </h4>
                        <p
                            v-if="station.provincia"
                            class="truncate text-sm text-muted-foreground"
                        >
                            {{ station.provincia }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-x-3 gap-y-1.5 text-xs">
                        <div
                            class="flex items-center gap-1 text-muted-foreground"
                        >
                            <MapPin class="h-3.5 w-3.5" />
                            <span class="font-mono">{{
                                formatCoordinate(station.lat, station.lon)
                            }}</span>
                        </div>
                        <div
                            v-if="station.altitude"
                            class="flex items-center gap-1 text-muted-foreground"
                        >
                            <Mountain class="h-3.5 w-3.5" />
                            <span>{{ station.altitude }} m</span>
                        </div>
                    </div>
                </div>

                <button
                    @click="emit('remove', station.id!)"
                    class="flex-shrink-0 rounded-md border bg-background px-2.5 py-1.5 text-xs font-medium text-muted-foreground transition-colors hover:border-destructive hover:bg-destructive hover:text-destructive-foreground"
                    title="Aus Liste entfernen"
                >
                    <X class="h-3.5 w-3.5" />
                </button>
            </div>
        </div>
    </div>
</template>
