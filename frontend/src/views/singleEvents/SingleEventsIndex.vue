<script lang="ts" setup>
import { formatDateTime } from '@/lib/helper';
import { current_organisation } from '@/composables/OrganisationComposable';

import Table from '@/components/KERN/Table.vue';
import Button from '@/components/KERN/Button.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';

import type { CreatedEvent } from '@/types/events/CreatedEvents';
import ButtonLegend from '@/components/ButtonLegend.vue';

import type { Column } from "@/types/General";


type Row = { created_event: CreatedEvent };


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
    <Table
        :api="{
            url: '/single-events',
            params: { mobilizon_group_id: current_organisation!.id }
        }"
        :columns="columns"
    >
        <template #aktionen="{ row }">
            <div class="flex justify-content-end flex-wrap gap-2 min-w-10rem">
                <RouterLink
                    v-if="row.id"
                    class=""
                    :to="{ name: 'singleEvents.create', query: { templateEventId: row.id } }"
                >
                    <Button
                        title="Duplizieren"
                        aria-label="Duplizieren"
                        icon-left="content-copy"
                    >
                    </Button>
                </RouterLink>
                <RouterLink
                    v-if="row?.created_event?.id"
                    :to="{ name: 'createdEvent.edit', params: { id: row?.created_event?.id } }"
                >
                    <Button
                        title="Bearbeiten"
                        aria-label="Bearbeiten"
                        icon-left="edit"
                    />

                </RouterLink>
                <template v-if="!!row?.created_event?.mobilizon_uuid">
                    <RouterLink
                        :to="{ name: 'materialGenerator.event', params: { uuid: row.created_event.mobilizon_uuid } }"
                    >
                        <Button
                            title="Werbemittel generieren"
                            aria-label="Werbemittel generieren"
                            icon-left="wall_art"
                        />
                    </RouterLink>
                    <RouterLink :to="{ name: 'public.event', params: { uuid: row.created_event.mobilizon_uuid } }">
                        <Button
                            title="Zum Termin"
                            aria-label="Zum Termin"
                            icon-left="open-in-new"
                        />
                    </RouterLink>
                </template>
                <span v-else></span>
            </div>
        </template>
    </Table>
    <ButtonLegend />
</template>
