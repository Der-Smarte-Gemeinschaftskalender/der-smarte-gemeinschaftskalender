<script lang="ts" setup>
import { ref } from 'vue';
import { I18nT, useI18n } from 'vue-i18n';
import { formatDateTime, formatInputTime } from '@/lib/helper';
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
    deletedMessage: t('app.events.imported.index.deleted'),
});

const columns: Array<Column> = [
    {
        key: 'url',
        name: t('app.events.columns.icalUrl'),
        align: 'left',
    },
    {
        key: 'created_at',
        name: t('app.events.columns.createdAt'),
        format: (value: string) => formatDateTime(value),
        align: 'center',
    },
    {
        key: 'created_events_count',
        name: t('app.events.columns.eventCount'),
        align: 'center',
    },
    {
        key: 'is_active',
        name: t('app.events.columns.status'),
        format: (value: number) => (value ? t('app.shared.active') : t('app.shared.inactive')),
        align: 'left',
    },
    {
        key: 'updated_at',
        name: t('app.events.columns.syncedAt'),
        format: (value: string) => (value ? formatDateTime(value) : '-'),
        align: 'center',
    },
    {
        key: 'aktionen',
        name: '',
        align: 'right',
    },
];

const importedTableApi = {
    url: '/imported-events',
    deleteUrl: '/imported-events',
    params: { mobilizon_group_id: current_organisation.value?.id },
    deleteDialogTitle: t('app.events.imported.deleteTitle'),
    deleteDialogDescription: (row: any) => t('app.events.imported.index.deleteDescription', { url: row?.url ?? '' }),
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

    <div class="flex align-items-end justify-content-between gap-2 mb-1">
        <h1 class="kern-heading text-theme-primary">{{ t('app.events.imported.index.title') }}</h1>
        <RouterLink :to="{ name: 'importedEvents.create' }">
            <Button
                type="primary"
                icon-left="add"
                icon-size="lg"
                :hide-text-on-mobile="true"
                class="mb-2"
                :title="t('app.events.imported.index.createTitle')"
            >
                {{ t('app.events.imported.index.create') }}
            </Button>
        </RouterLink>
    </div>
    <p class="mb-6">
        <b>{{ $t('app.shared.hint') }}</b> <I18nT scope="global" keypath="app.events.imported.hint">
            <template #link>
                <LinkToDocs path="Terminverwaltung/Kalenderintegration/" />
            </template>
        </I18nT>
    </p>
    <Table
        ref="tableRef"
        :api="importedTableApi"
        :values-max-length="30"
        :columns="columns"
    >
        <template #aktionen="{ row, deleteEntry }">
            <div
                v-if="row.id"
                class="flex justify-content-end flex-wrap gap-2 min-w-10rem"
            >
                <RouterLink :to="{ name: 'importedEvents.show', params: { id: row.id } }">
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
