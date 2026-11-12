<script setup lang="ts">
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
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
const { t } = useI18n();
const createdEvents = ref();
const uploadedEvent = ref<UploadedEventWithRelations>();

// computed so the labels follow a language switch. Missing data gets a fallback instead of
// throwing, an error here would leave the page blank.
const uploadedEventListData = computed<{ name: string; value: unknown }[]>(() => {
    const event = uploadedEvent.value;
    if (!event) return [];

    return [
        {
            name: 'ID',
            value: event.id,
        },
        {
            name: t('app.events.columns.name'),
            value: event.filename,
        },
        {
            name: t('app.events.columns.eventCount'),
            value: createdEvents.value?.length,
        },
        {
            name: t('app.events.columns.addedBy'),
            value: event.user?.mobilizon_name || t('common.notSpecified'),
        },
        {
            name: t('app.events.columns.addedAt'),
            value: formatDateTime(event.created_at),
        },
    ];
});

const { deleting, showDeleteDialog, alertMessage, handleDelete } = useEventShowDelete({
    getDeleteUrl: () => `/uploaded-events/${route.params.id}`,
    redirectRouteName: 'uploadedEvents.index',
    fallbackErrorText: t('app.events.uploaded.show.deleteError'),
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
        <h1 class="kern-heading text-theme-primary">{{ t('app.events.uploaded.show.title') }}</h1>
        <Button
            variant="secondary"
            icon-left="delete"
            :disabled="deleting || loading"
            @click="showDeleteDialog = true"
        >
            {{ deleting ? t('app.shared.deleting') : t('common.delete') }}
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
        :title="t('app.events.uploaded.deleteTitle')"
        :description="t('app.events.uploaded.show.deleteDescription', { filename: `${uploadedEvent?.filename}` })"
        :confirm-text="t('common.delete')"
        @confirm="handleDelete"
    />

    <div v-if="!loading">
        <div v-if="!error">
            <DescriptionList :data="uploadedEventListData" />

            <h3 class="kern-heading mt-8 text-theme-primary">{{ t('app.events.createdEvents') }}</h3>
            <CreatedEventsTable 
                :data="createdEvents" 
                type="uploaded_event"
            />
        </div>
        <Alert
            v-else
            :title="$t('common.error')"
            type="error"
        >
            <p>{{ t('app.events.fileLoadError') }}</p>
            <p>{{ t('app.events.tryAgainLater') }}</p>
        </Alert>
    </div>
    <Loader
        v-else
        class="mt-4 mx-auto"
    />
</template>

<style scoped lang="scss"></style>
