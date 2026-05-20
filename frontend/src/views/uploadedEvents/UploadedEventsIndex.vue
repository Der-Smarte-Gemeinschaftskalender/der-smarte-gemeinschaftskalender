<script setup lang="ts">
import { ref } from 'vue';
import { formatDateTime } from '@/lib/helper';
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
    deletedMessage: 'Kalenderdatei und alle darin enthaltenen Termine erfolgreich gelöscht.',
});

const columns: Array<Column> = [
    {
        key: 'filename',
        name: 'Dateiname',
        align: 'left',
    },
    {
        key: 'created_at',
        name: 'Hochgeladen am',
        format: (value: string) => formatDateTime(value),
        align: 'center',
    },
    {
        key: 'created_events_count',
        name: 'Terminanzahl',
        align: 'center',
    },
    {
        key: 'aktionen',
        name: '',
        align: 'right',
    },
];

const uploadedTableApi = {
    url: '/uploaded-events',
    deleteUrl: '/uploaded-events',
    params: { mobilizon_group_id: current_organisation.value?.id },
    deleteDialogTitle: 'Gesamte Kalenderdatei löschen',
    deleteDialogDescription: (row: any) =>
        `Sie sind dabei, die Kalenderdatei "${row?.filename ?? ''}" zu löschen.\n\n Alle darin enthaltenen Termine werden ebenfalls dauerhaft gelöscht. Eine Wiederherstellung ist nicht möglich.\n\nWenn bereits Werbung für die Veranstaltungen gemacht wurde, sollten Sie den Status der einzelnen Veranstaltung auf "Abgesagt" setzen, statt die Kalenderdatei zu löschen.`,
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
        <h1 class="kern-heading text-theme-primary">Kalenderdateien</h1>
        <RouterLink :to="{ name: 'uploadedEvents.create' }">
            <Button
                type="primary"
                icon-left="add"
                icon-size="lg"
                :hide-text-on-mobile="true"
                class="mb-2"
                title="Kalenderdatei (iCal) hochladen"
            >
                Kalenderdatei (iCal) hochladen
            </Button>
        </RouterLink>
    </div>
    <p class="mb-6">
        <b>Hinweis:</b>
        Die Kalenderdateien Funktion eignet sich, um mehrere Termine einmalig gesammelt aus einem externen
        Kalenderprogramm zu übernehmen. Weitere Informationen finden Sie im
        <LinkToDocs path="Terminverwaltung/iCal-Dateien/" />.
    </p>
    <Table
        ref="tableRef"
        :api="uploadedTableApi"
        :columns="columns"
        :values-max-length="30"
    >
        <template #aktionen="{ row, deleteEntry }">
            <div
                v-if="row.id"
                class="flex justify-content-end flex-wrap gap-2 min-w-10rem"
            >
                <RouterLink :to="{ name: 'uploadedEvents.show', params: { id: row.id } }">
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

<style scoped lang="scss"></style>
