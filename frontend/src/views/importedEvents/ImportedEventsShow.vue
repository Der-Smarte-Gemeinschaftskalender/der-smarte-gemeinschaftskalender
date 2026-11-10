<script lang="ts" setup>
import { ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { dsgApi } from '@/lib/dsgApi';
import { formatDateTime } from '@/lib/helper';
import { stripHtml } from '@/lib/helper';
import { useEventShowDelete } from '@/composables/EventShowDeleteComposable';

import Loader from '@/components/KERN/cosmetics/Loader.vue';
import Alert from '@/components/KERN/Alert.vue';
import DescriptionList from '@/components/KERN/DescriptionList.vue';
import CreatedEventsTable from '@/components/CreatedEventsTable.vue';
import ChangeImportedEventStatus from '@/components/ChangeImportedEventStatus.vue';
import Button from '@/components/KERN/Button.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import type { ImportedEvent } from '@/types/events/ImportedEvents';

import type { User } from '@/types/User';

interface ImportedEventWithRelations extends ImportedEvent {
    id: number;
    url: string;
    is_active: boolean;
    updated_at: string;
    created_at: string;
    user: User;
    created_events: unknown[];
}

const error = ref(false);
const loading = ref(false);
const route = useRoute();
const router = useRouter();
const { t } = useI18n();
const createdEvents = ref();
const importedEvent = ref<ImportedEventWithRelations>();
const importedEventListData = ref<{ name: string; value: unknown }[]>([]);

const { deleting, showDeleteDialog, alertMessage, handleDelete } = useEventShowDelete({
    getDeleteUrl: () => `/imported-events/${route.params.id}`,
    redirectRouteName: 'importedEvents.index',
    fallbackErrorTitle: t('common.error'),
    fallbackErrorText: t('app.events.imported.show.deleteError'),
});

const loadImportedEvent = async () => {
    try {
        loading.value = true;
        const { data } = await dsgApi.get<{importedEvent: ImportedEventWithRelations}>(`/imported-events/${route.params.id}`);
        
        if (!data || !data.importedEvent) {
            await router.push({ name: 'notFound' });
            return;
        }

        importedEvent.value = data.importedEvent;
        createdEvents.value = importedEvent.value.created_events;

        importedEventListData.value = [
            {
                name: 'ID',
                value: importedEvent.value.id,
            },
            {
                name: t('app.events.columns.url'),
                value: importedEvent.value.url,
            },
            {
                name: t('app.events.columns.eventCount'),
                value: createdEvents.value?.length,
            },
            {
                name: t('app.events.columns.status'),
                value: importedEvent.value.is_active ? t('app.shared.active') : t('app.shared.inactive'),
            },
            {
                name: t('app.events.columns.addedBy'),
                value: `${importedEvent.value.user.mobilizon_name}`,
            },
            {
                name: t('app.events.columns.addedAt'),
                value: formatDateTime(importedEvent.value.created_at),
            },
            {
                name: t('app.events.columns.syncedAt'),
                value: formatDateTime(importedEvent.value.updated_at),
            },
        ];

    } catch (error) {
        console.error(error);
        await router.push({ name: 'notFound' });

    } finally {
        loading.value = false;
    }
};

loadImportedEvent();
</script>
<template>
    <ConfirmDialog
        v-model="showDeleteDialog"
        :title="t('app.events.imported.deleteTitle')"
        :description="t('app.events.imported.show.deleteDescription', { url: `${importedEvent?.url}` })"
        :confirm-text="t('common.delete')"
        @confirm="handleDelete"
    />
    <Alert
        v-if="alertMessage"
        class="mb-4"
        :severity="alertMessage.severity"
        :title="alertMessage.title"
        :content="alertMessage.content"
    />
    <div class="flex align-items-center justify-content-between mb-1">
        <h1 class="kern-heading text-theme-primary">{{ t('app.events.imported.title') }}</h1>
        <div class="flex gap-2">
            <template v-if="!!importedEvent">
                <ChangeImportedEventStatus
                    v-model:alertMessage="alertMessage"
                    :current-status="importedEvent.is_active ? 'active' : 'inactive'"
                    :imported-event-id="importedEvent.id"
                    @status-changed="loadImportedEvent()"
                />
                <Button
                    variant="secondary"
                    icon-left="delete"
                    :disabled="deleting"
                    @click="showDeleteDialog = true"
                >
                    {{ deleting ? t('app.shared.deleting') : t('common.delete') }}
                </Button>
            </template>
        </div>
    </div>

    <div v-if="!loading">
        <div v-if="!error">
            <DescriptionList :data="importedEventListData">
                <template #description>
                    <p
                        class="kern-text prose"
                        v-html="
                            stripHtml(importedEvent?.mobilizon_fields?.description)
                                ? importedEvent?.mobilizon_fields?.description
                                : t('app.events.imported.show.noDescription')
                        "
                    ></p>
                </template>
            </DescriptionList>

            <h3 class="kern-heading mt-8 text-theme-primary">{{ t('app.events.createdEvents') }}</h3>
            <CreatedEventsTable
                :data="createdEvents"
                :show-created-at="true"
                type="imported_event"
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
