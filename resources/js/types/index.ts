// Centralized frontend types for charts and stations
export type DimensionKey =
    | 'temperature'
    | 'precipitation'
    | 'humidity'
    | 'wind';

export interface ChartDataPoint {
    time: number; // Unix timestamp (ms)
    temperature?: number | null;
    precipitation?: number | null;
    humidity?: number | null;
    wind?: number | null;
}

export const DimensionLabels: Record<DimensionKey, string> = {
    temperature: 'Temperatur',
    precipitation: 'Niederschlag',
    humidity: 'Luftfeuchtigkeit',
    wind: 'Wind',
};
