<script lang="ts" setup>
import Table from './KERN/Table.vue';
import Button from './KERN/Button.vue';
import { formatDateTime } from '@/lib/helper';
import ButtonLegend from './ButtonLegend.vue';

interface Props {
    data: Array<any> | undefined;
    type: 'series_event' | 'uploaded_event' | 'imported_event';
    showCreatedAt?: boolean;
}

const { showCreatedAt = false, type } = defineProps<Props>();

const columns = [
    { name: 'ID', key: 'id' },
    { name: 'Datum', key: 'start', format: (value: string) => formatDateTime(value) },
    { name: 'Uhrzeit', key: 'time' },
    { name: 'Dauer', key: 'duration' },
    { name: '', key: 'aktionen', slot: true },
];
if (showCreatedAt) {
    columns.splice(4, 0, { name: 'Erstellt am', key: 'created_at', format: (value: string) => formatDateTime(value) });
}

const getEventTypeLabel = (eventType: string): string => {
    const types = {
        series_event: 'Einzeltermin aus Terminserie',
        imported_event: 'Einzeltermin aus Kalenderintegration',
        uploaded_event: 'Einzeltermin aus Kalenderdatei',
    };

    return types[eventType as keyof typeof types] || 'Unbekannter Termin';
};

const createdEventsTableApi = {
    deleteUrl: '/created-events',
    deleteDialogTitle: `${getEventTypeLabel(type)} löschen`,
    deleteDialogDescription: (_: any) => `Sie sind dabei einen ${getEventTypeLabel(type)} zu löschen.\nEine Wiederherstellung ist nicht möglich.\n\n Wenn bereits Werbung für die Veranstaltung gemacht wurde, sollten Sie den Status der Veranstaltung auf "Abgesagt" setzen, statt die Veranstaltung zu löschen. Nach dem Löschen kann die Veranstaltung auch nicht mehr als Vorlage verwendet werden.`,
};
</script>
<template>
    <p v-if="!data?.length">Es wurden noch keine Veranstaltungen erstellt</p>
    <Table
        v-else
        :columns="columns"
        :data="data"
        :api="createdEventsTableApi"
    >
        <template #aktionen="{ row, deleteEntry }">
            <div class="flex justify-content-end flex-wrap gap-2 min-w-10rem">
                <RouterLink :to="{ name: 'createdEvent.edit', params: { id: row.id } }">
                    <Button
                        title="Bearbeiten"
                        aria-label="Bearbeiten"
                        icon-left="edit"
                        icon-size="sm"
                    />
                </RouterLink>
                <RouterLink
                    :to="{ name: 'materialGenerator.event', params: { uuid: row.mobilizon_uuid } }"
                >
                    <Button
                        title="Werbemittel generieren"
                        aria-label="Werbemittel generieren"
                        icon-left="wall_art"
                        icon-size="sm"
                    />
                </RouterLink>
                <RouterLink :to="{ name: 'public.event', params: { uuid: row.mobilizon_uuid } }">
                    <Button
                        title="Ansehen"
                        aria-label="Ansehen"
                        icon-left="open-in-new"
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
    <ButtonLegend 
        v-if="data?.length"
        class="mt-6" 
    />
</template>
