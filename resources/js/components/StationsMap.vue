<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import type { MarkerCluster } from 'leaflet';
import L from 'leaflet';
import type { Station } from '@/types/station';
import {
    configureDefaultMarkerIcons,
    createMarkerIcon,
    createClusterIcon,
    parseCoordinate,
} from '@/lib/leaflet-utils';

configureDefaultMarkerIcons();

interface Props {
    stations: Station[];
    /** Initial center coordinates [lat, lon] */
    center?: [number, number];
    /** Initial zoom level */
    zoom?: number;
    /** Max cluster radius in pixels */
    maxClusterRadius?: number;
    /** Height of the map container */
    height?: string;
    /** Allow scroll wheel zoom */
    scrollWheelZoom?: boolean;
    /** Enable selection mode */
    selectable?: boolean;
    /** IDs of selected stations (only used when selectable=true) */
    selectedStationIds?: Set<string>;
    /** Show coverage on hover for clusters */
    showCoverageOnHover?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    center: () => [40.4167, -3.70325], // Madrid
    zoom: 6,
    maxClusterRadius: 50,
    height: '70vh',
    scrollWheelZoom: false,
    selectable: false,
    selectedStationIds: () => new Set(),
    showCoverageOnHover: false,
});

const emit = defineEmits<{
    stationClick: [stationId: string];
    mapReady: [map: L.Map];
}>();

const mapRef = ref<HTMLDivElement | null>(null);
let map: L.Map | null = null;
let markerCluster: L.MarkerClusterGroup | null = null;
let markers: L.Marker[] = [];
let lightTileLayer: L.TileLayer | null = null;
let darkTileLayer: L.TileLayer | null = null;

const isDarkMode = ref(false);

const isSelected = computed(() => (stationId: string | null) => {
    if (!props.selectable || !stationId) return false;
    return props.selectedStationIds.has(stationId);
});

function updateTileLayer() {
    if (!map || !lightTileLayer || !darkTileLayer) return;

    if (isDarkMode.value) {
        if (map.hasLayer(lightTileLayer)) {
            map.removeLayer(lightTileLayer);
        }
        if (!map.hasLayer(darkTileLayer)) {
            map.addLayer(darkTileLayer);
        }
    } else {
        if (map.hasLayer(darkTileLayer)) {
            map.removeLayer(darkTileLayer);
        }
        if (!map.hasLayer(lightTileLayer)) {
            map.addLayer(lightTileLayer);
        }
    }
}

function checkDarkMode() {
    isDarkMode.value = window.matchMedia(
        '(prefers-color-scheme: dark)',
    ).matches;
    updateTileLayer();
}

function initializeMap() {
    if (!mapRef.value || map) return;

    map = L.map(mapRef.value, {
        scrollWheelZoom: props.scrollWheelZoom,
    }).setView(props.center, props.zoom);

    // Light mode tile layer
    lightTileLayer = L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 18,
        },
    );

    // Dark mode tile layer
    darkTileLayer = L.tileLayer(
        'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
        {
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
            maxZoom: 18,
        },
    );

    // Check initial dark mode state and add appropriate layer
    isDarkMode.value = window.matchMedia(
        '(prefers-color-scheme: dark)',
    ).matches;
    if (isDarkMode.value) {
        darkTileLayer.addTo(map);
    } else {
        lightTileLayer.addTo(map);
    }

    // Listen for color scheme changes
    const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
    darkModeQuery.addEventListener('change', checkDarkMode);

    markerCluster = L.markerClusterGroup({
        maxClusterRadius: props.maxClusterRadius,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: props.showCoverageOnHover,
        zoomToBoundsOnClick: true,
        iconCreateFunction: (cluster: MarkerCluster) =>
            createClusterIcon(cluster.getChildCount()),
    });

    map.addLayer(markerCluster);

    updateMarkers(true);

    emit('mapReady', map);

    // Ensure the map computes proper size when container becomes visible
    setTimeout(() => {
        void (map && map.invalidateSize(true));
    }, 0);
}

function updateMarkers(fitBounds = false) {
    if (!map || !markerCluster) return;

    markerCluster.clearLayers();
    markers = [];

    for (const s of props.stations) {
        const lat = parseCoordinate(s.lat);
        const lon = parseCoordinate(s.lon);
        if (Number.isNaN(lat) || Number.isNaN(lon)) continue;

        const selected = isSelected.value(s.id);
        const icon = createMarkerIcon(selected);

        const popupContent = props.selectable
            ? `<strong>${s.name}</strong>${s.provincia ? `<br/>${s.provincia}` : ''}<br/><button class="text-blue-600 underline mt-2">${selected ? 'Abwählen' : 'Auswählen'}</button>`
            : `<strong>${s.name}</strong>${s.provincia ? `<br/>${s.provincia}` : ''}`;

        const marker = L.marker([lat, lon], { icon }).bindPopup(popupContent);

        if (props.selectable) {
            marker.on('click', (e) => {
                L.DomEvent.stopPropagation(e);
                if (s.id) {
                    emit('stationClick', s.id);
                }
            });
        }

        markers.push(marker);
        markerCluster.addLayer(marker);
    }

    if (fitBounds && markers.length) {
        const group = L.featureGroup(markers);
        map.fitBounds(group.getBounds(), { padding: [20, 20] });
    }
}

function invalidateSize() {
    map?.invalidateSize(true);
}

function resetView() {
    if (!map || !markers.length) return;
    const group = L.featureGroup(markers);
    map.fitBounds(group.getBounds(), { padding: [20, 20] });
}

watch(
    () => props.selectedStationIds,
    () => {
        if (props.selectable) {
            updateMarkers(false);
        }
    },
    { deep: true },
);

onMounted(() => {
    initializeMap();
});

onUnmounted(() => {
    const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
    darkModeQuery.removeEventListener('change', checkDarkMode);
});

defineExpose({
    invalidateSize,
    updateMarkers,
    resetView,
});
</script>

<template>
    <div ref="mapRef" :style="{ height, width: '100%' }"></div>
</template>

<style>
.selected-marker {
    filter: hue-rotate(120deg) saturate(2);
}

.custom-cluster-icon {
    background: transparent !important;
    border: none !important;
}
</style>
