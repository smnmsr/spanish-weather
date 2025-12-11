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

export interface ChartDataPoint {
    time: Date;
    temperature: number | null;
    precipitation: number | null;
    humidity: number | null;
    wind: number | null;
}

export interface QueryResults {
    queryType: string;
    selectedStationIds: string[];
    observations: any[];
    stations: Record<string, { name?: string; provincia?: string | null }>;
}
