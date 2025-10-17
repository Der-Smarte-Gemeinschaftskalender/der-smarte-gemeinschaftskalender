<script setup lang="ts">
import { ref, watch } from 'vue';

import Icon from '@/components/KERN/cosmetics/Icon.vue';
import InputSelect from '@/components/KERN/inputs/InputSelect.vue';
import InputText from '@/components/KERN/inputs/InputText.vue';

import type { AddressForm } from '@/types/Mobilizon';
import { extendedSearchAddress } from '@/lib/mobilizonClient';
import { buildSuggestions } from '@/composables/EventCreateFormComposable';

interface Props {
    label?: string;
    postalCodeOnly?: boolean;
    inputTextClass?: string;
}

const props = defineProps<Props>();

const address = defineModel<string | undefined>('address');
const radius = defineModel<number>('radius', { default: 5 });
const addressForm = ref<AddressForm | null>(null);
const coordinates = ref<{ lat: number; lon: number } | null>(null);
const geoHash = ref<string | null>(null);
const list = ref<string[]>([]);
let debounceTimeout: ReturnType<typeof setTimeout> | null = null;

enum Radius {
    '5 km' = 5,
    '10 km' = 10,
    '25 km' = 25,
    '50 km' = 50,
    '100 km' = 100,
    '250 km' = 250,
}

const radiusOptions = [
    { text: '5 km', value: Radius['5 km'] },
    { text: '10 km', value: Radius['10 km'] },
    { text: '25 km', value: Radius['25 km'] },
    { text: '50 km', value: Radius['50 km'] },
    { text: '100 km', value: Radius['100 km'] },
    { text: '250 km', value: Radius['250 km'] },
];

// Turn coordinates into a geo hash
const base32 = '0123456789bcdefghjkmnpqrstuvwxyz';
function encodeGeoHash(latitude: number, longitude: number, precision = 8): string {
    const latRange = [-90.0, 90.0];
    const lonRange = [-180.0, 180.0];
    let hash = '';
    let isEven = true;
    let bit = 0;
    let ch = 0;

    while (hash.length < precision) {
        let mid;

        if (isEven) {
            mid = (lonRange[0] + lonRange[1]) / 2;
            if (longitude > mid) {
                ch |= 1 << (4 - bit);
                lonRange[0] = mid;
            } else lonRange[1] = mid;
        } else {
            mid = (latRange[0] + latRange[1]) / 2;
            if (latitude > mid) {
                ch |= 1 << (4 - bit);
                latRange[0] = mid;
            } else latRange[1] = mid;
        }

        isEven = !isEven;

        if (bit < 4) bit++;
        else {
            hash += base32[ch];
            bit = 0;
            ch = 0;
        }
    }

    return hash;
}

watch(address, async (newAddress) => {
    if (!newAddress || newAddress.length < 3) {
        addressForm.value = null;
        coordinates.value = null;
        geoHash.value = null;
        return;
    }

    if (debounceTimeout) clearTimeout(debounceTimeout);

    debounceTimeout = setTimeout(async () => {
        try {
            const results = await extendedSearchAddress(newAddress, 'de', 'ADMINISTRATIVE');
            if (results.length > 0 && results[0].geom) {
                addressForm.value = results[0];
                const lat = results[0].geom.split(';')[1];
                const lon = results[0].geom.split(';')[0];

                coordinates.value = { lat: parseFloat(lat), lon: parseFloat(lon) };
                geoHash.value = encodeGeoHash(parseFloat(lat), parseFloat(lon));
            }

            list.value = buildSuggestions(results, props.postalCodeOnly);
        } catch (err) {
            console.error('Failed to geocode postal code:', err);
            addressForm.value = null;
        }
    }, 1000);
});

defineExpose({
    addressForm,
    coordinates,
    geoHash,
});
</script>

<template>
    <div class="flex flex-column">
        <p
            class="kern-fieldset__legend mb-3"
            v-if="label"
        >
            {{ label }}
        </p>
        <div class="flex align-items-center">
            <div class="icon-wrapper w-5rem h-3rem flex align-items-center justify-content-center bg-theme-primary">
                <Icon
                    name="location_on"
                    color="white"
                    class="flex align-items-center"
                    aria-hidden="true"
                />
            </div>

            <InputText
                class="address-input w-full"
                :class="props.inputTextClass"
                input-class="outline-none border-none"
                name="address"
                v-model="address"
                :list="list"
                placeholder="Postleitzahl"
                aria-label="Postleitzahl"
            />
            <InputSelect
                aria-label="Umkreis"
                class="radius-select kern-form-input__select-wrapper w-fit bg-theme-primary text-white outline-none radius-select"
                name="radius"
                :options="radiusOptions"
                v-model="radius"
            />
        </div>
    </div>
</template>

<style lang="scss" scoped>
.address-input {
    min-width: 6rem;
}

input[type='postal_code'] {
    border-radius: 0 !important;
}

input:hover {
    border-bottom: 2px !important;
}

.icon-wrapper {
    //left borders
    border-top-left-radius: 4px !important;
    border-bottom-left-radius: 4px !important;
}

.radius-select {
    border-top-right-radius: 4px !important;
    border-bottom-right-radius: 4px !important;
}

.radius-select:after {
    position: absolute;
    right: 1rem;
    top: 55%;
    content: ' ' !important;
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M7 10l5 5 5-5H7z'/%3E%3C/svg%3E");
    background-color: white !important;
}

.radius-select:hover {
    border-bottom: none !important;
}
</style>
