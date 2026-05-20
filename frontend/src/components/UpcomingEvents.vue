<script setup lang="ts">
import { ref } from 'vue';
import { findNextEvents } from '@/lib/mobilizonClient';
import { landingPage } from '@/lib/instanceConfig';

import EventCard from './EventCard.vue';

import type { IEvent } from '@/types/General';

const events = ref<IEvent[]>([]);
const totalEvents = ref<number>(0);

interface Props {
    maxEvents?: number;
    showLinkAllEventsButton?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    maxEvents: landingPage.value.numberOfUpcomingEvents ?? 3,
    showLinkAllEventsButton: true,
});

const loadEvents = async () => {
    const nextEvents = await findNextEvents(props.maxEvents, new Date());
    events.value = nextEvents.elements;
    totalEvents.value = nextEvents.total;
};
loadEvents();

defineExpose({
    events,
    totalEvents,
});
</script>
<template>
    <template v-if="events.length">
        <h3 class="kern-heading text-theme-primary mb-4">
            <span>{{ totalEvents }} {{ $t('public.event.upcomingEvents') }}</span>
        </h3>
        <div class="cards-template">
            <div
                v-for="event in events"
                :key="event.uuid"
            >
                <EventCard
                    :event="event"
                    class="h-full"
                />
            </div>
            <div
                v-if="showLinkAllEventsButton"
                class="w-full flex align-items-center justify-content-center"
            >
                <RouterLink
                    class="text-lg flex align-items-center gap-2"
                    :to="{ name: 'public.search' }"
                > 
                    {{ $t('public.event.showAllEvents') }} →
                </RouterLink>
            </div>
        </div>
    </template>
    <template v-else>
        <p class="text-theme-secondary">{{ $t('public.event.noUpcomingEvents') }}</p>
    </template>
</template>
