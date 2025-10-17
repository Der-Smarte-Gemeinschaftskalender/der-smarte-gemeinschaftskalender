<script setup lang="ts">
import { ref } from 'vue';
import { useRoute } from 'vue-router';
import { dsgApi } from '@/lib/dsgApi';
import { formatDateTime } from '@/lib/helper';
import Loader from '@/components/KERN/cosmetics/Loader.vue';
import Alert from '@/components/KERN/Alert.vue';
import DescriptionList from '@/components/KERN/DescriptionList.vue';
import CreatedEventsTable from '@/components/CreatedEventsTable.vue';

const error = ref(false);
const loading = ref(false);
const route = useRoute();
const createdEvents = ref();
const uploadedEventListData = ref<{ name: string; value: any }[]>([]);

const loadUploadedEvent = async () => {
    try {
        loading.value = true;
        const { data } = await dsgApi.get(`/uploaded-events/${route.params.id}`);

        const uploadedEvent = data.uploadedEvent;
        createdEvents.value = uploadedEvent.created_events;

        uploadedEventListData.value = [
            {
                name: 'Name',
                value: uploadedEvent.filename,
            },
            {
                name: 'ID',
                value: uploadedEvent.id,
            },
            {
                name: 'Terminanzahl',
                value: createdEvents.value.length,
            },
            {
                name: 'Angelegt von',
                value: `${uploadedEvent.user.mobilizon_name}`,
            },
            {
                name: 'Angelegt am',
                value: formatDateTime(uploadedEvent.created_at),
            },
        ];
    } catch (e) {
        error.value = true;
        console.error(e);
    } finally {
        loading.value = false;
    }
};

loadUploadedEvent();
</script>

<template>
    <div class="mb-1">
        <h1 class="kern-heading text-theme-primary">Kalenderdatei</h1>
    </div>

    <div v-if="!loading">
        <div v-if="!error">
            <DescriptionList :data="uploadedEventListData" />

            <h3 class="kern-heading mt-8 text-theme-primary">Angelegte Termine</h3>
            <CreatedEventsTable :data="createdEvents" />
        </div>
        <Alert
            v-else
            title="Fehler"
            type="error"
        >
            <p>Die Datei konnte nicht geladen werden.</p>
            <p>Bitte versuchen Sie es später erneut.</p>
        </Alert>
    </div>
    <Loader
        v-else
        class="mt-4 mx-auto"
    />
</template>

<style scoped lang="scss"></style>
