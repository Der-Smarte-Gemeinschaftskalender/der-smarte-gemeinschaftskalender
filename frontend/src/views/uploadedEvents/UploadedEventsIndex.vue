<script setup lang="ts">
import { formatDateTime } from '@/lib/helper';
import Table from '@/components/KERN/Table.vue';
import Button from '@/components/KERN/Button.vue';
import { current_organisation } from '@/composables/OrganisationComposable';
import LinkToDocs from '@/components/LinkToDocs.vue';
import ButtonLegend from '@/components/ButtonLegend.vue';

const columns = [
    {
        key: 'id',
        name: 'ID',
        align: 'center',
    },
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
</script>
<template>
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
        :api="{
            url: '/uploaded-events',
            params: { mobilizon_group_id: current_organisation?.id },
        }"
        :columns="columns"
        :values-max-length="30"
    >
        <template #aktionen="{ row }">
            <RouterLink
                v-if="row.id"
                :to="{ name: 'uploadedEvents.show', params: { id: row.id } }"
            >
                <Button
                    icon-left="visibility"
                    title="Ansehen"
                    aria-label="Ansehen"
                />
            </RouterLink>
        </template>
    </Table>
    <ButtonLegend />
</template>

<style scoped lang="scss"></style>
