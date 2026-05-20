<script lang="ts" setup>
import { formatOnMonthDayTime, getCardImageUrl } from '@/lib/helper';

import Card from './KERN/Card.vue';
import Icon from './KERN/cosmetics/Icon.vue';

import type { IEvent } from '@/types/General';

interface Props {
    event: IEvent;
}

defineProps<Props>();
</script>
<template>
    <Card
        :title="event.title"
        :image-src="getCardImageUrl(event)"
        :image-alt="''"
        :to="{ name: 'public.event', params: { uuid: event.uuid } }"
    >
        <div class="flex gap-2 align-items-start">
            <Icon name="schedule" />
            {{ formatOnMonthDayTime(event.beginsOn) }}
        </div>
        <div
            v-if="event?.attributedTo?.name"
            class="flex gap-2 align-items-start"
        >
            <Icon name="person" />
            {{ event?.attributedTo?.name }}
        </div>
    </Card>
</template>
