<script setup lang="ts">
import { user_types_keys } from '@/lib/const';

import Table from '@/components/KERN/Table.vue';
import Button from '@/components/KERN/Button.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';

import type { Column } from '@/types/General';

const columns: Array<Column> = [
    {
        key: 'id',
        name: 'ID',
        numeric: true,
        align: 'center',
    },
    {
        key: 'mobilizon_name',
        name: 'Name',
        align: 'left',
    },
    {
        key: 'mobilizon_preferred_username',
        name: 'Benutzername',
        align: 'left',
    },
    {
        key: 'email',
        name: 'E-Mail',
        align: 'left',
    },
    {
        key: 'type',
        name: 'Rolle (Instanz)',
        format: (value: string) => user_types_keys[value as keyof typeof user_types_keys] || value,
        align: 'left',
    },
    {
        key: 'is_active',
        name: 'Status',
        format: (value: string) => (value ? 'Aktiv' : 'Inaktiv'),
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
    <div class="flex align-items-center justify-content-between mb-1 gap-2">
        <h1 class="kern-heading text-theme-primary">Nutzer*innen der Instanz</h1>
        <RouterLink
            class=""
            :to="{ name: 'admin.user.create' }"
        >
            <Button
                :hide-text-on-mobile="true"
                icon-left="add"
                icon-size="lg"
            >
                Nutzer*in anlegen
            </Button>
        </RouterLink>
    </div>
    <p class="mb-4">
        <b>Hinweis:</b>
        Als Administrator*in der Instanz können Sie hier die Mitglieder aller Organisationen einsehen und bearbeiten.
        Über den Button Bearbeiten können sie Nutzer*innen entfernen. Weitere Informationen finden Sie im
        <LinkToDocs path="Terminverwaltung/Instanz/" />
        .
    </p>
    <Table
        :api="{
            url: '/admin',
        }"
        :page-size="20"
        :columns="columns"
        class="col-12"
    >
        <template #aktionen="{ row }">
            <div class="flex flex-wrap gap-2 justify-content-end">
                <RouterLink :to="{ name: 'admin.user.edit', params: { id: row.id } }">
                    <Button
                        icon-left="edit"
                        :hide-text-on-mobile="true"
                    >
                        Bearbeiten
                    </Button>
                </RouterLink>
            </div>
        </template>
    </Table>
</template>
