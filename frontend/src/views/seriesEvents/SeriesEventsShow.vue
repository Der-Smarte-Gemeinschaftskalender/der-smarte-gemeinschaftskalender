<script setup lang="ts">
import { ref } from 'vue';
import { dsgApi } from '@/lib/dsgApi';
import { useRoute, useRouter } from 'vue-router';
import { intervall_keys } from '@/lib/const';
import { formatDateTime } from '@/lib/helper';
import DescriptionList from '@/components/KERN/DescriptionList.vue';
import CreatedEventsTable from '@/components/CreatedEventsTable.vue';
import Loader from '@/components/KERN/cosmetics/Loader.vue';

import type { SeriesEvent } from '@/types/events/SeriesEvents.ts';
import type { User } from '@/types/User.ts';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const seriesEvent = ref<SeriesEvent & { user: User; created_events: any[] }>();
const descriptionListData = ref<Array<any>>([]);

const loadSeriesEvent = async () => {
    try {
        loading.value = true;
        const { data } = await dsgApi.get(`/series-events/${route.params.id}`);
        seriesEvent.value = data;

        descriptionListData.value = [
            {
                name: 'ID',
                value: seriesEvent.value!.id,
            },
            {
                name: 'Titel',
                value: seriesEvent.value!.name,
            },
            {
                name: 'Startdatum',
                value: formatDateTime(seriesEvent.value!.start),
            },
            {
                name: 'Enddatum',
                value: formatDateTime(seriesEvent.value!.end),
            },
            {
                name: 'Intervall',
                value:
                    intervall_keys[seriesEvent.value!.intervall.toLowerCase() as keyof typeof intervall_keys] ||
                    seriesEvent.value!.intervall,
            },
            {
                name: 'Erstellt von',
                value: `${seriesEvent.value!.user.mobilizon_name}`,
            },
        ];
    } catch (error) {
        console.error(error);
        await router.push({ name: 'notFound' });
    } finally {
        loading.value = false;
    }
};

loadSeriesEvent();
</script>
<template>
    <div class="mb-1">
        <h1 class="kern-heading text-theme-primary">Serien Veranstaltungen</h1>
    </div>
    <template v-if="!loading">
        <DescriptionList :data="descriptionListData" />
        <h3 class="kern-heading mt-8 text-theme-primary">Angelegte Termine</h3>
        <CreatedEventsTable :data="seriesEvent?.created_events" />
    </template>
    <Loader v-else />
</template>
