<script setup lang="ts">
import { ref } from 'vue';
import { I18nT, useI18n } from 'vue-i18n';
import { formatDateTime } from '@/lib/helper';
import { useEventIndexAlerts } from '@/composables/EventIndexAlertsComposable';
import Table from '@/components/KERN/Table.vue';
import Button from '@/components/KERN/Button.vue';
import { current_organisation } from '@/composables/OrganisationComposable';
import LinkToDocs from '@/components/LinkToDocs.vue';
import ButtonLegend from '@/components/ButtonLegend.vue';
import Alert from '@/components/KERN/Alert.vue';
import type { Column } from '@/types/General';

const { t } = useI18n();
const tableRef = ref<InstanceType<typeof Table> | null>(null);
const { alertMessage } = useEventIndexAlerts(tableRef, {
    deletedMessage: t('app.events.uploaded.index.deleted'),
});

const columns: Array<Column> = [
    {
        key: 'filename',
        name: t('app.events.columns.filename'),
        align: 'left',
    },
    {
        key: 'created_at',
        name: t('app.events.columns.uploadedAt'),
        format: (value: string) => formatDateTime(value),
        align: 'center',
    },
    {
        key: 'created_events_count',
        name: t('app.events.columns.eventCount'),
        align: 'center',
    },
    {
        key: 'aktionen',
        name: '',
        align: 'right',
    },
];

const uploadedTableApi = {
    url: '/uploaded-events',
    deleteUrl: '/uploaded-events',
    params: { mobilizon_group_id: current_organisation.value?.id },
    deleteDialogTitle: t('app.events.uploaded.deleteTitle'),
    deleteDialogDescription: (row: any) => t('app.events.uploaded.index.deleteDescription', { filename: row?.filename ?? '' }),
};
</script>
<template>
    <Alert
        v-if="alertMessage"
        class="mb-4"
        :severity="alertMessage.severity"
        :title="alertMessage.title"
        :content="alertMessage.content"
    />

    <div class="flex align-items-center justify-content-between gap-2 mb-1">
        <h1 class="kern-heading text-theme-primary">{{ t('app.events.uploaded.index.title') }}</h1>
        <RouterLink :to="{ name: 'uploadedEvents.create' }">
            <Button
                type="primary"
                icon-left="add"
                icon-size="lg"
                :hide-text-on-mobile="true"
                class="mb-2"
                :title="t('app.events.uploaded.index.upload')"
            >
                {{ t('app.events.uploaded.index.upload') }}
            </Button>
        </RouterLink>
    </div>
    <p class="mb-6">
        <b>{{ $t('app.shared.hint') }}</b> <I18nT scope="global" keypath="app.events.uploaded.index.hint">
            <template #link>
                <LinkToDocs path="Terminverwaltung/iCal-Dateien/" />
            </template>
        </I18nT>
    </p>
    <Table
        ref="tableRef"
        :api="uploadedTableApi"
        :columns="columns"
        :values-max-length="30"
    >
        <template #aktionen="{ row, deleteEntry }">
            <div
                v-if="row.id"
                class="flex justify-content-end flex-wrap gap-2 min-w-10rem"
            >
                <RouterLink :to="{ name: 'uploadedEvents.show', params: { id: row.id } }">
                    <Button
                        icon-left="visibility"
                        :title="t('app.shared.actions.view')"
                        :aria-label="t('app.shared.actions.view')"
                        icon-size="sm"
                    />
                </RouterLink>
                <Button
                    variant="secondary"
                    icon-left="delete"
                    :title="t('common.delete')"
                    :aria-label="t('common.delete')"
                    icon-size="sm"
                    @click="deleteEntry(row)"
                />
            </div>
        </template>
    </Table>
    <ButtonLegend class="mt-6" />
</template>

<style scoped lang="scss"></style>
