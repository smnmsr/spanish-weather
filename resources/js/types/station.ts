export interface Station {
    id: string | null;
    name: string;
    lat: string | number;
    lon: string | number;
    provincia?: string | null;
    altitude?: number | null;
}

export interface StationInfo {
    id: string;
    name: string;
    provincia?: string | null;
}

import type { DateRangeSelection, MonthYearRange } from '@/types/data-query';

export interface QueryResults {
    queryType: string;
    selectedStationIds: string[];
    observations: any[];
    stations: Record<string, { name?: string; provincia?: string | null }>;
    dateRange?: DateRangeSelection;
    monthYearRange?: MonthYearRange;
}
