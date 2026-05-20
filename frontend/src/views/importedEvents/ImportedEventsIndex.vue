<script lang="ts" setup>
import { ref } from 'vue';
import { formatDateTime, formatInputTime } from '@/lib/helper';
import { useEventIndexAlerts } from '@/composables/EventIndexAlertsComposable';
import Table from '@/components/KERN/Table.vue';
import Button from '@/components/KERN/Button.vue';
import { current_organisation } from '@/composables/OrganisationComposable';
import LinkToDocs from '@/components/LinkToDocs.vue';
import ButtonLegend from '@/components/ButtonLegend.vue';
import Alert from '@/components/KERN/Alert.vue';
import type { Column } from '@/types/General';

const tableRef = ref<InstanceType<typeof Table> | null>(null);
const { alertMessage } = useEventIndexAlerts(tableRef, {
    deletedMessage: 'Kalenderintegration und alle zugehörigen Termine erfolgreich gelöscht.',
});

const columns: Array<Column> = [
    {
        key: 'url',
        name: 'iCal-URL',
        align: 'left',
    },
    {
        key: 'created_at',
        name: 'Erstellt am',
        format: (value: string) => formatDateTime(value),
        align: 'center',
    },
    {
        key: 'created_events_count',
        name: 'Terminanzahl',
        align: 'center',
    },
    {
        key: 'is_active',
        name: 'Status',
        format: (value: number) => (value ? 'Aktiv' : 'Inaktiv'),
        align: 'left',
    },
    {
        key: 'updated_at',
        name: 'Synchronisiert am',
        format: (value: string) => (value ? formatDateTime(value) : '-'),
        align: 'center',
    },
    {
        key: 'aktionen',
        name: '',
        align: 'right',
    },
];

const importedTableApi = {
    url: '/imported-events',
    deleteUrl: '/imported-events',
    params: { mobilizon_group_id: current_organisation.value?.id },
    deleteDialogTitle: 'Gesamte Kalenderintegration löschen',
    deleteDialogDescription: (row: any) =>
        `Sie sind dabei, folgende Kalenderintegration zu löschen:\n ${row?.url ?? ''}.\n\n Alle importierten Termine werden ebenfalls dauerhaft gelöscht. Eine Wiederherstellung ist nicht möglich.\n\nWenn bereits Werbung für die Veranstaltungen gemacht wurde, sollten Sie den Status der einzelnen Veranstaltung auf "Abgesagt" setzen, statt die Kalenderintegration zu löschen.`,
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

    <div class="flex align-items-end justify-content-between gap-2 mb-1">
        <h1 class="kern-heading text-theme-primary">Kalenderintegrationen</h1>
        <RouterLink :to="{ name: 'importedEvents.create' }">
            <Button
                type="primary"
                icon-left="add"
                icon-size="lg"
                :hide-text-on-mobile="true"
                class="mb-2"
                title="iCal-URL anbinden"
            >
                iCal-URL
            </Button>
        </RouterLink>
    </div>
    <p class="mb-6">
        <b>Hinweis:</b>
        Durch die Anbindung von iCal-URLs können externe Kalender (z. B. Outlook, Google Kalender, etc.) dauerhaft
        angebunden und automatisch synchronisiert werden. Weitere Informationen finden Sie im
        <LinkToDocs path="Terminverwaltung/Kalenderintegration/" />.
    </p>
    <Table
        ref="tableRef"
        :api="importedTableApi"
        :values-max-length="30"
        :columns="columns"
    >
        <template #aktionen="{ row, deleteEntry }">
            <div
                v-if="row.id"
                class="flex justify-content-end flex-wrap gap-2 min-w-10rem"
            >
                <RouterLink :to="{ name: 'importedEvents.show', params: { id: row.id } }">
                    <Button
                        icon-left="visibility"
                        title="Ansehen"
                        aria-label="Ansehen"
                        icon-size="sm"
                    />
                </RouterLink>
                <Button
                    variant="secondary"
                    icon-left="delete"
                    title="Löschen"
                    aria-label="Löschen"
                    icon-size="sm"
                    @click="deleteEntry(row)"
                />
            </div>
        </template>
    </Table>
    <ButtonLegend class="mt-6" />
</template>
