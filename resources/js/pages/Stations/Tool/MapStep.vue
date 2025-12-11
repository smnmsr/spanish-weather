<script setup lang="ts">
import HelpButton from '@/components/HelpButton.vue';
import SelectedStationsList from '@/components/SelectedStationsList.vue';
import StationsMap from '@/components/StationsMap.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { computed, ref } from 'vue';

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
    selectedIds: Set<string>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'toggle-station', id: string): void;
    (e: 'reset-selection'): void;
    (e: 'go-to-data-options'): void;
    (e: 'map-ready'): void;
    (e: 'invalidate-map'): void;
}>();

const MAX_STATIONS = 5;
const selectedCount = computed(() => props.selectedIds.size);
const isMaxStationsReached = computed(
    () => selectedCount.value >= MAX_STATIONS,
);

function handleStationToggle(id: string) {
    const isCurrentlySelected = props.selectedIds.has(id);
    if (!isCurrentlySelected && isMaxStationsReached.value) {
        return;
    }
    emit('toggle-station', id);
}

const selectedStations = computed(() => {
    return props.stations.filter(
        (station) => station.id && props.selectedIds.has(station.id),
    );
});

const mapComponentRef = ref<InstanceType<typeof StationsMap> | null>(null);
const sheetOpen = ref(false);
</script>

<template>
    <div class="flex h-full flex-col">
        <!-- Compact Header -->
        <div class="flex-shrink-0 border-b p-4">
            <div class="mx-auto flex max-w-7xl items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-bold sm:text-xl">
                            Stationen wählen
                        </h2>
                        <HelpButton
                            title="Stationen auswählen"
                            description="So wählen Sie Wetterstationen aus"
                        >
                            <div class="space-y-6">
                                <div>
                                    <h3 class="mb-2 text-base font-semibold">
                                        Stationen auf der Karte auswählen
                                    </h3>
                                    <p
                                        class="leading-relaxed text-muted-foreground"
                                    >
                                        Klicken Sie auf die Marker auf der
                                        Karte, um Wetterstationen auszuwählen
                                        oder abzuwählen. Sie können bis zu
                                        {{ MAX_STATIONS }} Stationen
                                        gleichzeitig auswählen.
                                    </p>
                                </div>
                                <div>
                                    <h3 class="mb-2 text-base font-semibold">
                                        Cluster-Ansicht
                                    </h3>
                                    <p
                                        class="leading-relaxed text-muted-foreground"
                                    >
                                        Mehrere nahe beieinanderliegende
                                        Stationen werden automatisch gruppiert.
                                        Zoomen Sie hinein oder klicken Sie auf
                                        einen Cluster, um einzelne Stationen zu
                                        sehen.
                                    </p>
                                </div>
                                <div>
                                    <h3 class="mb-2 text-base font-semibold">
                                        Ausgewählte Stationen ansehen
                                    </h3>
                                    <p
                                        class="leading-relaxed text-muted-foreground"
                                    >
                                        Tippen Sie auf "Liste", um Ihre
                                        ausgewählten Stationen anzuzeigen und zu
                                        verwalten.
                                    </p>
                                </div>
                            </div>
                        </HelpButton>
                    </div>
                    <p class="text-xs text-muted-foreground sm:text-sm">
                        <span v-if="selectedCount > 0" class="font-medium">
                            {{ selectedCount }}/{{ MAX_STATIONS }}
                        </span>
                        <span v-else>Klicken Sie auf die Karte</span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Sheet v-model:open="sheetOpen">
                        <SheetTrigger as-child>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="selectedCount === 0"
                                class="relative"
                            >
                                <span class="hidden sm:inline">Ausgewählt</span>
                                <span class="sm:hidden">Liste</span>
                                <Badge
                                    v-if="selectedCount > 0"
                                    variant="default"
                                    class="ml-2"
                                >
                                    {{ selectedCount }}
                                </Badge>
                            </Button>
                        </SheetTrigger>
                        <SheetContent
                            side="bottom"
                            class="max-h-[85vh] p-6 sm:p-8"
                        >
                            <SheetHeader class="mx-auto max-w-4xl">
                                <SheetTitle>Ausgewählte Stationen</SheetTitle>
                                <SheetDescription>
                                    {{ selectedCount }} von {{ MAX_STATIONS }}
                                    Stationen ausgewählt
                                </SheetDescription>
                            </SheetHeader>
                            <div
                                class="mx-auto mt-6 max-h-[60vh] max-w-4xl overflow-auto"
                            >
                                <SelectedStationsList
                                    :stations="selectedStations"
                                    @remove="(id) => emit('toggle-station', id)"
                                />
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>
            </div>
        </div>

        <!-- Map - takes remaining space -->
        <div class="flex-1">
            <StationsMap
                ref="mapComponentRef"
                :stations="stations"
                :selectable="true"
                :selected-station-ids="selectedIds"
                :show-coverage-on-hover="true"
                height="100%"
                @station-click="handleStationToggle"
                @map-ready="() => emit('map-ready')"
            />
        </div>

        <!-- Compact Action Buttons -->
        <div class="flex-shrink-0 border-t p-3 shadow-lg sm:p-4">
            <div
                class="mx-auto flex max-w-7xl items-center justify-between gap-3"
            >
                <Button
                    variant="ghost"
                    size="sm"
                    @click="emit('reset-selection')"
                    :disabled="selectedCount === 0"
                    class="text-xs sm:text-sm"
                >
                    Zurücksetzen
                </Button>
                <Button
                    size="sm"
                    @click="emit('go-to-data-options')"
                    :disabled="selectedCount === 0"
                    class="text-xs sm:text-sm"
                >
                    Weiter ({{ selectedCount }})
                </Button>
            </div>
        </div>
    </div>
</template>
