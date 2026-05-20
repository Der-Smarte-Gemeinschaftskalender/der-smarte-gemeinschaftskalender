<script setup lang="ts">
import { copyContent } from '@/lib/helper';
import { showPhysicalAddressRouting } from '@/lib/instanceConfig';

import Button from './KERN/Button.vue';

import type { AddressForm } from '@/types/Mobilizon';

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
        class="mt-2 flex flex-wrap gap-3 align-items-center shadow-sm py-2"
    >
        <a
            v-if="showPhysicalAddressRouting.googleMaps"
            :href="`https://www.google.com/maps/search/?api=1&query=${encodedAddress}`"
            target="_blank"
            class="text-lg cursor-pointer"
        >
            Google Maps
        </a>
        <span
            v-if="showPhysicalAddressRouting.googleMaps && showPhysicalAddressRouting.appleMaps"
            class="text-lg text-gray-500"
        >
            |
        </span>
        <a
            v-if="showPhysicalAddressRouting.appleMaps"
            :href="`http://maps.apple.com/?q=${encodedAddress}`"
            target="_blank"
            class="text-lg cursor-pointer"
        >
            {{ $t('public.navigation.appleMaps') }}
        </a>
        <template v-if="showPhysicalAddressRouting.openGeoCoordinates && !!coordinates && isLinuxOrAndroid">
            <span class="text-lg text-gray-500"> | </span>
            <a
                class="text-lg cursor-pointer"
                :href="`geo:${coordinates?.[1]},${coordinates?.[0]}`"
            >
                {{ $t('public.navigation.openGeoCoordinates') }}
            </a>
        </template>
        <template v-if="showPhysicalAddressRouting.copyAdressButton">
            <textarea
                id="copyToClipboardInput"
                type="text"
                :value="fullAddress"
                style="display: none"
                :aria-label="$t('public.navigation.copyAddressAria')"
            />
            <Button
                class="ml-2"
                icon-left="content-copy"
                variant="secondary"
                @click="copyContent()"
            >
                {{ $t('public.navigation.copyAddress') }}
            </Button>
        </template>
    </div>
</template>
