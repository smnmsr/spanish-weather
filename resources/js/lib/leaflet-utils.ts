import L from 'leaflet';
import markerRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import markerIconUrl from 'leaflet/dist/images/marker-icon.png';
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png';

export function configureDefaultMarkerIcons() {
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: markerRetinaUrl,
        iconUrl: markerIconUrl,
        shadowUrl: markerShadowUrl,
    });
}

export function createMarkerIcon(selected: boolean = false): L.Icon {
    return L.icon({
        iconUrl: markerIconUrl,
        iconRetinaUrl: markerRetinaUrl,
        shadowUrl: markerShadowUrl,
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        tooltipAnchor: [16, -28],
        shadowSize: [41, 41],
        className: selected ? 'selected-marker' : '',
    });
}

export function createClusterIcon(count: number): L.DivIcon {
    return L.divIcon({
        html: `<div style="background-color: rgba(100, 116, 139, 0.6); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><span style="background-color: rgba(71, 85, 105, 0.8); color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px;">${count}</span></div>`,
        className: 'custom-cluster-icon',
        iconSize: L.point(40, 40),
    });
}

export function parseCoordinate(value: string | number): number {
    if (typeof value === 'number') return value;
    const num = Number(value);
    if (!Number.isNaN(num)) return num;
    const match = value.match(/(\d+)°(\d+)'(\d+)"([NSEW])/);
    if (match) {
        const degrees = Number(match[1]);
        const minutes = Number(match[2]);
        const seconds = Number(match[3]);
        const direction = match[4];
        let decimal = degrees + minutes / 60 + seconds / 3600;
        if (direction === 'S' || direction === 'W') decimal *= -1;
        return decimal;
    }
    return NaN;
}
