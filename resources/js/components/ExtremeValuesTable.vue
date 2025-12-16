<script setup lang="ts">
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { computed } from 'vue';

interface ExtremeValuesRecord {
    indicativo: string;
    nombre: string;
    ubicacion: string;
    dimension: 'temperature' | 'precipitation' | 'wind';
    [key: string]: any;
}

type ExtremeMetricKey =
    | 'temperature-max'
    | 'temperature-min'
    | 'temperature-avg-high'
    | 'temperature-avg-low'
    | 'precipitation-max-day'
    | 'precipitation-max-month'
    | 'precipitation-min-month'
    | 'wind-max-gust';

interface Props {
    extremeValues: ExtremeValuesRecord[];
    stationDetails: Record<
        string,
        { id: string; name: string; provincia: string }
    >;
    dimensions?: Array<'temperature' | 'precipitation' | 'wind'>;
    metric?: ExtremeMetricKey;
}

const props = defineProps<Props>();

const dimensionLabels: Record<string, string> = {
    temperature: 'Temperatur',
    precipitation: 'Niederschlag',
    wind: 'Wind',
};

const activeDimensions = computed(
    () => props.dimensions ?? ['temperature', 'precipitation', 'wind'],
);
const activeDimensionSet = computed(() => new Set(activeDimensions.value));

const groupedByDimension = computed(() => {
    const grouped: Record<string, Record<string, ExtremeValuesRecord[]>> = {
        temperature: {},
        precipitation: {},
        wind: {},
    };

    props.extremeValues.forEach((record) => {
        const dimension = record.dimension as keyof typeof grouped;
        if (!grouped[dimension]) {
            grouped[dimension] = {};
        }

        const stationId = record.indicativo || record.idema;
        if (!grouped[dimension][stationId]) {
            grouped[dimension][stationId] = [];
        }

        grouped[dimension][stationId].push(record);
    });

    return grouped;
});

// Format temperature value from tenths of degrees to display format
const formatTemp = (value: string | number | null): string => {
    if (value === null || value === undefined) return '—';
    const num = Number(value);
    return (num / 10).toFixed(1) + ' °C';
};

// Format precipitation value from tenths of mm
const formatPrecip = (value: string | number | null): string => {
    if (value === null || value === undefined) return '—';
    const num = Number(value);
    return (num / 10).toFixed(1) + ' mm';
};

// Format wind speed (already in km/h for extreme values)
const formatWind = (value: string | number | null): string => {
    if (value === null || value === undefined) return '—';
    const num = Number(value);
    return Math.round(num) + ' km/h';
};

// Format hour as HH:MM
const formatTime = (timeStr: string): string => {
    if (!timeStr || timeStr.length < 5) return timeStr;
    const parts = timeStr.split('-');
    return parts[0] + ':' + parts[1];
};

