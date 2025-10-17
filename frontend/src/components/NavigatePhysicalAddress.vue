<script setup lang="ts">
import type { AddressForm } from '@/types/Mobilizon';
import Button from './KERN/Button.vue';

interface Props {
    physicalAddress: AddressForm;
    coordinates?: [number, number] | null;
}

const { physicalAddress } = defineProps<Props>();
const fullAddress = !physicalAddress
    ? ''
    : `${physicalAddress?.street}  ${physicalAddress?.postalCode} ${physicalAddress?.locality}`;
</script>
<template>
    <div
        v-if="!!physicalAddress"
        class="mt-2 flex flex-wrap gap-2"
    >
        <a
            :href="`https://www.google.com/maps/search/?q=${ fullAddress }`"
            target="_blank"
        >
            <Button variant="secondary">Google Maps</Button>
        </a>
        <a
            :href="`http://maps.apple.com/?q=${ fullAddress }`"
            target="_blank"
        >
            <Button variant="secondary">Apple Karten</Button>
        </a>
        <a
            v-if="!!coordinates"
            :href="`geo:${ coordinates?.[1] },${ coordinates?.[0] }`"
        >
            <Button variant="secondary">Geokoordinaten öffnen</Button>
        </a>
    </div>
</template>
