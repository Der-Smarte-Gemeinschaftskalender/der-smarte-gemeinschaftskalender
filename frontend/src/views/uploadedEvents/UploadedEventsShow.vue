<script setup lang="ts">
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { dsgApi } from '@/lib/dsgApi';
import { formatDateTime } from '@/lib/helper';
import { useEventShowDelete } from '@/composables/EventShowDeleteComposable';

import Loader from '@/components/KERN/cosmetics/Loader.vue';
import Alert from '@/components/KERN/Alert.vue';
import DescriptionList from '@/components/KERN/DescriptionList.vue';
import CreatedEventsTable from '@/components/CreatedEventsTable.vue';
import Button from '@/components/KERN/Button.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';

import type { UploadedEvent } from '@/types/events/UploadedEvents';
import type { User } from '@/types/User';


interface UploadedEventWithRelations extends UploadedEvent {
    id: number;
    created_at: string;
    filename: string;
    user: User;
    mobilizon_group_id: number;
    created_events: unknown[];
}

const error = ref(false);
const loading = ref(false);
const route = useRoute();
const router = useRouter();
const createdEvents = ref();
const uploadedEventListData = ref<{ name: string; value: unknown }[]>([]);
const uploadedEvent = ref<UploadedEventWithRelations>();

const { deleting, showDeleteDialog, alertMessage, handleDelete } = useEventShowDelete({
    getDeleteUrl: () => `/uploaded-events/${route.params.id}`,
    redirectRouteName: 'uploadedEvents.index',
    fallbackErrorText: 'Fehler beim Löschen der Kalenderdatei. Bitte versuchen Sie es später erneut oder kontaktieren Sie den Support.',
});

const loadUploadedEvent = async () => {
    try {
        loading.value = true;
        const { data } = await dsgApi.get<{uploadedEvent: UploadedEventWithRelations}>(`/uploaded-events/${route.params.id}`);

        if (!data || !data.uploadedEvent) {
            await router.push({ name: 'notFound' });
            return;
        }

        uploadedEvent.value = data.uploadedEvent;
        createdEvents.value = uploadedEvent.value.created_events;

        uploadedEventListData.value = [
            {
                name: 'ID',
                value: uploadedEvent.value.id,
            },
            {
                name: 'Name',
                value: uploadedEvent.value.filename,
            },
            {
                name: 'Terminanzahl',
                value: createdEvents.value.length,
            },
            {
                name: 'Angelegt von',
                value: `${uploadedEvent.value.user.mobilizon_name}`,
            },
            {
                name: 'Angelegt am',
                value: formatDateTime(uploadedEvent.value.created_at),
            },
        ];

    } catch (error) {
        console.error(error);
        await router.push({ name: 'notFound' });

    } finally {
        loading.value = false;
    }
};

loadUploadedEvent();
</script>

<template>
    <div class="flex align-items-center justify-content-between mb-1">
        <h1 class="kern-heading text-theme-primary">Kalenderdatei</h1>
        <Button
            variant="secondary"
            icon-left="delete"
            :disabled="deleting || loading"
            @click="showDeleteDialog = true"
        >
            {{ deleting ? 'Wird gelöscht...' : 'Löschen' }}
        </Button>
    </div>

    <Alert
        v-if="alertMessage"
        class="mb-4"
        :severity="alertMessage.severity"
        :title="alertMessage.title"
        :content="alertMessage.content"
    />
    <ConfirmDialog
        v-model="showDeleteDialog"
        title="Gesamte Kalenderdatei löschen"
        :description='`Sie sind dabei, folgende Kalenderdatei zu löschen: ${ uploadedEvent?.filename }.\n\n Alle darin enthaltenen Termine werden ebenfalls dauerhaft gelöscht. Eine Wiederherstellung ist nicht möglich.\n\nWenn bereits Werbung für die Veranstaltungen gemacht wurde, sollten Sie den Status der einzelnen Veranstaltung auf "Abgesagt" setzen, statt die Kalenderdatei zu löschen.`'
        confirm-text="Löschen"
        @confirm="handleDelete"
    />

    <div v-if="!loading">
        <div v-if="!error">
            <DescriptionList :data="uploadedEventListData" />

            <h3 class="kern-heading mt-8 text-theme-primary">Angelegte Termine</h3>
            <CreatedEventsTable 
                :data="createdEvents" 
                type="uploaded_event"
            />
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
