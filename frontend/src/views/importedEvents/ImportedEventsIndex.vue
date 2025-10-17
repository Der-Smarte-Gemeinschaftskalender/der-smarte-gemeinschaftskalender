<script lang="ts" setup>
import { formatDateTime, formatInputTime } from '@/lib/helper';
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
        format: (value: number) => (!!value ? 'Aktiv' : 'Inaktiv'),
        align: 'left',
    },
    {
        key: 'syncronized_at',
        name: 'Synchronisiert',
        format: (value: string) => (value ? formatInputTime(value) : ''),
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
        :api="{
            url: '/imported-events',
            params: { mobilizon_group_id: current_organisation?.id }
        }"
        :values-max-length="50"
        :columns="columns"
    >
        <template #aktionen="{ row }">
            <RouterLink
                v-if="row.id"
                :to="{ name: 'importedEvents.show', params: { id: row.id } }"
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
