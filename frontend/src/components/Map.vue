<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

import type { AddressForm } from '@/types/Mobilizon';
import { searchAddress } from '@/lib/mobilizonClient';

const props = defineProps<{
    searchValue: string | undefined;
    physicalAddress: AddressForm;
    zoom?: number;
}>();

const emit = defineEmits<{
    (e: 'created', map: L.Map): void;
    (e: 'removed'): void;
}>();

let debounceTimeout: ReturnType<typeof setTimeout> | null = null;

const mapElement = ref<HTMLElement | null>(null);
const map = ref<L.Map | undefined>(undefined);
const marker = ref<L.Marker | undefined>(undefined);
const observer = ref<IntersectionObserver | undefined>(undefined);

const lat = computed(() => {
    return props.physicalAddress?.geom ? parseFloat(props.physicalAddress.geom.split(';')[1]) : 54.7933;
});
const lon = computed(() => {
    return props.physicalAddress?.geom ? parseFloat(props.physicalAddress.geom.split(';')[0]) : 9.44;
});

const suggestions = ref<AddressForm[]>([]);
const isLoading = ref(false);

// custom icon
const myIcon = L.icon({
    iconUrl: '/marker-icon.png',
    shadowUrl: '/marker-shadow.png',
    iconAnchor: [13, 40],
});

function initMap(element: HTMLElement) {
    const map = L.map(element).setView([lat.value, lon.value], props.zoom ?? 13);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    return map;
}

// Cleanup
function removeMap() {
    if (map.value) {
        map.value.remove();
        map.value = undefined;
        emit('removed');
    }
}

async function geocodeAndUpdateMap(location: string) {
    try {
        const data: AddressForm[] = await searchAddress(location).then((response) => {
            // sanitize the response
            return response.map((item: AddressForm) => ({
                description: item.description?.trim() || '',
                street: item.street?.trim() || '',
                postalCode: item.postalCode?.trim() || '',
                locality: item.locality?.trim() || '',
                region: item.region?.trim() || '',
                country: item.country?.trim() || '',
                geom: item.geom?.trim() || '',
            }));
        });

        if (data.length > 0) {
            const bestMatch = data[0];

            const [lon, lat] = bestMatch.geom.split(';').map((coord) => parseFloat(coord.trim()));

            // Move map view to the new coordinates
            map.value!.setView([lat, lon], 13);

            // Remove old marker
            marker.value?.remove();

            // Add new marker
            marker.value = L.marker([lat, lon], { icon: myIcon }).addTo(map.value as L.Map);

            props.physicalAddress.street = bestMatch.street;
            props.physicalAddress.postalCode = bestMatch.postalCode?.trim();
            props.physicalAddress.locality = bestMatch.locality?.trim();
            props.physicalAddress.region = bestMatch.region?.trim();
            props.physicalAddress.country = bestMatch.country?.trim();
            props.physicalAddress.geom = bestMatch.geom?.trim();

            suggestions.value = data;
        }
    } catch (err) {
        suggestions.value = [];
        console.error('Geocoding error:', err);
    } finally {
        isLoading.value = false;
    }
}

onMounted(() => {
    if (!mapElement.value) return;

    observer.value = new IntersectionObserver(
        (entries) => {
            const entry = entries[0];

            if (entry.isIntersecting) {
                removeMap();
                map.value = initMap(mapElement.value!);
                map.value.invalidateSize();

                marker.value = L.marker([lat.value, lon.value], { icon: myIcon }).addTo(map.value as L.Map);
                map.value.setView([lat.value, lon.value], 13);
                emit('created', map.value as L.Map);
            } else removeMap();
        },
        { threshold: [0.5] }
    );

    observer.value.observe(mapElement.value);
});

onBeforeUnmount(() => {
    observer.value?.disconnect();
    removeMap();
});

watch(
    () => props.searchValue,
    async (newValue) => {
        if (!map.value || !newValue || newValue.length <= 2) return;

        suggestions.value = [];
        isLoading.value = true;
        if (debounceTimeout) clearTimeout(debounceTimeout);

        debounceTimeout = setTimeout(async () => {
            await geocodeAndUpdateMap(newValue);
            isLoading.value = false;
        }, 1000);
    }
);

defineExpose({
    isLoading,
    suggestions,
});
</script>

<template>
    <div
        ref="mapElement"
        class="leaflet-container"
    ></div>
</template>

<style scoped>
.leaflet-container {
    width: 100%;
    height: 350px; /* or full-height if needed */
}
</style>
