<script lang="ts" setup>
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

import { dsgApi } from '@/lib/dsgApi';
import Loader from '@/components/KERN/cosmetics/Loader.vue';

const loadEventId = async () => {
    try {
        const { data } = await dsgApi.post(`/created-events/findEvent`, {
            uuid: route.params.uuid,
        });

        router.push({ name: 'createdEvent.edit', params: { id: data.id } });

    } catch (error) {
        console.error(error);
        router.push({ name: 'notFound' });
    }
};
loadEventId();
</script>
<template>
    <Loader />
</template>
