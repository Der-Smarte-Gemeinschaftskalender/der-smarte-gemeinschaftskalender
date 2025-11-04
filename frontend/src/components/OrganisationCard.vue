<script lang="ts" setup>
import { normalizeStreet } from "@/lib/helper";

import Card from './KERN/Card.vue';
import Button from './KERN/Button.vue';
import Icon from './KERN/cosmetics/Icon.vue';

import type { IOrganisation } from "@/types/General";

interface Props {
    organisation: IOrganisation
}

defineProps<Props>();
</script>
<template>
    <Card
        :title="organisation.name"
        :image-src="organisation?.avatar?.url || '/default_card.png'"
        :to="{
            name: 'public.organisations.show',
            params: { preferredUsername: organisation?.preferredUsername }
        }"
    >
        <div
            class="flex gap-2 align-items-start"
            v-if="organisation?.physicalAddress"
        >
            <Icon name="location_on" />
            <div>
                <div v-if="organisation?.physicalAddress?.street">
                    {{ normalizeStreet(organisation?.physicalAddress?.street) }},
                </div>
                <div v-if="organisation?.physicalAddress?.postalCode">
                    {{ organisation?.physicalAddress?.postalCode }} {{ organisation?.physicalAddress?.locality }}
                </div>

            </div>
        </div>
        <template #footer>
            <RouterLink
                v-if="organisation.id"
                :to="{
                    name: 'public.organisations.show',
                    params: { preferredUsername: organisation.preferredUsername },
                }"
            >
                <Button
                    title="Organisation ansehen"
                    aria-label="Organisation ansehen"
                    icon-left="visibility"
                >
                    Ansehen
                </Button>
            </RouterLink>
        </template>
    </Card>
</template>
