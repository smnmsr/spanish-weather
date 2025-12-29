// Centralized frontend types for charts and stations

export type BreadcrumbItemType = {
    title: string;
    href?: string;
};

export type DimensionKey =
    | 'temperature'
    | 'precipitation'
    | 'humidity'
    | 'wind'
    | 'windDirection'
    | 'pressure'
    | 'sunshine'
    | 'clearDays'
    | 'overcastDays'
    | 'rainyDays';

export interface ChartDataPoint {
    time: number; // Unix timestamp (ms)
    temperature?: number | null;
    temperatureMax?: number | null;
    temperatureMin?: number | null;
    precipitation?: number | null;
    humidity?: number | null;
    humidityMax?: number | null;
    humidityMin?: number | null;
    wind?: number | null;
    windGust?: number | null;
    windDirection?: number | null;
    pressure?: number | null;
    pressureMin?: number | null;
    pressureMax?: number | null;
    sunshine?: number | null;
    clearDays?: number | null;
    overcastDays?: number | null;
    rainyDays?: number | null;
}

export interface Municipality {
    id: string;
    nombre: string; // Municipality name
    latitud_dec?: string; // Decimal latitude
    longitud_dec?: string; // Decimal longitude
    provincia?: string; // Province name
    altitud?: string; // Altitude
    cpro?: string; // Province code
    cmun?: string; // Municipality code
}

export const DimensionLabels: Record<DimensionKey, string> = {
    temperature: 'Temperatur',
    precipitation: 'Niederschlag',
    humidity: 'Luftfeuchtigkeit',
    wind: 'Wind',
    windDirection: 'Windrichtung',
    pressure: 'Luftdruck',
    sunshine: 'Sonnenschein',
    clearDays: 'Klare Tage',
    overcastDays: 'Bedeckte Tage',
    rainyDays: 'Regentage',
};
