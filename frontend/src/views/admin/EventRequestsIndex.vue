<script setup lang="ts">
import { computed, ref, onMounted, toDisplayString } from 'vue';
import { I18nT, useI18n } from 'vue-i18n';
import { dsgApi } from '@/lib/dsgApi';
import { formatDateTime } from '@/lib/helper';

import Table from '@/components/KERN/Table.vue';
import Button from '@/components/KERN/Button.vue';
import Loader from '@/components/KERN/cosmetics/Loader.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';
import Alert from '@/components/KERN/Alert.vue';

import type { Column } from '@/types/General';

const { t } = useI18n();

const loading = ref(false);
const showErrorMessage = ref(false);
const errorMessageContent = ref('');

const eventRequests = ref<any[]>([]);

const getEventTypeName = (requestableType: string) => {
    const typeMap: Record<string, string> = {
        CreatedEvent: t('app.admin.approval.types.singleEvent'),
        SingleEvent: t('app.admin.approval.types.singleEvent'),
        //'SeriesEvent': 'Serientermin',
        //'UploadedEvent': 'Kalenderdatei',
        //'ImportedEvent': 'Importierter Termin',
    };
    return typeMap[requestableType] || requestableType;
};

const getActionName = (actionType: string) => {
    const actionMap: Record<string, string> = {
        create: t('app.admin.approval.actions.create'),
        store: t('app.admin.approval.actions.create'),
        update: t('app.admin.approval.actions.update'),
        delete: t('app.admin.approval.actions.delete'),
        //'accept_upload': 'Hochladen',
    };
    return actionMap[actionType] || actionType;
};

// computed so the labels follow a language switch
const columns = computed<Array<Column>>(() => [
    {
        key: 'id',
        name: 'ID',
        align: 'center',
    },
    /**
    {
        key: 'requestable_type',
        name: 'Typ',
        format: (col: string) => `${getEventTypeName(col)}`,
        align: 'left',
    },
     */
    {
        key: 'request_type',
        name: t('app.admin.approval.columns.action'),
        format: (col: string) => `${getActionName(col)}`,
        align: 'left',
    },
    {
        key: 'payload',
        name: t('app.admin.approval.columns.title'),
        format: (col: any) => {
            return col?.name || t('app.admin.shared.unknown');
        },
        align: 'left',
    },
    {
        key: 'created_by',
        name: t('app.admin.approval.columns.createdBy'),
        align: 'left',
    },
    {
        key: 'created_at',
        name: t('app.admin.approval.columns.createdAt'),
        format: (col: string) => formatDateTime(col),
        align: 'left',
    },
    {
        key: 'aktionen',
        name: '',
        align: 'right',
    },
]);

const fetchEventRequests = async () => {
    loading.value = true;
    showErrorMessage.value = false;
    try {
        const response = await dsgApi.get<any[]>('/approval-requests/events', {
            params: { status: 'pending' },
        });

        eventRequests.value = response.data || [];
    } catch (error: any) {
        console.error('Error fetching event requests:', error);
        showErrorMessage.value = true;
        errorMessageContent.value =
            error?.response?.data?.error || t('app.admin.approval.loadRequestsError');
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchEventRequests();
});
</script>

<template>
    <div class="flex align-items-center justify-content-between mb-1">
        <h1 class="kern-heading text-theme-primary">{{ t('app.admin.eventRequests.indexTitle') }}</h1>
    </div>

    <Alert
        v-if="showErrorMessage"
        :title="t('common.error')"
        severity="danger"
        class="mb-4"
    >
        {{ errorMessageContent }}
    </Alert>

    <p class="mb-3">
        <span class="block">
            <b>{{ t('app.admin.shared.note') }}</b>
            {{ t('app.admin.eventRequests.indexHint') }}
        </span>
    </p>
    <p class="mb-6">
        <span>
            <I18nT
                keypath="app.admin.shared.moreInfo"
                scope="global"
            >
                <template #link>
                    <LinkToDocs
                        path="Terminverwaltung/Instanz/"
                        fragment="terminfreigabe-–-strict-mode"
                    />
                </template>
            </I18nT>
        </span>
    </p>

    <Loader
        v-if="loading"
        class="mt-6"
    />

    <div v-else>
        <Table
            v-if="eventRequests.length > 0"
            :columns="columns"
            :data="eventRequests"
            class="mb-2"
        >
            <template #created_by="{ row }">
                <div>{{ row.created_by.mobilizon_name }} ({{ row.created_by.mobilizon_preferred_username }})</div>
                <p class="text-sm text-theme-secondary">
                    {{ row.created_by.mobilizon_email }}
                </p>
                <p
                    v-if="!row.created_by.email_verified_at"
                    class="text-sm text-theme-secondary text-red-600"
                >
                    {{ t('app.admin.approval.emailNotVerified') }}
                </p>
            </template>
            <template #aktionen="{ row }">
                <RouterLink :to="{ name: 'admin.events.requests.show', params: { id: row.id } }">
                    <Button
                        :icon-left="'visibility'"
                        :title="t('app.admin.approval.view')"
                        :aria-label="t('app.admin.approval.view')"
                        :label="t('app.admin.approval.view')"
                    />
                </RouterLink>
            </template>
        </Table>
        <Alert
            v-else
            :title="t('app.admin.eventRequests.noneTitle')"
            type="info"
        >
            {{ t('app.admin.eventRequests.none') }}
        </Alert>
    </div>
</template>
<style scoped>
.approval-request-thumbnail {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid #e0e0e0;
}

.approval-request-preview-image {
    max-width: 100%;
    max-height: 300px;
    object-fit: contain;
    border-radius: 8px;
    border: 1px solid #ddd;
}
</style>
