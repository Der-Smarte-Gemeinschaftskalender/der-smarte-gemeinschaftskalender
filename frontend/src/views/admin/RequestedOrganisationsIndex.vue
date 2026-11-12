<script setup lang="ts">
import { computed, ref } from 'vue';
import { I18nT, useI18n } from 'vue-i18n';
import { dsgApi } from '@/lib/dsgApi';
import { requested_organisation_status } from '@/lib/const';

import Table from '@/components/KERN/Table.vue';
import Button from '@/components/KERN/Button.vue';
import Loader from '@/components/KERN/cosmetics/Loader.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';
import Alert from '@/components/KERN/Alert.vue';

import type { Column } from '@/types/General';

const { t } = useI18n();

const loading = ref(false);
const loadingNewState = ref(false);
const showErrorMessage = ref(false);
const errorMessageContent = ref('');

// computed so the labels follow a language switch
const columns = computed<Array<Column>>(() => [
    {
        key: 'id',
        name: 'ID',
        align: 'center',
    },
    {
        key: 'requested_organisation_data',
        name: t('app.admin.eventRequests.fields.organisation'),
        format: (col: any) => {
            return `${col?.name} (${col?.preferredUsername})`.slice(0, 30) + '...';
        },
        align: 'left',
    },
    {
        key: 'status',
        name: t('app.admin.users.columns.status'),
        format: (col: keyof typeof requested_organisation_status.value) => {
            return requested_organisation_status.value[col] || t('app.admin.shared.unknown');
        },
        align: 'left',
    },
    {
        key: 'aktionen',
        name: '',
        align: 'right',
    },
]);
const changeStatus = async (id: number, status: string) => {
    loadingNewState.value = true;
    showErrorMessage.value = false;
    try {
        loading.value = true;
        await dsgApi.post(`/organisations/changeOrganisationStatus/${id}`, { status });
    } catch (error) {
        console.error('Error changing status:', error.response.data.error);
        showErrorMessage.value = true;
        errorMessageContent.value =
            error?.response?.data?.error || t('app.admin.requestedOrganisations.changeStatusError');
    } finally {
        loadingNewState.value = false;
        loading.value = false;
    }
};
</script>
<template>
    <div class="flex align-items-center justify-content-between mb-1">
        <h1 class="kern-heading text-theme-primary">{{ t('app.admin.organisationRequests.indexTitle') }}</h1>
    </div>
    <p class="mb-4">
        <span class="block mb-3">
            <b>{{ t('app.admin.shared.note') }}</b>
            {{ t('app.admin.requestedOrganisations.hint') }}
        </span>
        <span class="block mb-3">
            {{ t('app.admin.requestedOrganisations.hintManage') }}
        </span>
        <span class="block">
            <I18nT
                keypath="app.admin.shared.moreInfo"
                scope="global"
            >
                <template #link>
                    <LinkToDocs
                        path="Terminverwaltung/Instanz/"
                        fragment="organisationen-verwalten"
                    />
                </template>
            </I18nT>
        </span>
    </p>
    <Alert
        v-if="showErrorMessage"
        :title="t('common.error')"
        :content="errorMessageContent || t('app.admin.shared.genericError')"
        severity="danger"
    />
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
                        :title="t('app.admin.requestedOrganisations.accept')"
                        :aria-label="t('app.admin.requestedOrganisations.accept')"
                        @click="changeStatus(row.id, 'ACTIVE')"
                        icon-left="check"
                        :hide-text-on-mobile="true"
                        :disabled="loadingNewState"
                    >
                        {{ t('app.admin.requestedOrganisations.accept') }}
                    </Button>
                    <Button
                        :title="t('app.admin.requestedOrganisations.reject')"
                        :aria-label="t('app.admin.requestedOrganisations.reject')"
                        @click="changeStatus(row.id, 'REQUEST_DENIED')"
                        icon-left="close"
                        variant="secondary"
                        :hide-text-on-mobile="true"
                        :disabled="loadingNewState"
                    >
                        {{ t('app.admin.requestedOrganisations.reject') }}
                    </Button>
                </div>
            </template>
        </Table>
    </template>
</template>
