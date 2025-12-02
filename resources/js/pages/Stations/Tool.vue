<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

import L from 'leaflet';
import markerRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import markerIconUrl from 'leaflet/dist/images/marker-icon.png';
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png';

// Configure default marker icons
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerRetinaUrl,
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
    selectedStations?: string[];
}

const props = withDefaults(defineProps<Props>(), {
    selectedStations: () => [],
});

const breadcrumbs = ref<BreadcrumbItemType[]>([
    { label: 'Home', href: '/' },
    { label: 'Stations Tool' },
]);

const welcomeRef = ref<HTMLElement | null>(null);
const mapSectionRef = ref<HTMLElement | null>(null);
const mapRef = ref<HTMLDivElement | null>(null);
const selectedIds = ref<Set<string>>(new Set(props.selectedStations));
let map: L.Map | null = null;
let markerCluster: L.MarkerClusterGroup | null = null;
let markers: L.Marker[] = [];
let scrollTimeout: number | null = null;

const selectedCount = computed(() => selectedIds.value.size);

// Current step; set from URL on mount to avoid SSR "window" issues
const currentStep = ref<'welcome' | 'map'>('welcome');

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

function initializeMap() {
    if (!mapRef.value || map) return;

    const madridCenter: [number, number] = [40.4167, -3.70325];
    map = L.map(mapRef.value).setView(madridCenter, 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 18,
    }).addTo(map);

    // Initialize marker cluster group
    // @ts-ignore - markerClusterGroup is added by the plugin
    markerCluster = L.markerClusterGroup({
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
    });
    map.addLayer(markerCluster);

    updateMarkers();

    // Ensure the map computes proper size when container becomes visible
    setTimeout(() => {
        map && map.invalidateSize(true);
    }, 0);
}

function updateMarkers() {
    if (!map || !markerCluster) return;

    // Remove all markers from cluster group
    markerCluster.clearLayers();
    markers = [];

    for (const s of props.stations) {
        const lat = parseCoordinate(s.lat);
        const lon = parseCoordinate(s.lon);
        if (Number.isNaN(lat) || Number.isNaN(lon)) continue;

        const isSelected = s.id && selectedIds.value.has(s.id);

        // Create custom icon for selected markers
        const icon = isSelected
            ? L.icon({
                  iconUrl: markerIconUrl,
                  iconRetinaUrl: markerRetinaUrl,
                  shadowUrl: markerShadowUrl,
                  iconSize: [25, 41],
                  iconAnchor: [12, 41],
                  className: 'selected-marker',
              })
            : new L.Icon.Default();

        const marker = L.marker([lat, lon], { icon }).bindPopup(
            `<strong>${s.name}</strong>${s.provincia ? `<br/>${s.provincia}` : ''}<br/><button class="text-blue-600 underline mt-2">${isSelected ? 'Abwählen' : 'Auswählen'}</button>`,
        );

        marker.on('click', () => {
            if (s.id) {
                toggleStation(s.id);
                updateMarkers(); // Refresh markers
            }
        });

        markers.push(marker);
        markerCluster.addLayer(marker);
    }

    if (markers.length) {
        const group = L.featureGroup(markers);
        map.fitBounds(group.getBounds(), { padding: [20, 20] });
    }
}

function updateUrlStep(step: 'welcome' | 'map') {
    const url = new URL(window.location.href);
    if (step === 'map') {
        url.searchParams.set('step', 'map');
    } else {
        url.searchParams.delete('step');
    }
    window.history.replaceState({}, '', url.toString());
    currentStep.value = step;
}

function handleScroll() {
    if (scrollTimeout) {
        clearTimeout(scrollTimeout);
    }

    scrollTimeout = window.setTimeout(() => {
        const mapSection = mapSectionRef.value;
        if (!mapSection) return;

        const rect = mapSection.getBoundingClientRect();
        // Consider the map visible if any part intersects the viewport
        const isMapVisible = rect.top < window.innerHeight && rect.bottom > 0;

        if (isMapVisible && currentStep.value === 'welcome') {
            updateUrlStep('map');
            if (!map) {
                initializeMap();
            }
        } else if (!isMapVisible && currentStep.value === 'map') {
            updateUrlStep('welcome');
        }
    }, 100);
}

