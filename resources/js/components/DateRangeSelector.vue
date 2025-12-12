<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Drawer, DrawerClose, DrawerContent } from '@/components/ui/drawer';
import { RangeCalendar } from '@/components/ui/range-calendar';
import type { DateRangeSelection } from '@/types/data-query';
import { parseDate } from '@internationalized/date';
import { computed, ref } from 'vue';

interface Props {
    dateRange: DateRangeSelection | null;
    maxDays?: number;
    presets?: Array<{ key: string; label: string }>;
}

const props = withDefaults(defineProps<Props>(), {
    maxDays: 60,
    presets: () => [
        { key: 'last7', label: 'Letzte 7 Tage' },
        { key: 'last30', label: 'Letzte 30 Tage' },
        { key: 'thisMonth', label: 'Dieser Monat' },
    ],
});

const emit = defineEmits<{
    (e: 'update-date-range', range: DateRangeSelection): void;
    (e: 'apply-preset', preset: string): void;
}>();

const showDrawer = ref(false);
const validationMessage = ref('');
const manuallySelectedPreset = ref<string | null>(null);

const currentStartDate = computed(() => props.dateRange?.startDate ?? '');
const currentEndDate = computed(() => props.dateRange?.endDate ?? '');

// Compute which preset matches the current date range
function computePresetRange(preset: 'last7' | 'last30' | 'thisMonth'): {
    startDate: string;
    endDate: string;
} {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    const end = `${year}-${month}-${day}`;

    if (preset === 'thisMonth') {
        const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        const startYear = startOfMonth.getFullYear();
        const startMonth = String(startOfMonth.getMonth() + 1).padStart(2, '0');
        const startDay = String(startOfMonth.getDate()).padStart(2, '0');
        return {
            startDate: `${startYear}-${startMonth}-${startDay}`,
            endDate: end,
        };
    }

    const start = new Date(today);
    const days = preset === 'last7' ? 6 : 29;
    start.setDate(today.getDate() - days);
    const startYear = start.getFullYear();
    const startMonth = String(start.getMonth() + 1).padStart(2, '0');
    const startDay = String(start.getDate()).padStart(2, '0');

    return {
        startDate: `${startYear}-${startMonth}-${startDay}`,
        endDate: end,
    };
}

// Detect which preset (if any) matches the current date range
const selectedPreset = computed(() => {
    // If user manually selected a preset, use that
    if (manuallySelectedPreset.value) {
        return manuallySelectedPreset.value;
    }

    // Otherwise, try to detect which preset matches the current range
    if (!currentStartDate.value || !currentEndDate.value) {
        return 'last7'; // default
    }

    for (const preset of props.presets) {
        if (
            preset.key === 'last7' ||
            preset.key === 'last30' ||
            preset.key === 'thisMonth'
        ) {
            const presetRange = computePresetRange(
                preset.key as 'last7' | 'last30' | 'thisMonth',
            );
            if (
                presetRange.startDate === currentStartDate.value &&
                presetRange.endDate === currentEndDate.value
            ) {
                return preset.key;
            }
        }
    }

    return 'custom';
});

const displayRange = computed(() => {
    if (!currentStartDate.value || !currentEndDate.value) {
        return '---';
    }
    return `${currentStartDate.value} → ${currentEndDate.value}`;
});

const dateRangeComplete = computed(
    () => !!currentStartDate.value && !!currentEndDate.value,
);

// Convert string date to CalendarDate for RangeCalendar
const calendarValue = computed(() => {
    if (!dateRangeComplete.value) return undefined;
    try {
        return {
            start: parseDate(currentStartDate.value),
            end: parseDate(currentEndDate.value),
        };
    } catch (e) {
        console.error('Error parsing dates:', e);
        return undefined;
    }
});

function handlePresetClick(presetKey: string) {
    validationMessage.value = '';
    manuallySelectedPreset.value = presetKey;
    emit('apply-preset', presetKey);
}

function handleCalendarUpdate(range: any) {
    if (!range || !range.start || !range.end) return;

    const startStr = range.start.toString();
    const endStr = range.end.toString();

    // Check max days constraint
    const start = new Date(startStr);
    const end = new Date(endStr);
    const daysDiff = Math.floor(
        (end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24),
    );

    if (daysDiff > props.maxDays) {
        validationMessage.value = `Maximum ${props.maxDays} Tage erlaubt.`;
        return;
    }

    validationMessage.value = '';
    manuallySelectedPreset.value = 'custom';
    emit('update-date-range', {
        startDate: startStr,
        endDate: endStr,
    });
}

function openDrawer() {
    showDrawer.value = true;
}
</script>

<template>
    <div class="space-y-4">
        <!-- Display and controls -->
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    <slot name="label">Zeitraum für die Abfrage wählen</slot>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span
                    class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200"
                >
                    {{ displayRange }}
                </span>
                <Button
                    :variant="
                        selectedPreset === 'custom' ? 'default' : 'outline'
                    "
                    size="sm"
                    class="md:hidden"
                    @click="openDrawer"
                >
                    Bearbeiten
                </Button>
            </div>
        </div>

        <!-- Preset buttons + custom range button -->
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
            <slot name="presets" :handle-preset-click="handlePresetClick">
                <Button
                    v-for="preset in presets"
                    :key="preset.key"
                    :variant="
                        selectedPreset === preset.key ? 'default' : 'outline'
                    "
                    class="w-full justify-start text-left text-sm"
                    @click="handlePresetClick(preset.key)"
                >
                    {{ preset.label }}
                </Button>
            </slot>
            <Button
                :variant="selectedPreset === 'custom' ? 'default' : 'outline'"
                class="w-full justify-start text-left text-sm"
                @click="openDrawer"
            >
                Benutzerdefiniert
            </Button>
        </div>
    </div>

    <!-- Drawer with calendar (all screen sizes) -->
    <Drawer v-model:open="showDrawer">
        <DrawerContent>
            <div class="mx-auto w-full max-w-sm p-4">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold">Zeitraum auswählen</h2>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Klicken Sie auf Start- und Enddatum.
                    </p>
                </div>
                <div class="mb-4 flex justify-center">
                    <RangeCalendar
                        v-if="calendarValue"
                        :default-value="calendarValue"
                        :number-of-months="2"
                        @update:model-value="handleCalendarUpdate"
                    />
                    <RangeCalendar
                        v-else
                        :number-of-months="2"
                        @update:model-value="handleCalendarUpdate"
                    />
                </div>
                <p v-if="validationMessage" class="mb-4 text-sm text-red-500">
                    {{ validationMessage }}
                </p>
                <div class="flex justify-end">
                    <DrawerClose as-child>
                        <Button variant="secondary">Fertig</Button>
                    </DrawerClose>
                </div>
            </div>
        </DrawerContent>
    </Drawer>
</template>
