<script setup lang="ts">
import { formatDateTime } from '@/lib/helper';
import { current_organisation } from '@/composables/OrganisationComposable';
import { intervall_keys } from '@/lib/const';

import Table from '@/components/KERN/Table.vue';
import Button from '@/components/KERN/Button.vue';
import Icon from '@/components/KERN/cosmetics/Icon.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';
import ButtonLegend from '@/components/ButtonLegend.vue';

const columns = [
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
</script>
<template>
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
        :api="{
            url: '/series-events',
            params: { mobilizon_group_id: current_organisation?.id },
        }"
        :columns="columns"
    >
        <template #aktionen="{ row }">
            <div class="flex justify-content-end flex-wrap gap-2 min-w-10rem">
                    <RouterLink
                        v-if="row.id"
                        :to="{ name: 'seriesEvents.create', query: { templateEventId: row.id } }"
                    >
                        <Button
                            title="Kopieren"
                            aria-label="Kopieren"
                            :icon-left="'content-copy'"
                        />
                    </RouterLink>
                    <RouterLink
                        v-if="row.id"
                        :to="{ name: 'seriesEvents.show', params: { id: row.id } }"
                    >
                        <Button
                            :icon-left="'visibility'"
                            title="Ansehen"
                            aria-label="Ansehen"
                        />

                  </RouterLink>
            </div>
        </template>
    </Table>
    <ButtonLegend />
</template>
