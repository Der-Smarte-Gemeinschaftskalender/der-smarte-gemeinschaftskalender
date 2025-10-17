<script setup lang="ts">
import { ref } from 'vue';
import { dsgApi } from '@/lib/dsgApi';
import { requested_organisation_status } from '@/lib/const';

import Table from '@/components/KERN/Table.vue';
import Button from '@/components/KERN/Button.vue';
import Loader from '@/components/KERN/cosmetics/Loader.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';

import type { Column } from '@/types/General';

const loading = ref(false);

const columns: Array<Column> = [
    {
        key: 'id',
        name: 'ID',
        align: 'center',
    },
    {
        key: 'requested_organisation_data',
        name: 'Organisation',
        format: (col: any) => {
            return `${col?.name} (${col?.preferredUsername})`.slice(0, 30) + '...';
        },
        align: 'left',
    },
    {
        key: 'status',
        name: 'Status',
        format: (col: keyof typeof requested_organisation_status) => {
            return requested_organisation_status[col] || 'Unbekannt';
        },
        align: 'left',
    },
    {
        key: 'aktionen',
        name: '',
        align: 'right',
    },
];
const changeStatus = async (id: number, status: string) => {
    loading.value = true;
    try {
        await dsgApi.post(`/organisations/changeOrganisationStatus/${id}`, { status });
    } catch (error) {
        console.error('Error changing status:', error);
    } finally {
        loading.value = false;
    }
};
</script>
<template>
    <div class="flex align-items-center justify-content-between mb-1">
        <h1 class="kern-heading text-theme-primary">Ausstehende Organisationsanfragen</h1>
    </div>
    <p class="mb-4">
        <span class="block mb-3">
            <b>Hinweis:</b>
            Als Administrator*in der Instanz können Sie hier Anfragen von Organisationen annehmen oder ablehnen.
            Organisationen mit ausstehenden Anfragen können keine Termine erstellen.
        </span>
        <span class="block mb-3">
            Die Verwaltung aller aktiver Organisationen Ihrer Instanz können Sie im Bereich Instanz verwalten
            (Seitenmenü) vornehmen.
        </span>
        <span class="block">
            Weitere Informationen finden Sie im
            <LinkToDocs path="" />
            .
        </span>
    </p>
    <Loader v-if="loading" />
    <template v-else>
        <Table
            :api="{
                url: '/organisations/requestedOrganisations',
            }"
            :columns="columns"
        >
            <template #aktionen="{ row }">
                <div class="flex flex-wrap gap-3 justify-content-end">
                    <Button
                        title="Annehmen"
                        aria-label="Annehmen"
                        @click="changeStatus(row.id, 'ACTIVE')"
                        icon-left="check"
                        :hide-text-on-mobile="true"
                    >
                        Annehmen
                    </Button>
                    <Button
                        title="Ablehnen"
                        aria-label="Ablehnen"
                        @click="changeStatus(row.id, 'REQUEST_DENIED')"
                        icon-left="close"
                        variant="secondary"
                        :hide-text-on-mobile="true"
                    >
                        Ablehnen
                    </Button>
                </div>
            </template>
        </Table>
    </template>
</template>
