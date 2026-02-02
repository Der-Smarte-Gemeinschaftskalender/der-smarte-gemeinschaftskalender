<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue';
import { useRouter } from 'vue-router';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import L from 'leaflet';
import 'leaflet.markercluster';

import { formatOnMonthDayTime } from '@/lib/helper';
import type { IEvent } from '@/types/General';
import { isMobile } from '@/lib/helper';
import { eventsMap } from '@/lib/instanceConfig';

const router = useRouter();

const props = withDefaults(
    defineProps<{
        events: IEvent[];
        zoomDesktop?: number;
        zoomMobile?: number;
        center?: { lat: number; lon: number };
    }>(),
    {
        zoomDesktop: eventsMap.initialZoomLevel + 1,
        zoomMobile: eventsMap.initialZoomLevel,
        center: undefined,
    }
);

const mapElement = ref<HTMLElement | null>(null);
const map = ref<L.Map | undefined>(undefined);
const markerClusterGroup = ref<L.MarkerClusterGroup | undefined>(undefined);
const isMapInitialized = ref(false);

// Responsive zoom based on screen width
const currentZoom = computed(() => (isMobile ? props.zoomMobile : props.zoomDesktop));

// Custom marker icon
const eventIcon = L.icon({
    iconUrl: '/marker-icon.png',
    shadowUrl: '/marker-shadow.png',
    iconSize: [25, 41],
    //iconAnchor: [12, 41],
    iconAnchor: [13, 40],
    popupAnchor: [1, -34],
    shadowSize: [41, 41],
});

// Filter events that have valid coordinates
const eventsWithCoordinates = computed(() => {
    return props.events.filter((event) => {
        if (!event.physicalAddress?.geom) return false;
        const parts = event.physicalAddress.geom.split(';');
        if (parts.length !== 2) return false;
        const lon = parseFloat(parts[0]);
        const lat = parseFloat(parts[1]);
        return !isNaN(lon) && !isNaN(lat);
    });
});

// Use provided center or default center
const mapCenter = computed(() => {
    if (props.center) {
        return props.center;
    }
    return eventsMap.defaultCenter;
});

function createPopupContent(event: IEvent): string {
    const dateStr = formatOnMonthDayTime(event.beginsOn);
    const organizer = event.attributedTo?.name || '';
    const eventUrl = router.resolve({ name: 'public.event', params: { uuid: event.uuid } }).href;

    return `
        <div class="event-popup">
            <strong class="event-popup__title">${event.title}</strong>
            <div class="event-popup__date">${dateStr}</div>
            ${organizer ? `<div class="event-popup__organizer">${organizer}</div>` : ''}
            <a href="${eventUrl}" class="event-popup__link">Ansehen →</a>
        </div>
    `;
}

function initMap(element: HTMLElement) {
    const mapInstance = L.map(element, {
        scrollWheelZoom: false,
        dragging: !isMobile,
        zoomSnap: 0.25,
    }).setView([mapCenter.value.lat, mapCenter.value.lon], currentZoom.value);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(mapInstance);

    return mapInstance;
}

function addMarkers() {
    if (!map.value) return;

    // Remove existing cluster group
    if (markerClusterGroup.value) {
        map.value.removeLayer(markerClusterGroup.value);
    }

    // Create new cluster group
    markerClusterGroup.value = L.markerClusterGroup({
        showCoverageOnHover: false,
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        disableClusteringAtZoom: 17,
    });

    // Add markers for each event with coordinates
    eventsWithCoordinates.value.forEach((event) => {
        const [lon, lat] = event.physicalAddress!.geom.split(';').map(parseFloat);

        const marker = L.marker([lat, lon], { icon: eventIcon });
        marker.bindPopup(createPopupContent(event), {
            maxWidth: 300,
            className: 'event-map-popup',
        });

        markerClusterGroup.value!.addLayer(marker);
    });

    map.value.addLayer(markerClusterGroup.value);

    // Only extend view if there are markers outside the current default bounds
    if (eventsWithCoordinates.value.length > 0) {
        const markerBounds = markerClusterGroup.value.getBounds();
        const currentBounds = map.value.getBounds();

        // Check if any marker is outside the current view
        if (markerBounds.isValid() && !currentBounds.contains(markerBounds)) {
            // Extend to include both the default view and all markers
            const extendedBounds = currentBounds.extend(markerBounds);
            map.value.fitBounds(extendedBounds, { padding: [50, 50] });
        }
    }
}

function removeMap() {
    if (map.value) {
        map.value.remove();
        map.value = undefined;
        markerClusterGroup.value = undefined;
        isMapInitialized.value = false;
    }
}

function initializeMap() {
    if (!mapElement.value || isMapInitialized.value) return;

    map.value = initMap(mapElement.value);
    isMapInitialized.value = true;

    // Need to wait a tick for the container to have proper dimensions
    setTimeout(() => {
        if (map.value) {
            map.value.invalidateSize();
            addMarkers();
        }
    }, 100);
}

onMounted(() => {
    // Initialize immediately if element exists
    if (mapElement.value) {
        initializeMap();
    }
});

onBeforeUnmount(() => {
    removeMap();
});

// Watch for changes in events and update markers
watch(
    () => props.events,
    () => {
        if (map.value) {
            addMarkers();
        }
    },
    { deep: true }
);
</script>

<template>
    <div
        ref="mapElement"
        class="events-map-container"
    ></div>
</template>

<style scoped>
.events-map-container {
    width: 100%;
    height: 700px;
    border-radius: 8px;
    overflow: hidden;
}
</style>

<style>
/* Global styles for popup (not scoped) */
.event-map-popup .leaflet-popup-content-wrapper {
    border-radius: 8px;
}

.event-popup {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 4px;
}

.event-popup__title {
    font-size: 14px;
    color: var(--kern-color-text-primary, #333);
    line-height: 1.3;
}

.event-popup__date {
    font-size: 12px;
    color: var(--kern-color-text-secondary, #666);
}

.event-popup__organizer {
    font-size: 12px;
    color: var(--kern-color-text-secondary, #666);
}

.event-popup__link {
    font-size: 12px;
    color: var(--kern-color-primary, #007bff);
    text-decoration: none;
    margin-top: 4px;
}

.event-popup__link:hover {
    text-decoration: underline;
}

/* Custom cluster styling */
.marker-cluster-small,
.marker-cluster-medium,
.marker-cluster-large {
    background-color: rgba(var(--kern-color-primary-rgb, 0, 123, 255), 0.6);
}

.marker-cluster-small div,
.marker-cluster-medium div,
.marker-cluster-large div {
    background-color: rgba(var(--kern-color-primary-rgb, 0, 123, 255), 0.8);
    color: white;
    font-weight: bold;
}
</style>
