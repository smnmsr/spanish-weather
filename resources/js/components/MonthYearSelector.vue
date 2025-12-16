<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Drawer, DrawerClose, DrawerContent } from '@/components/ui/drawer';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { MonthYearRange } from '@/types/data-query';
import { computed, ref } from 'vue';

interface Props {
    monthYearRange: MonthYearRange | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update-month-year-range', range: MonthYearRange): void;
}>();

const showDrawer = ref(false);
const validationMessage = ref('');

// Generate German month names using Intl API with fallback
const MONTH_NAMES: string[] = (() => {
    try {
        const locale = 'de';
        const months: string[] = [];
        for (let month = 0; month < 12; month++) {
            const date = new Date(2025, month, 1);
            const monthName = new Intl.DateTimeFormat(locale, {
                month: 'long',
            }).format(date);
            months.push(monthName);
        }
        return months;
    } catch {
        // Fallback to hardcoded German month names if Intl fails (e.g., in test environments)
        return [
            'Januar',
            'Februar',
            'März',
            'April',
            'Mai',
            'Juni',
            'Juli',
            'August',
            'September',
            'Oktober',
            'November',
            'Dezember',
        ];
    }
})();

const currentMonth = computed(() => props.monthYearRange?.month ?? 1);
const currentStartYear = computed(
    () => props.monthYearRange?.startYear ?? new Date().getFullYear() - 4,
);
const currentEndYear = computed(
    () => props.monthYearRange?.endYear ?? new Date().getFullYear(),
);

const displayRange = computed(() => {
    if (
        !currentMonth.value ||
        !currentStartYear.value ||
        !currentEndYear.value
    ) {
        return '---';
    }
    const monthName = MONTH_NAMES[currentMonth.value - 1] ?? '---';
    return `${monthName} (${currentStartYear.value}–${currentEndYear.value})`;
});

const tempMonth = ref(currentMonth.value);
const tempStartYear = ref(currentStartYear.value);
const tempEndYear = ref(currentEndYear.value);

function updateMonth(
    newMonth: string | number | bigint | Record<string, any> | null,
) {
    if (newMonth === null || typeof newMonth === 'object') return;
    const month = Number(newMonth);
    emit('update-month-year-range', {
        month,
        startYear: currentStartYear.value,
        endYear: currentEndYear.value,
    });
}

function updateStartYear(
    newYear: string | number | bigint | Record<string, any> | null,
) {
    if (newYear === null || typeof newYear === 'object') return;
    const year = Number(newYear);
    if (year <= currentEndYear.value && year >= 1900) {
        emit('update-month-year-range', {
            month: currentMonth.value,
            startYear: year,
            endYear: currentEndYear.value,
        });
    }
}

function updateEndYear(
    newYear: string | number | bigint | Record<string, any> | null,
) {
    if (newYear === null || typeof newYear === 'object') return;
    const year = Number(newYear);
    if (year >= currentStartYear.value && year <= new Date().getFullYear()) {
        emit('update-month-year-range', {
            month: currentMonth.value,
            startYear: currentStartYear.value,
            endYear: year,
        });
    }
}

function openDrawer() {
    tempMonth.value = currentMonth.value;
    tempStartYear.value = currentStartYear.value;
    tempEndYear.value = currentEndYear.value;
    validationMessage.value = '';
    showDrawer.value = true;
}

function applyCustomRange() {
    // Validate
    if (tempStartYear.value > tempEndYear.value) {
        validationMessage.value =
            'Startjahr muss kleiner oder gleich dem Endjahr sein.';
        return;
    }

    if (tempEndYear.value - tempStartYear.value > 50) {
        validationMessage.value = 'Maximal 50 Jahre Zeitraum erlaubt.';
        return;
    }

    validationMessage.value = '';

    emit('update-month-year-range', {
        month: tempMonth.value,
        startYear: tempStartYear.value,
        endYear: tempEndYear.value,
    });

    showDrawer.value = false;
}
</script>

<template>
    <div class="space-y-4">
        <!-- Display and controls -->
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    <slot name="label"
                        >Monat und Jahresbereich für die Analyse wählen</slot
                    >
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span
                    class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200"
                >
                    {{ displayRange }}
                </span>
                <Button
                    variant="outline"
                    size="sm"
                    class="md:hidden"
                    @click="openDrawer"
                >
                    Bearbeiten
                </Button>
            </div>
        </div>

        <!-- Month/year controls -->
        <div class="space-y-4">
            <!-- Month selector -->
            <div>
                <Label for="month-select-inline">Monat</Label>
                <Select
                    id="month-select-inline"
                    :model-value="String(currentMonth)"
                    @update:model-value="updateMonth"
                >
                    <SelectTrigger class="w-full">
                        <SelectValue :placeholder="'Monat wählen'" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="(monthName, index) in MONTH_NAMES"
                            :key="index + 1"
                            :value="String(index + 1)"
                        >
                            {{ monthName }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Year range inputs -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <Label for="start-year-inline">Startjahr</Label>
                    <Input
                        id="start-year-inline"
                        :model-value="currentStartYear"
                        type="number"
                        min="1900"
                        :max="new Date().getFullYear()"
                        placeholder="z.B. 2015"
                        @update:model-value="updateStartYear"
                    />
                </div>
                <div>
                    <Label for="end-year-inline">Endjahr</Label>
                    <Input
                        id="end-year-inline"
                        :model-value="currentEndYear"
                        type="number"
                        min="1900"
                        :max="new Date().getFullYear()"
                        placeholder="z.B. 2024"
                        @update:model-value="updateEndYear"
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- Drawer with month/year selectors -->
    <Drawer v-model:open="showDrawer">
        <DrawerContent>
            <div class="mx-auto w-full max-w-md p-6">
                <div class="mb-6">
                    <h2 class="text-lg font-semibold">
                        Monat und Zeitraum auswählen
                    </h2>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Wählen Sie einen Monat und einen Jahresbereich für die
                        Trendanalyse.
                    </p>
                </div>

                <div class="space-y-4">
                    <!-- Month selector -->
                    <div>
                        <Label for="month-select">Monat</Label>
                        <Select
                            id="month-select"
                            v-model="tempMonth"
                            :default-value="String(tempMonth)"
                        >
                            <SelectTrigger class="w-full">
                                <SelectValue :placeholder="'Monat wählen'" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="(monthName, index) in MONTH_NAMES"
                                    :key="index + 1"
                                    :value="String(index + 1)"
                                >
                                    {{ monthName }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Year range inputs -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <Label for="start-year">Startjahr</Label>
                            <Input
                                id="start-year"
                                v-model.number="tempStartYear"
                                type="number"
                                min="1900"
                                :max="new Date().getFullYear()"
                                placeholder="z.B. 2015"
                            />
                        </div>
                        <div>
                            <Label for="end-year">Endjahr</Label>
                            <Input
                                id="end-year"
                                v-model.number="tempEndYear"
                                type="number"
                                min="1900"
                                :max="new Date().getFullYear()"
                                placeholder="z.B. 2024"
                            />
                        </div>
                    </div>

                    <p
                        v-if="validationMessage"
                        class="text-sm text-red-500 dark:text-red-400"
                    >
                        {{ validationMessage }}
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <DrawerClose as-child>
                        <Button variant="outline">Abbrechen</Button>
                    </DrawerClose>
                    <Button @click="applyCustomRange">Anwenden</Button>
                </div>
            </div>
        </DrawerContent>
    </Drawer>
</template>
