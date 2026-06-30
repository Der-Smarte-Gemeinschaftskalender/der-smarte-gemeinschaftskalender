<script setup lang="ts">
import { ref, watch } from 'vue';

import InputText from '@/components/KERN/inputs/InputText.vue';
import Card from '@/components/KERN/Card.vue';
import Button from '@/components/KERN/Button.vue';
import { buildSuggestions } from '@/composables/EventCreateFormComposable';
import { getSuggestedAddresses } from '@/lib/dsgClient';
import { addressDefaults, type AddressForm } from '@/types/Mobilizon';

const rawAddress = defineModel<string>({ default: '' });
const address = defineModel<AddressForm>('address');

interface Props {
    name: string;
    label?: string;
    errors?: string;
    suggestions?: string[];
    mobilizonGroupId?: number | string | null;
}

const props = withDefaults(defineProps<Props>(), {
    suggestions: () => [],
});

// Manuelle Eingabe: nur die Beschreibung setzen, <Map> ergänzt die Geokoordinaten.
const onInput = () => {
    address.value = { ...addressDefaults, description: rawAddress.value };
};

interface AddressCard {
    label: string;
    display: string;
    address: AddressForm;
}

const cards = ref<AddressCard[]>([]);

const toCard = (label: string, partial: Partial<AddressForm>): AddressCard | null => {
    const full = { ...addressDefaults, ...partial } as AddressForm;
    const display = buildSuggestions([full])[0];

    if (!display || !full.geom) return null;

    return { label, display, address: { ...full, description: display } };
};

const loadCards = async (groupId?: number | string | null) => {
    cards.value = [];
    if (!groupId) return;

    const { organisationAddress, frequentAddresses } = await getSuggestedAddresses(groupId);

    const result: AddressCard[] = [];
    const seen = new Set<string>();

    const push = (card: AddressCard | null) => {
        if (!card || seen.has(card.display) || result.length >= 3) return;
        seen.add(card.display);
        result.push(card);
    };

    if (organisationAddress) push(toCard('Organisation', organisationAddress));
    frequentAddresses.forEach((entry) => push(toCard('Häufig genutzt', entry)));

    cards.value = result;
};

const selectCard = (card: AddressCard) => {
    rawAddress.value = card.display;
    address.value = { ...card.address };
};

watch(
    () => props.mobilizonGroupId,
    (groupId) => loadCards(groupId),
    { immediate: true }
);
</script>
<template>
    <div class="flex flex-column gap-3">
        <InputText
            v-model="rawAddress"
            :name="name"
            :label="label"
            :list="suggestions"
            :errors="errors"
            @input="onInput"
        />
        <div
            v-if="cards.length"
            class="flex flex-column md:flex-row gap-3"
        >
            <Card
                v-for="card in cards"
                :key="card.display"
                class="flex-1"
                body-class="gap-2"
            >
                <span class="kern-text kern-text--small">
                    {{ card.display }}
                    <br />
                    ({{ card.label }})
                </span>
                <Button
                    type="button"
                    variant="primary"
                    label="Adresse übernehmen"
                    icon-left="check"
                    @click="selectCard(card)"
                />
            </Card>
        </div>
    </div>
</template>
