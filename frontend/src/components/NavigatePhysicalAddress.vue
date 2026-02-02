<script setup lang="ts">
import type { AddressForm } from '@/types/Mobilizon';
import Button from './KERN/Button.vue';
import { copyContent } from '@/lib/helper';
import { showPhysicalAddressRouting } from '@/lib/instanceConfig';

interface Props {
    physicalAddress: AddressForm;
    coordinates?: [number, number] | null;
}

const { physicalAddress } = defineProps<Props>();
const fullAddress = !physicalAddress
    ? ''
    : `${physicalAddress?.street}  ${physicalAddress?.postalCode} ${physicalAddress?.locality}`;
const encodedAddress = encodeURIComponent(fullAddress);

const userAgent = navigator.userAgent.toLowerCase();
const isLinuxOrAndroid = userAgent.includes('linux') || userAgent.includes('android');
</script>
<template>
    <div
        v-if="!!physicalAddress"
        class="mt-2 flex flex-wrap gap-2"
    >
        <a
            v-if="showPhysicalAddressRouting.googleMaps"
            :href="`https://www.google.com/maps/search/?api=1&query=${encodedAddress}`"
            target="_blank"
        >
            <Button variant="secondary">Google Maps</Button>
        </a>
        <a
            v-if="showPhysicalAddressRouting.appleMaps"
            :href="`http://maps.apple.com/?q=${encodedAddress}`"
            target="_blank"
        >
            <Button variant="secondary">Apple Karten</Button>
        </a>
        <template v-if="showPhysicalAddressRouting.copyAdressButton">
            <textarea
                id="copyToClipboardInput"
                type="text"
                :value="fullAddress"
                style="display: none"
                aria-details="Kopieren"
            />
            <Button
                icon-left="content-copy"
                variant="secondary"
                @click="copyContent()"
            >
                Adresse kopieren
            </Button>
        </template>
        <a
            v-if="showPhysicalAddressRouting.openGeoCoordinates && !!coordinates && isLinuxOrAndroid"
            :href="`geo:${coordinates?.[1]},${coordinates?.[0]}`"
        >
            <Button
                icon-left="open-in-new"
                variant="secondary"
            >
                Geokoordinaten öffnen
            </Button>
        </a>
    </div>
</template>
