export type DataQueryType =
    | 'current-observations'
    | 'daily-values'
    | 'monthly-yearly-trends'
    | 'extreme-values'
    | 'climatological-normals';

export interface DataQueryOption {
    type: DataQueryType;
    title: string;
    description: string;
    icon: string;
    quickWin: boolean;
    requiresDateRange: boolean;
    estimatedTime: string;
}

export interface DateRangeSelection {
    startDate: string;
    endDate: string;
}

export interface YearRangeSelection {
    startYear: number;
    endYear: number;
}

export interface DataQueryRequest {
    type: DataQueryType;
    stationIds: string[];
    dateRange?: DateRangeSelection;
    yearRange?: YearRangeSelection;
    parameters?: string[];
}

export const DATA_QUERY_OPTIONS: DataQueryOption[] = [
    {
        type: 'current-observations',
        title: 'Aktuelle Beobachtungen (24h)',
        description:
            'Letzte 24 Stunden stündliche Beobachtungsdaten der ausgewählten Stationen',
        icon: 'clock',
        quickWin: true,
        requiresDateRange: false,
        estimatedTime: '< 1 Min',
    },
    {
        type: 'daily-values',
        title: 'Tageswerte',
        description:
            'Tägliche klimatologische Werte für einen benutzerdefinierten Zeitraum',
        icon: 'calendar',
        quickWin: true,
        requiresDateRange: true,
        estimatedTime: '1-2 Min',
    },
    {
        type: 'monthly-yearly-trends',
        title: 'Monatliche/Jährliche Trends',
        description:
            'Monatliche und jährliche Durchschnittswerte für Langzeitanalysen (z.B. Juni-Wetter über die Jahre)',
        icon: 'trending-up',
        quickWin: true,
        requiresDateRange: true,
        estimatedTime: '2-3 Min',
    },
    {
        type: 'extreme-values',
        title: 'Extremwerte',
        description:
            'Rekordwerte für Niederschlag, Temperatur und Wind an den ausgewählten Stationen',
        icon: 'alert-circle',
        quickWin: true,
        requiresDateRange: false,
        estimatedTime: '< 1 Min',
    },
    {
        type: 'climatological-normals',
        title: 'Klimanormale (1991-2020)',
        description:
            'Standardisierte klimatologische Normalwerte für den Zeitraum 1991-2020',
        icon: 'bar-chart',
        quickWin: true,
        requiresDateRange: false,
        estimatedTime: '< 1 Min',
    },
];
