<script setup lang="ts">
import { useRouter } from 'vue-router';
import { setOrganisationData } from '@/composables/OrganisationComposable';
import { requested_organisation_status } from '@/lib/const';
import { dsgApi } from '@/lib/dsgApi';

import Button from '@/components/KERN/Button.vue';
import Table from '@/components/KERN/Table.vue';

import type { Column } from '@/types/General';

import LinkToDocs from '@/components/LinkToDocs.vue';

const router = useRouter();

const columns: Array<Column> = [
    {
        key: 'id',
        name: 'ID',
        format: (_: any, row: any) => {
            return row?.parent?.id;
        },
        align: 'center',
    },
    {
        key: 'parent',
        name: 'Name',
        format: (value: any) => {
            return value?.name;
        },
        align: 'left',
    },
    {
        key: 'aktionen',
        name: '',
        align: 'right',
    },
];

const columnsRequestedOrganisations: Array<Column> = [
    {
        key: 'id',
        name: 'ID',
        align: 'center',
    },
    {
        key: 'requested_organisation_data',
        name: 'Name',
        format: (col: any) => col?.name,
        align: 'left',
    },
    {
        key: 'status',
        name: 'Status',
        format: (col: keyof typeof requested_organisation_status) => {
            return requested_organisation_status[col] || 'Unbekannt';
        },
        align: 'center',
    },
];

const rejectInvitation = async (membershipId: string) => {
    try {
        await dsgApi.post('organisations/rejectInvitation', {
            membership_id: membershipId,
        });

        router.go(0);
    } catch (error) {
        console.error(error);
    }
};
const acceptInvitation = async (membershipId: string) => {
    try {
        await dsgApi.post('organisations/acceptInvitation', {
            membership_id: membershipId,
        });

        await setOrganisationData(null, true);
        router.go(0);
    } catch (error) {
        console.error(error);
    }
};
</script>
<template>
    <div class="flex align-items-center justify-content-between gap-2 mb-1">
        <h1 class="kern-heading text-theme-primary">Meine Organisationen</h1>
        <RouterLink :to="{ name: 'app.myOrganisations.request' }">
            <Button
                title="Organisation erstellen"
                aria-label="Organisation erstellen"
                variant="primary"
                icon-left="add"
                icon-size="lg"
                class="mb-2"
                :hide-text-on-mobile="true"
            >
                Organisation erstellen
            </Button>
        </RouterLink>
    </div>
    <p class="mt-1 mb-6">
        <b>Hinweis:</b>
        Hier finden Sie alle Organisationen in denen Sie Mitglied sind und können diese bearbeiten. Weitere
        Informationen finden Sie im
        <LinkToDocs
            path="Terminverwaltung/Organisation/"
            fragment="organisationsadministration"
        />
        .
    </p>

    <Table
        :api="{
            url: '/organisations/myOrganisations',
        }"
        :columns="columns"
        class="organisations-table"
    >
        <template #aktionen="{ row }">
            <div
                class="flex justify-content-end flex-wrap gap-2"
                v-if="row.role === 'ADMINISTRATOR' && row?.parent"
            >
                <RouterLink
                    :to="{
                        name: 'app.organisation.edit',
                        params: { preferredUsername: row?.parent?.preferredUsername },
                    }"
                >
                    <Button
                        title="Bearbeiten"
                        aria-label="Bearbeiten"
                        icon-left="edit"
                        :hide-text-on-mobile="true"
                    >
                        Bearbeiten
                    </Button>
                </RouterLink>
                <RouterLink
                    :to="{
                        name: 'app.organisation.members',
                        params: { preferredUsername: row?.parent?.preferredUsername },
                    }"
                >
                    <Button
                        title="Mitglieder"
                        aria-label="Mitglieder"
                        icon-left="group"
                        :hide-text-on-mobile="true"
                    >
                        Mitglieder
                    </Button>
                </RouterLink>
                <RouterLink
                    :to="{
                        name: 'public.organisations.show',
                        params: { preferredUsername: row?.parent?.preferredUsername },
                    }"
                >
                    <Button
                        title="Ansehen"
                        aria-label="Ansehen"
                        icon-left="visibility"
                        :hide-text-on-mobile="true"
                    >
                        Ansehen
                    </Button>
                </RouterLink>
            </div>
            <div
                class="flex justify-content-end flex-wrap gap-2"
                v-else-if="row.role === 'INVITED'"
            >
                <Button
                    title="Annehmen"
                    aria-label="Annehmen"
                    variant="secondary"
                    @click="acceptInvitation(row.id)"
                    :hide-text-on-mobile="true"
                >
                    Einladung annehmen
                </Button>
                <Button
                    title="Ablehnen"
                    aria-label="Ablehnen"
                    variant="secondary"
                    @click="rejectInvitation(row.id)"
                    :hide-text-on-mobile="true"
                >
                    Einladung ablehnen
                </Button>
            </div>
        </template>
    </Table>
    <h2 class="kern-heading font-medium text-theme-primary mt-6">Angefragte Organisationen</h2>
    <Table
        :api="{
            url: '/organisations/requestedOrganisationsFromMe',
        }"
        :columns="columnsRequestedOrganisations"
        class="requested-organisations-table"
    />
</template>
