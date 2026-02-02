<script setup lang="ts">
import { ref } from 'vue';
import { findNextEvents } from '@/lib/mobilizonClient';

import EventCard from './EventCard.vue';
import Button from './KERN/Button.vue';

import type { IEvent } from '@/types/General';
import { landingPage } from '@/lib/instanceConfig';

const events = ref<IEvent[]>([]);
const totalEvents = ref<number>(0);

interface Props {
    maxEvents?: number;
    showLinkAllEventsButton?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    maxEvents: landingPage.numberOfUpcomingEvents ?? 3,
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
            <span>{{ totalEvents }} bevorstehende Veranstaltungen</span>
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
                <RouterLink :to="{ name: 'public.search' }">
                    <Button
                        icon-right="arrow-forward"
                        class="align-self-start"
                    >
                        Alle Veranstaltungen anzeigen
                    </Button>
                </RouterLink>
            </div>
        </div>
    </template>
    <template v-else>
        <p class="text-theme-secondary">Keine bevorstehenden Veranstaltungen gefunden.</p>
    </template>
</template>
