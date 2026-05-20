<script lang="ts" setup>
import { ref } from 'vue';
import { formatDateTime } from '@/lib/helper';
import { current_organisation } from '@/composables/OrganisationComposable';
import { useEventIndexAlerts } from '@/composables/EventIndexAlertsComposable';

import Table from '@/components/KERN/Table.vue';
import Button from '@/components/KERN/Button.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';

import type { CreatedEvent } from '@/types/events/CreatedEvents';
import ButtonLegend from '@/components/ButtonLegend.vue';

import type { Column } from '@/types/General';
import Alert from '@/components/KERN/Alert.vue';

type Row = { created_event: CreatedEvent };

const tableRef = ref<InstanceType<typeof Table> | null>(null);

const { alertMessage } = useEventIndexAlerts(tableRef, {
    deletedMessage: 'Einzeltermin erfolgreich gelöscht.',
    requestSentMessage:
        'Ihre Terminanfrage wurde gesendet. Sie werden per E-Mail benachrichtigt, sobald Ihre Anfrage bearbeitet wurde.',
});

const columns: Array<Column> = [
    {
        key: 'name',
        name: 'Titel',
        align: 'left',
    },
    {
        key: 'start',
        name: 'Startdatum',
        format: (value: string, row: Row) => formatDateTime(row?.created_event?.start),
        align: 'center',
    },
    {
        key: 'time',
        name: 'Uhrzeit',
        format: (value: string, row: Row) => row?.created_event?.time,
        align: 'center',
    },
    {
        key: 'duration',
        name: 'Dauer',
        format: (value: string, row: Row) => row?.created_event?.duration,
        align: 'center',
    },
    {
        key: 'aktionen',
        name: '',
        align: 'right',
    },
];

const singleTableApi = {
    url: '/single-events',
    deleteUrl: '/created-events',
    params: { mobilizon_group_id: current_organisation.value?.id },
    deleteDialogTitle: 'Veranstaltung löschen',
    deleteDialogDescription: (row: any) =>
        `Sie sind dabei, folgende Veranstaltung zu löschen: ${row?.name ?? ''}.\n Eine Wiederherstellung ist nicht möglich.\n\nWenn bereits Werbung für die Veranstaltung gemacht wurde, sollten Sie den Status der Veranstaltung auf "Abgesagt" setzen, statt die Veranstaltung zu löschen.`,
};
</script>
<template>
    <div class="flex align-items-center justify-content-between gap-2 mb-1">
        <h1 class="kern-heading text-theme-primary">Einzeltermine</h1>
        <RouterLink :to="{ name: 'singleEvents.create' }">
            <Button
                type="primary"
                icon-left="add"
                icon-size="lg"
                class="mb-2"
                title="Einzeltermin anlegen"
                :hide-text-on-mobile="true"
            >
                Einzeltermin anlegen
            </Button>
        </RouterLink>
    </div>
    <p class="mb-6">
        <b>Hinweis:</b>
        Weitere Informationen finden Sie im
        <LinkToDocs path="Terminverwaltung/Einzeltermine/" />.
    </p>
    <Alert
        class="mb-4"
        v-if="alertMessage"
        :severity="alertMessage.severity"
        :title="alertMessage.title"
        :content="alertMessage.content"
    />

    <Table
        ref="tableRef"
        :columns="columns"
        :api="singleTableApi"
    >
        <template #aktionen="{ row, deleteEntry }">
            <div class="flex justify-content-end flex-wrap gap-2 min-w-10rem">
                <RouterLink
                    v-if="row.id && row.created_event?.id"
                    class=""
                    :to="{ name: 'singleEvents.create', query: { templateEventId: row.id } }"
                >
                    <Button
                        title="Duplizieren"
                        aria-label="Duplizieren"
                        icon-left="content-copy"
                        icon-size="sm"
                    />
                </RouterLink>
                <RouterLink
                    v-if="row?.created_event?.id"
                    :to="{ name: 'createdEvent.edit', params: { id: row?.created_event?.id } }"
                >
                    <Button
                        title="Bearbeiten"
                        aria-label="Bearbeiten"
                        icon-left="edit"
                        icon-size="sm"
                    />
                </RouterLink>
                <template v-if="row?.created_event?.mobilizon_uuid">
                    <RouterLink
                        :to="{ name: 'materialGenerator.event', params: { uuid: row.created_event.mobilizon_uuid } }"
                    >
                        <Button
                            title="Werbemittel generieren"
                            aria-label="Werbemittel generieren"
                            icon-left="wall_art"
                            icon-size="sm"
                        />
                    </RouterLink>
                    <RouterLink :to="{ name: 'public.event', params: { uuid: row.created_event.mobilizon_uuid } }">
                        <Button
                            title="Zum Termin"
                            aria-label="Zum Termin"
                            icon-left="open-in-new"
                            icon-size="sm"
                        />
                    </RouterLink>
                </template>
                <Button
                    v-if="row?.created_event?.id"
                    title="Löschen"
                    aria-label="Löschen"
                    icon-left="delete"
                    icon-size="sm"
                    variant="secondary"
                    @click="deleteEntry({ ...row.created_event, name: row.name })"
                />
            </div>
        </template>
    </Table>
    <ButtonLegend class="mt-6" />
</template>