function goToMap() {
    updateUrlStep('map');
    mapSectionRef.value?.scrollIntoView({ behavior: 'smooth' });
    if (!map) {
        setTimeout(() => {
            initializeMap();
            // Invalidate size after scroll to ensure tiles render
            setTimeout(() => {
                map && map.invalidateSize(true);
            }, 100);
        }, 500);
    }
}

function saveSelection() {
    router.post(
        '/save-selection',
        {
            stations: Array.from(selectedIds.value),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                // Show success message
            },
        },
    );
}

function toggleStation(stationId: string | null) {
    if (!stationId) return;

    if (selectedIds.value.has(stationId)) {
        selectedIds.value.delete(stationId);
    } else {
        selectedIds.value.add(stationId);
    }
}

onMounted(() => {
    // Determine step from URL safely in browser
    try {
        const params = new URLSearchParams(window.location.search);
        if (params.get('step') === 'map') {
            currentStep.value = 'map';
        }
    } catch (e) {
        // noop: keep default 'welcome' when URL not available (SSR)
    }

    // Add scroll listener
    window.addEventListener('scroll', handleScroll, { passive: true });
    // Invalidate on window resize
    window.addEventListener(
        'resize',
        () => {
            map && map.invalidateSize(true);
        },
        { passive: true },
    );

    // If step is map, scroll to it and initialize
    if (currentStep.value === 'map') {
        setTimeout(() => {
            mapSectionRef.value?.scrollIntoView({ behavior: 'auto' });
            initializeMap();
            setTimeout(() => {
                map && map.invalidateSize(true);
            }, 100);
        }, 100);
    }

    // Evaluate initial visibility without requiring a user scroll
    handleScroll();
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    if (scrollTimeout) {
        clearTimeout(scrollTimeout);
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col">
            <!-- Welcome Section -->
            <section
                ref="welcomeRef"
                class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-b from-slate-50 to-white p-8 dark:from-slate-950 dark:to-slate-900"
            >
                <div class="max-w-2xl text-center">
                    <h1 class="mb-6 text-5xl font-bold">
                        Willkommen zum Stations-Tool
                    </h1>
                    <p class="mb-8 text-lg text-slate-600 dark:text-slate-400">
                        Dieses Tool hilft Ihnen, Wetterstationen aus dem
                        AEMET-Netzwerk auszuwählen und relevante Variablen zu
                        speichern. Wählen Sie die Stationen aus, die für Ihre
                        Analyse wichtig sind.
                    </p>
                    <Button size="lg" @click="goToMap">
                        Weiter zur Karte
                    </Button>
                </div>
            </section>

            <!-- Map Section -->
            <section ref="mapSectionRef" class="min-h-screen p-8">
                <div class="mx-auto max-w-7xl">
                    <div class="mb-6">
                        <h2 class="mb-2 text-3xl font-bold">
                            Stationen auswählen
                        </h2>
                        <p class="text-slate-600 dark:text-slate-400">
                            Klicken Sie auf die Marker, um Stationen auszuwählen
                            oder abzuwählen.
                            <span
                                v-if="selectedCount > 0"
                                class="font-semibold text-blue-600 dark:text-blue-400"
                            >
                                {{ selectedCount }} Station{{
                                    selectedCount !== 1 ? 'en' : ''
                                }}
                                ausgewählt
                            </span>
                        </p>
                    </div>

                    <div
                        ref="mapRef"
                        class="rounded-lg border border-slate-200 shadow-lg dark:border-slate-800"
                        style="height: 70vh; width: 100%"
                    ></div>

                    <div class="mt-6 flex justify-end gap-4">
                        <Button
                            variant="outline"
                            @click="selectedIds.clear()"
                            :disabled="selectedCount === 0"
                        >
                            Auswahl zurücksetzen
                        </Button>
                        <Button
                            @click="saveSelection"
                            :disabled="selectedCount === 0"
                        >
                            {{ selectedCount }} Station{{
                                selectedCount !== 1 ? 'en' : ''
                            }}
                            speichern
                        </Button>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<style>
.selected-marker {
    filter: hue-rotate(120deg) saturate(2);
}
</style>
