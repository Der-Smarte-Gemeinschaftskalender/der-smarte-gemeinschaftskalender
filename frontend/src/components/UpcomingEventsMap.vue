<script setup lang="ts">
import { ref } from 'vue';
import { findNextEvents } from '@/lib/mobilizonClient';
import EventsMap from './EventsMap.vue';
import { landingPage } from '@/lib/instanceConfig';
import dayjs from '@/lib/dayjs';

const upcomingEventsMap = ref([]);
const loadEvents = async () => {
    let endsOn: Date | null = null;
    if (!!landingPage.upcomingEventsMapBeforeDateFromNowInDays) {
        endsOn = dayjs().add(landingPage.upcomingEventsMapBeforeDateFromNowInDays, 'day').toDate();
    }
    const nextEvents = await findNextEvents(200, new Date(), endsOn);
    upcomingEventsMap.value = nextEvents.elements;
};
loadEvents();
</script>
<template>
    <EventsMap :events="upcomingEventsMap" />
</template>
