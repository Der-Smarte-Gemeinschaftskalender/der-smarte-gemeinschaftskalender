<script lang="ts" setup>
import { ref } from 'vue';
import { useRoute } from 'vue-router';
import { dsgApi } from '@/lib/dsgApi';
import { formatDateTime } from '@/lib/helper';
import Loader from '@/components/KERN/cosmetics/Loader.vue';
import Alert from '@/components/KERN/Alert.vue';
import DescriptionList from '@/components/KERN/DescriptionList.vue';
import CreatedEventsTable from '@/components/CreatedEventsTable.vue';
import ChangeImportedEventStatus from '@/components/ChangeImportedEventStatus.vue';
import type { ImportedEvent } from '@/types/events/ImportedEvents';

const error = ref(false);
const loading = ref(false);
const route = useRoute();
const createdEvents = ref();
const importedEvent = ref<ImportedEvent>();

const importedEventListData = ref<{ name: string; value: any }[]>([]);

const loadImportedEvent = async () => {
    try {
        loading.value = true;
        const { data } = await dsgApi.get(`/imported-events/${route.params.id}`);
        // ImportedEvent schema validation can be added here if needed

        if (!data || !data.importedEvent) {
            throw new Error('Imported event data is missing');
        }

        importedEvent.value = data.importedEvent as ImportedEvent;
        createdEvents.value = importedEvent.value.created_events;

        importedEventListData.value = [
            {
                name: 'URL',
                value: importedEvent.value.url,
            },
            {
                name: 'Default Beschreibung',
                value: importedEvent.value?.mobilizon_fields?.description,
            },
            {
                name: 'ID',
                value: importedEvent.value.id,
            },
            {
                name: 'Terminanzahl',
                value: createdEvents.value?.length,
            },
            {
                name: 'Angelegt von',
                value: `${importedEvent.value.user.mobilizon_name}`,
            },
            {
                name: 'Status',
                value: !!importedEvent.value.is_active ? 'Aktiv' : 'Inaktiv',
            },
            {
                name: 'Synchronisiert',
                value: formatDateTime(importedEvent.value.syncronized_at),
            },
            {
                name: 'Angelegt am',
                value: formatDateTime(importedEvent.value.created_at),
            },
        ];
    } catch (e) {
        error.value = true;
        console.error(e);
    } finally {
        loading.value = false;
    }
};
loadImportedEvent();
</script>
<template>
    <div class="flex align-items-center justify-content-between mb-1">
        <h1 class="kern-heading text-theme-primary">Kalenderintegration (iCal-URL)</h1>
        <template v-if="!!importedEvent">
            <ChangeImportedEventStatus
                :current-status="importedEvent.is_active ? 'active' : 'inactive'"
                :imported-event-id="importedEvent.id"
                @statusChanged="loadImportedEvent()"
            />
        </template>
    </div>

    <div v-if="!loading">
        <div v-if="!error">
            <DescriptionList :data="importedEventListData" />

            <h3 class="kern-heading mt-8 text-theme-primary">Angelegte Termine</h3>
            <CreatedEventsTable
                :data="createdEvents"
                :show-created-at="true"
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
