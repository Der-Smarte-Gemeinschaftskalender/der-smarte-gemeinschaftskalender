<script lang="ts" setup>
import { ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
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
const createdEvents = ref();
const importedEvent = ref<ImportedEventWithRelations>();
const importedEventListData = ref<{ name: string; value: unknown }[]>([]);

const { deleting, showDeleteDialog, alertMessage, handleDelete } = useEventShowDelete({
    getDeleteUrl: () => `/imported-events/${route.params.id}`,
    redirectRouteName: 'importedEvents.index',
    fallbackErrorTitle: 'Fehler',
    fallbackErrorText: 'Beim Löschen der Kalenderintegration ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.',
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
                name: 'URL',
                value: importedEvent.value.url,
            },
            {
                name: 'Terminanzahl',
                value: createdEvents.value?.length,
            },
            {
                name: 'Status',
                value: importedEvent.value.is_active ? 'Aktiv' : 'Inaktiv',
            },
            {
                name: 'Angelegt von',
                value: `${importedEvent.value.user.mobilizon_name}`,
            },
            {
                name: 'Angelegt am',
                value: formatDateTime(importedEvent.value.created_at),
            },
            {
                name: 'Synchronisiert am',
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
        title="Gesamte Kalenderintegration löschen"
        :description='`Sie sind dabei, folgende Kalenderintegration zu löschen: ${ importedEvent?.url }.\n\n Alle importierten Termine werden ebenfalls dauerhaft gelöscht. Eine Wiederherstellung ist nicht möglich.\n\nWenn bereits Werbung für die Veranstaltungen gemacht wurde, sollten Sie den Status der einzelnen Veranstaltung auf "Abgesagt" setzen, statt die Kalenderintegration zu löschen.`'
        confirm-text="Löschen"
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
        <h1 class="kern-heading text-theme-primary">Kalenderintegration (iCal-URL)</h1>
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
                    {{ deleting ? 'Wird gelöscht...' : 'Löschen' }}
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
                                : 'Es wurde keine Beschreibung angegeben.'
                        "
                    ></p>
                </template>
            </DescriptionList>

            <h3 class="kern-heading mt-8 text-theme-primary">Angelegte Termine</h3>
            <CreatedEventsTable
                :data="createdEvents"
                :show-created-at="true"
                type="imported_event"
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
