<script lang="ts" setup>
import { normalizeStreet } from '@/lib/helper';

import Card from './KERN/Card.vue';
import Icon from './KERN/cosmetics/Icon.vue';

import type { IOrganisation } from '@/types/General';
import Badge from './KERN/cosmetics/Badge.vue';

interface Props {
    organisation: IOrganisation;
}

defineProps<Props>();
</script>
<template>
    <Card
        :title="organisation.name"
        :image-src="organisation?.avatar?.url || '/default_card.png'"
        :image-alt="''"
        :to="{
            name: 'public.organisations.show',
            params: { preferredUsername: organisation?.preferredUsername },
        }"
    >
        <Badge
            v-if="organisation?.is_featured"
            label="Empfohlen"
            variant="primary"
            size="small"
            class="mb-2 px-2"
            body-class="pl-0"
        >
            <div class="flex gap-1 align-items-center">
                <Icon
                    name="star_outline"
                    size="sm"
                />
                Empfohlen
            </div>
        </Badge>
        <div class="flex gap-2 align-items-center">
            <Icon 
                name="location_on"
                class="mb-auto"     
            />
            <div v-if="organisation?.physicalAddress">
                <div v-if="organisation?.physicalAddress?.street">
                    {{ normalizeStreet(organisation?.physicalAddress?.street) }},
                </div>
                <span v-if="organisation?.physicalAddress?.postalCode">
                    {{ organisation?.physicalAddress?.postalCode }} {{ organisation?.physicalAddress?.locality }}
                </span>
            </div>
            <div v-else>
                {{ $t('public.organisation.noAddressProvided') }}
            </div>
        </div>
    </Card>
</template>