// Parse month number to German name
const monthName = (monthNum: string | number): string => {
    const names = [
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
    const idx = Number(monthNum) - 1;
    return names[idx] || '?';
};

const formatDateParts = (
    day?: string | number | null,
    month?: string | number | null,
    year?: string | number | null,
): string => {
    const parts: string[] = [];

    if (day !== null && day !== undefined && day !== '') {
        parts.push(`${String(day).padStart(2, '0')}.`);
    }

    if (month !== null && month !== undefined && month !== '') {
        parts.push(monthName(month));
    }

    if (year !== null && year !== undefined && year !== '') {
        parts.push(String(year));
    }

    return parts.length > 0 ? parts.join(' ') : '—';
};

const formatMonthYear = (
    month?: string | number | null,
    year?: string | number | null,
): string => {
    if (month === undefined && year === undefined) return '—';

    const parts: string[] = [];

    if (month !== null && month !== undefined && month !== '') {
        parts.push(monthName(month));
    }

    if (year !== null && year !== undefined && year !== '') {
        parts.push(String(year));
    }

    return parts.length > 0 ? parts.join(' ') : '—';
};

const formatDateTime = (
    day?: string | number | null,
    month?: string | number | null,
    year?: string | number | null,
    timeStr?: string | null,
): string => {
    const datePart = formatDateParts(day, month, year);
    const timePart = timeStr ? formatTime(timeStr) : '';

    if (!timePart) {
        return datePart;
    }

    if (datePart === '—') {
        return timePart;
    }

    return `${datePart}, ${timePart}`;
};

interface MetricConfig {
    dimension: 'temperature' | 'precipitation' | 'wind';
    value: (record?: ExtremeValuesRecord) => string;
    date: (record?: ExtremeValuesRecord) => string;
    secondary?: (record?: ExtremeValuesRecord) => string | null;
}

const metricConfigs: Record<ExtremeMetricKey, MetricConfig> = {
    'temperature-max': {
        dimension: 'temperature',
        value: (record) => formatTemp(record?.temMax?.[12]),
        date: (record) =>
            formatDateParts(
                record?.diaMax?.[12],
                record?.mesMax,
                record?.anioMax?.[12],
            ),
    },
    'temperature-min': {
        dimension: 'temperature',
        value: (record) => formatTemp(record?.temMin?.[12]),
        date: (record) =>
            formatDateParts(
                record?.diaMin?.[12],
                record?.mesMin,
                record?.anioMin?.[12],
            ),
    },
    'temperature-avg-high': {
        dimension: 'temperature',
        value: (record) => formatTemp(record?.temMedAlta?.[12]),
        date: (record) =>
            formatMonthYear(record?.mesMedAlta, record?.anioMedAlta?.[12]),
    },
    'temperature-avg-low': {
        dimension: 'temperature',
        value: (record) => formatTemp(record?.temMedBaja?.[12]),
        date: (record) =>
            formatMonthYear(record?.mesMedBaja, record?.anioMedBaja?.[12]),
    },
    'precipitation-max-day': {
        dimension: 'precipitation',
        value: (record) => formatPrecip(record?.precMaxDia?.[12]),
        date: (record) =>
            formatDateParts(
                record?.diaMaxDia?.[12],
                record?.mesMaxDia,
                record?.anioMaxDia?.[12],
            ),
    },
    'precipitation-max-month': {
        dimension: 'precipitation',
        value: (record) => formatPrecip(record?.precMaxMen?.[12]),
        date: (record) =>
            formatMonthYear(record?.mesMaxMen, record?.anioMaxMen?.[12]),
    },
    'precipitation-min-month': {
        dimension: 'precipitation',
        value: (record) => formatPrecip(record?.precMinMen?.[12]),
        date: (record) =>
            formatMonthYear(record?.mesMinMen, record?.anioMinMes?.[12]),
    },
    'wind-max-gust': {
        dimension: 'wind',
        value: (record) => formatWind(record?.rachMax?.[12]),
        date: (record) =>
            formatDateTime(
                record?.dia?.[12],
                record?.mes,
                record?.anio?.[12],
                record?.hora?.[12],
            ),
        secondary: (record) => {
            const direction = record?.dirRachMax?.[12];
            return direction ? `Richtung: ${direction}°` : null;
        },
    },
};

const getStationLabel = (
    stationId: string,
    record?: ExtremeValuesRecord,
): string => props.stationDetails[stationId]?.name || record?.name || stationId;

const metricEntries = computed(() => {
    if (!props.metric) return [];

    const config = metricConfigs[props.metric];
    const stationsForDimension =
        groupedByDimension.value[config.dimension] ?? {};

    return Object.entries(stationsForDimension)
        .map(([stationId, records]) => {
            const record = records?.[0];
            return {
                stationId,
                stationName: getStationLabel(stationId, record),
                value: config.value(record),
                dateLabel: config.date(record),
                secondary: config.secondary?.(record) ?? null,
            };
        })
        .filter((entry) => entry.value !== '—');
});
</script>

<template>
    <div class="w-full space-y-8">
        <div v-if="metric" class="space-y-4">
            <div
                v-if="metricEntries.length > 0"
                class="grid gap-3 sm:gap-4 md:grid-cols-2"
            >
                <div
                    v-for="entry in metricEntries"
                    :key="entry.stationId"
                    class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p
                                class="text-xs tracking-wide text-slate-500 uppercase dark:text-slate-400"
                            >
                                Station
                            </p>
                            <p
                                class="text-base font-semibold text-slate-900 dark:text-slate-50"
                            >
                                {{ entry.stationName }}
                            </p>
                        </div>
                        <p
                            class="text-lg font-bold text-slate-900 dark:text-slate-50"
                        >
                            {{ entry.value }}
                        </p>
                    </div>
                    <p class="mt-2 text-sm text-slate-700 dark:text-slate-300">
                        {{ entry.dateLabel }}
                    </p>
                    <p
                        v-if="entry.secondary"
                        class="text-xs text-slate-500 dark:text-slate-400"
                    >
                        {{ entry.secondary }}
                    </p>
                </div>
            </div>
            <p v-else class="text-sm text-slate-600 dark:text-slate-400">
                Keine Daten für diese Kennzahl.
            </p>
        </div>

        <template v-else>
            <!-- Temperature Extremes -->
            <div
                v-if="
                    activeDimensionSet.has('temperature') &&
                    groupedByDimension.temperature &&
                    Object.keys(groupedByDimension.temperature).length > 0
                "
            >
                <h3
                    class="mb-4 text-lg font-semibold text-slate-900 dark:text-slate-50"
                >
                    {{ dimensionLabels.temperature }}
                </h3>
                <div class="space-y-6">
                    <div
                        v-for="(
                            records, stationId
                        ) in groupedByDimension.temperature"
                        :key="`temp-${stationId}`"
                        class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-800"
                    >
                        <div class="bg-slate-50 px-4 py-3 dark:bg-slate-900">
                            <p
                                class="font-medium text-slate-900 dark:text-slate-50"
                            >
                                {{
                                    stationDetails[stationId]?.name ||
                                    records[0]?.name ||
                                    stationId
                                }}
                            </p>
                        </div>
                        <Table>
                            <TableHeader>
                                <TableRow
                                    class="border-t border-slate-200 dark:border-slate-800"
                                >
                                    <TableHead class="text-left"
                                        >Extremwert</TableHead
                                    >
                                    <TableHead class="text-center"
                                        >Minimum</TableHead
                                    >
                                    <TableHead class="text-center"
                                        >Datum</TableHead
                                    >
                                    <TableHead class="text-center"
                                        >Maximum</TableHead
                                    >
                                    <TableHead class="text-center"
                                        >Datum</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <!-- Absolute Min/Max -->
                                <TableRow>
                                    <TableCell
                                        class="font-medium text-slate-700 dark:text-slate-300"
                                    >
                                        Absolutes Extrem
                                    </TableCell>
                                    <TableCell class="text-center">
                                        {{
                                            formatTemp(records[0]?.temMin?.[12])
                                        }}
                                    </TableCell>
                                    <TableCell
                                        class="text-center text-sm text-slate-600 dark:text-slate-400"
                                    >
                                        {{ records[0]?.diaMin?.[12] }}.{{
                                            monthName(records[0]?.mesMin)
                                        }}
                                        {{ records[0]?.anioMin?.[12] }}
                                    </TableCell>
                                    <TableCell class="text-center">
                                        {{
                                            formatTemp(records[0]?.temMax?.[12])
                                        }}
                                    </TableCell>
                                    <TableCell
                                        class="text-center text-sm text-slate-600 dark:text-slate-400"
                                    >
                                        {{ records[0]?.diaMax?.[12] }}.{{
                                            monthName(records[0]?.mesMax)
                                        }}
                                        {{ records[0]?.anioMax?.[12] }}
                                    </TableCell>
                                </TableRow>
                                <!-- Current Year Stats -->
                                <TableRow
                                    class="bg-slate-50 dark:bg-slate-900/50"
                                >
                                    <TableCell
                                        class="font-medium text-slate-700 dark:text-slate-300"
                                    >
                                        Durchschnitt Tief
                                    </TableCell>
                                    <TableCell class="text-center">
                                        {{
                                            formatTemp(
                                                records[0]?.temMedBaja?.[12],
                                            )
                                        }}
                                    </TableCell>
                                    <TableCell
                                        class="text-center text-sm text-slate-600 dark:text-slate-400"
                                    >
                                        {{ monthName(records[0]?.mesMedBaja) }}
                                    </TableCell>
                                    <TableCell colspan="2"></TableCell>
                                </TableRow>
                                <TableRow
                                    class="bg-slate-50 dark:bg-slate-900/50"
                                >
                                    <TableCell
                                        class="font-medium text-slate-700 dark:text-slate-300"
                                    >
                                        Durchschnitt Hoch
                                    </TableCell>
                                    <TableCell colspan="2"></TableCell>
                                    <TableCell class="text-center">
                                        {{
                                            formatTemp(
                                                records[0]?.temMedAlta?.[12],
                                            )
                                        }}
                                    </TableCell>
                                    <TableCell
                                        class="text-center text-sm text-slate-600 dark:text-slate-400"
                                    >
                                        {{ monthName(records[0]?.mesMedAlta) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </div>

            <!-- Precipitation Extremes -->
            <div
                v-if="
                    activeDimensionSet.has('precipitation') &&
                    groupedByDimension.precipitation &&
                    Object.keys(groupedByDimension.precipitation).length > 0
                "
            >
                <h3
                    class="mb-4 text-lg font-semibold text-slate-900 dark:text-slate-50"
                >
                    {{ dimensionLabels.precipitation }}
                </h3>
                <div class="space-y-6">
                    <div
                        v-for="(
                            records, stationId
                        ) in groupedByDimension.precipitation"
                        :key="`prec-${stationId}`"
                        class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-800"
                    >
                        <div class="bg-slate-50 px-4 py-3 dark:bg-slate-900">
                            <p
                                class="font-medium text-slate-900 dark:text-slate-50"
                            >
                                {{
                                    stationDetails[stationId]?.name ||
                                    records[0]?.name ||
                                    stationId
                                }}
                            </p>
                        </div>
                        <Table>
                            <TableHeader>
                                <TableRow
                                    class="border-t border-slate-200 dark:border-slate-800"
                                >
                                    <TableHead class="text-left"
                                        >Extremwert</TableHead
                                    >
                                    <TableHead class="text-center"
                                        >Wert</TableHead
                                    >
                                    <TableHead class="text-center"
                                        >Datum</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <!-- Max daily precipitation -->
                                <TableRow>
                                    <TableCell
                                        class="font-medium text-slate-700 dark:text-slate-300"
                                    >
                                        Max. Tagesniederschlag
                                    </TableCell>
                                    <TableCell class="text-center">
                                        {{
                                            formatPrecip(
                                                records[0]?.precMaxDia?.[12],
                                            )
                                        }}
                                    </TableCell>
                                    <TableCell
                                        class="text-center text-sm text-slate-600 dark:text-slate-400"
                                    >
                                        {{ records[0]?.diaMaxDia?.[12] }}.{{
                                            monthName(records[0]?.mesMaxDia)
                                        }}
                                        {{ records[0]?.anioMaxDia?.[12] }}
                                    </TableCell>
                                </TableRow>
                                <!-- Max monthly precipitation -->
                                <TableRow>
                                    <TableCell
                                        class="font-medium text-slate-700 dark:text-slate-300"
                                    >
                                        Max. Monatsniederschlag
                                    </TableCell>
                                    <TableCell class="text-center">
                                        {{
                                            formatPrecip(
                                                records[0]?.precMaxMen?.[12],
                                            )
                                        }}
                                    </TableCell>
                                    <TableCell
                                        class="text-center text-sm text-slate-600 dark:text-slate-400"
                                    >
                                        {{ monthName(records[0]?.mesMaxMen) }}
                                        {{ records[0]?.anioMaxMen?.[12] }}
                                    </TableCell>
                                </TableRow>
                                <!-- Min monthly precipitation -->
                                <TableRow
                                    class="bg-slate-50 dark:bg-slate-900/50"
                                >
                                    <TableCell
                                        class="font-medium text-slate-700 dark:text-slate-300"
                                    >
                                        Min. Monatsniederschlag
                                    </TableCell>
                                    <TableCell class="text-center">
                                        {{
                                            formatPrecip(
                                                records[0]?.precMinMen?.[12],
                                            )
                                        }}
                                    </TableCell>
                                    <TableCell
                                        class="text-center text-sm text-slate-600 dark:text-slate-400"
                                    >
                                        {{ monthName(records[0]?.mesMinMen) }}
                                        {{ records[0]?.anioMinMes?.[12] }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </div>

            <!-- Wind Extremes -->
            <div
                v-if="
                    activeDimensionSet.has('wind') &&
                    groupedByDimension.wind &&
                    Object.keys(groupedByDimension.wind).length > 0
                "
            >
                <h3
                    class="mb-4 text-lg font-semibold text-slate-900 dark:text-slate-50"
                >
                    {{ dimensionLabels.wind }}
                </h3>
                <div class="space-y-6">
                    <div
                        v-for="(records, stationId) in groupedByDimension.wind"
                        :key="`wind-${stationId}`"
                        class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-800"
                    >
                        <div class="bg-slate-50 px-4 py-3 dark:bg-slate-900">
                            <p
                                class="font-medium text-slate-900 dark:text-slate-50"
                            >
                                {{
                                    stationDetails[stationId]?.name ||
                                    records[0]?.name ||
                                    stationId
                                }}
                            </p>
                        </div>
                        <Table>
                            <TableHeader>
                                <TableRow
                                    class="border-t border-slate-200 dark:border-slate-800"
                                >
                                    <TableHead class="text-left"
                                        >Extremwert</TableHead
                                    >
                                    <TableHead class="text-center"
                                        >Geschwindigkeit</TableHead
                                    >
                                    <TableHead class="text-center"
                                        >Richtung</TableHead
                                    >
                                    <TableHead class="text-center"
                                        >Datum / Zeit</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <!-- Max gust for each month -->
                                <TableRow>
                                    <TableCell
                                        class="font-medium text-slate-700 dark:text-slate-300"
                                    >
                                        Max. Windböe
                                    </TableCell>
                                    <TableCell class="text-center">
                                        {{
                                            formatWind(
                                                records[0]?.rachMax?.[12],
                                            )
                                        }}
                                    </TableCell>
                                    <TableCell
                                        class="text-center text-sm text-slate-600 dark:text-slate-400"
                                    >
                                        {{ records[0]?.dirRachMax?.[12] }}°
                                    </TableCell>
                                    <TableCell
                                        class="text-center text-sm text-slate-600 dark:text-slate-400"
                                    >
                                        {{ records[0]?.dia?.[12] }}.{{
                                            monthName(records[0]?.mes)
                                        }}
                                        {{ records[0]?.anio?.[12] }} <br />{{
                                            formatTime(records[0]?.hora?.[12])
                                        }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
