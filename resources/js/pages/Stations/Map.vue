<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { onMounted, ref } from 'vue';

import L from 'leaflet';
import markerIconUrl from 'leaflet/dist/images/marker-icon.png';
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png';
import 'leaflet/dist/leaflet.css';

L.Icon.Default.mergeOptions({
    iconUrl: markerIconUrl,
    shadowUrl: markerShadowUrl,
});

interface Station {
    id: string | null;
    name: string;
    lat: string | number;
    lon: string | number;
    provincia?: string | null;
}

interface Props {
    stations: Station[];
}

const props = defineProps<Props>();

const breadcrumbs = ref<BreadcrumbItemType[]>([
    { label: 'Home', href: '/' },
    { label: 'Stations Map' },
]);

const mapRef = ref<HTMLDivElement | null>(null);

function parseCoordinate(value: string | number): number {
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

onMounted(() => {
    if (!mapRef.value) return;

    const madridCenter: [number, number] = [40.4167, -3.70325];
    const map = L.map(mapRef.value).setView(madridCenter, 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 18,
    }).addTo(map);

    const markers: L.Layer[] = [];
    for (const s of props.stations) {
        const lat = parseCoordinate(s.lat);
        const lon = parseCoordinate(s.lon);
        if (Number.isNaN(lat) || Number.isNaN(lon)) continue;
        const marker = L.marker([lat, lon]).bindPopup(
            `<strong>${s.name}</strong>${s.provincia ? `<br/>${s.provincia}` : ''}`,
        );
        markers.push(marker);
        marker.addTo(map);
    }

    if (markers.length) {
        const group = L.featureGroup(markers);
        map.fitBounds(group.getBounds(), { padding: [20, 20] });
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <h1 class="text-xl font-semibold">AEMET Stations Map</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                Initial map with all available stations. Functionality will
                improve over time.
            </p>
            <div
                ref="mapRef"
                class="mt-4"
                style="height: 70vh; width: 100%"
            ></div>
        </div>
    </AppLayout>
</template>
