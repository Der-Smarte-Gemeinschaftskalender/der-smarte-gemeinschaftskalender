<script setup lang="ts">
import { computed } from 'vue';
import { I18nT, useI18n } from 'vue-i18n';
import { user_types_keys } from '@/lib/const';

import Table from '@/components/KERN/Table.vue';
import Button from '@/components/KERN/Button.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';

import type { Column, USER_TYPES } from '@/types/General';
import { dsgApi } from '@/lib/dsgApi';

const { t } = useI18n();

// computed so the labels follow a language switch
const columns = computed<Array<Column>>(() => [
    {
        key: 'id',
        name: 'ID',
        numeric: true,
        align: 'center',
    },
    {
        key: 'mobilizon_name',
        name: t('app.admin.users.columns.name'),
        align: 'left',
    },
    {
        key: 'mobilizon_preferred_username',
        name: t('app.admin.users.columns.username'),
        align: 'left',
    },
    {
        key: 'email',
        name: t('app.admin.users.columns.email'),
        align: 'left',
    },
    {
        key: 'type',
        name: t('app.admin.users.columns.role'),
        format: (value: string) => user_types_keys.value[value as USER_TYPES] || value,
        align: 'left',
    },
    {
        key: 'is_active',
        name: t('app.admin.users.columns.status'),
        format: (value: string) => (value ? t('app.const.userIsActive.active') : t('app.const.userIsActive.inactive')),
        align: 'left',
    },
    {
        key: 'aktionen',
        name: '',
        align: 'right',
    },
]);

const exportUserEmails = async () => {
    try {
        const response = await dsgApi.get<Blob>('/admin/export/users', {
            responseType: 'blob',
        });

        const data = response.data;

        const url = window.URL.createObjectURL(new Blob([data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'user_emails.csv');
        document.body.appendChild(link);
        link.click();
        link.remove();
    } catch (error) {
        console.error('Es ist zu einem Fehler beim Exportieren der Nutzer-E-Mails gekommen.');
    }
};
</script>
<template>
    <div class="flex align-items-center justify-content-between mb-1 gap-2">
        <h1 class="kern-heading text-theme-primary">{{ t('app.admin.users.indexTitle') }}</h1>
        <RouterLink
            class=""
            :to="{ name: 'admin.user.create' }"
        >
            <Button
                :hide-text-on-mobile="true"
                icon-left="add"
                icon-size="lg"
            >
                {{ t('app.admin.users.createButton') }}
            </Button>
        </RouterLink>
    </div>
    <p class="mb-4">
        <b>{{ t('app.admin.shared.note') }}</b> <I18nT
            keypath="app.admin.users.indexHint"
            scope="global"
        >
            <template #link>
                <LinkToDocs
                    path="Terminverwaltung/Instanz/"
                    fragment="nutzer-innen-verwalten"
                />
            </template>
        </I18nT>
    </p>
    <div class="flex justify-content-end">
        <Button
            @click="exportUserEmails"
            icon-left="download"
            icon-size="lg"
            variant="secondary"
            class="mb-4"
        >
            {{ t('app.admin.users.exportEmails') }}
        </Button>
    </div>
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
                        {{ t('common.edit') }}
                    </Button>
                </RouterLink>
            </div>
        </template>
    </Table>
</template>
