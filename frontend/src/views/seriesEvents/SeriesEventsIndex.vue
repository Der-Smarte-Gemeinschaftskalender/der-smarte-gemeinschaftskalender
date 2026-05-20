<script setup lang="ts">
import { ref } from 'vue';
import { formatDateTime } from '@/lib/helper';
import { current_organisation } from '@/composables/OrganisationComposable';
import { useEventIndexAlerts } from '@/composables/EventIndexAlertsComposable';
import { intervall_keys } from '@/lib/const';

import Table from '@/components/KERN/Table.vue';
import Button from '@/components/KERN/Button.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';
import ButtonLegend from '@/components/ButtonLegend.vue';
import Alert from '@/components/KERN/Alert.vue';
import type { Column } from '@/types/General';

const tableRef = ref<InstanceType<typeof Table> | null>(null);
const { alertMessage } = useEventIndexAlerts(tableRef, {
    deletedMessage: 'Serientermin und alle zugehörigen Einzeltermine erfolgreich gelöscht.',
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
        format: (value: string) => formatDateTime(value),
        align: 'center',
    },
    {
        key: 'end',
        name: 'Enddatum',
        format: (value: string) => formatDateTime(value),
        align: 'center',
    },
    {
        key: 'time',
        name: 'Start',
        align: 'center',
    },
    {
        key: 'duration',
        name: 'Dauer',
        format: (value: string) => value ?? 'unbekannt',
        align: 'center',
    },
    {
        key: 'intervall',
        name: 'Intervall',
        format: (value: string) => intervall_keys[value.toLowerCase() as keyof typeof intervall_keys] || value,
        align: 'left',
    },
    {
        key: 'aktionen',
        name: '',
        align: 'right',
    },
];

const seriesTableApi = {
    url: '/series-events',
    deleteUrl: '/series-events',
    params: { mobilizon_group_id: current_organisation.value?.id },
    deleteDialogTitle: 'Gesamten Serientermin löschen',
    deleteDialogDescription: (row: any) =>
        `Sie sind dabei, folgenden Serientermin zu löschen: ${row?.name ?? ''}.\n\n Alle darin enthaltenen Einzeltermine werden ebenfalls dauerhaft gelöscht. Eine Wiederherstellung ist nicht möglich.\n\nWenn bereits Werbung für die Veranstaltungen gemacht wurde, sollten Sie den Status der einzelnen Veranstaltung auf "Abgesagt" setzen, statt den Serientermin zu löschen.`,
};
</script>
<template>
    <Alert
        v-if="alertMessage"
        class="mb-4"
        :severity="alertMessage.severity"
        :title="alertMessage.title"
        :content="alertMessage.content"
    />

    <div class="flex align-items-center justify-content-between gap-2 mb-1">
        <h1 class="kern-heading text-theme-primary">Serientermine</h1>
        <RouterLink :to="{ name: 'seriesEvents.create' }">
            <Button
                type="primary"
                icon-left="add"
                icon-size="lg"
                :hide-text-on-mobile="true"
                class="mb-2"
                title="Serientermin anlegen"
            >
                Serientermin anlegen
            </Button>
        </RouterLink>
    </div>
    <p class="mb-6">
        <b>Hinweis:</b>
        Serientermine eignen sich für wiederkehrende Veranstaltungen mit gleichbleibender Uhrzeit und Dauer. Weitere
        Informationen finden Sie im
        <LinkToDocs path="Terminverwaltung/Serientermine/" />.
    </p>
    <Table
        ref="tableRef"
        :api="seriesTableApi"
        :columns="columns"
    >
        <template #aktionen="{ row, deleteEntry }">
            <div
                v-if="row.id"
                class="flex justify-content-end flex-wrap gap-2 min-w-10rem"
            >
                <RouterLink :to="{ name: 'seriesEvents.create', query: { templateEventId: row.id } }">
                    <Button
                        title="Duplizieren"
                        aria-label="Duplizieren"
                        icon-left="content-copy"
                        icon-size="sm"
                    />
                </RouterLink>
                <RouterLink :to="{ name: 'seriesEvents.show', params: { id: row.id } }">
                    <Button
                        :icon-left="'visibility'"
                        title="Ansehen"
                        aria-label="Ansehen"
                        icon-size="sm"
                    />
                </RouterLink>
                <Button
                    variant="secondary"
                    title="Löschen"
                    aria-label="Löschen"
                    icon-left="delete"
                    icon-size="sm"
                    @click="deleteEntry(row)"
                />
            </div>
        </template>
    </Table>
    <ButtonLegend class="mt-6" />
</template>
