<script setup lang="ts">
import { ref } from 'vue';
import { I18nT, useI18n } from 'vue-i18n';
import { formatDateTime } from '@/lib/helper';
import { current_organisation } from '@/composables/OrganisationComposable';
import { useEventIndexAlerts } from '@/composables/EventIndexAlertsComposable';

import Table from '@/components/KERN/Table.vue';
import Button from '@/components/KERN/Button.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';
import ButtonLegend from '@/components/ButtonLegend.vue';
import Alert from '@/components/KERN/Alert.vue';
import type { Column } from '@/types/General';

const { t } = useI18n();
const tableRef = ref<InstanceType<typeof Table> | null>(null);
const { alertMessage } = useEventIndexAlerts(tableRef, {
    deletedMessage: t('app.events.series.index.deleted'),
});

const intervallLabels: Record<string, string> = {
    weekly: t('common.weekly'),
    monthly: t('common.monthly'),
};

const columns: Array<Column> = [
    {
        key: 'name',
        name: t('app.events.columns.title'),
        align: 'left',
    },
    {
        key: 'start',
        name: t('app.events.columns.startDate'),
        format: (value: string) => formatDateTime(value),
        align: 'center',
    },
    {
        key: 'end',
        name: t('app.events.columns.endDate'),
        format: (value: string) => formatDateTime(value),
        align: 'center',
    },
    {
        key: 'time',
        name: t('app.events.columns.start'),
        align: 'center',
    },
    {
        key: 'duration',
        name: t('app.events.columns.duration'),
        format: (value: string) => value ?? t('app.shared.unknown'),
        align: 'center',
    },
    {
        key: 'intervall',
        name: t('app.events.columns.intervall'),
        format: (value: string) => intervallLabels[value.toLowerCase()] || value,
        align: 'left',
    },
    {
        key: 'aktionen',
        name: '',
        align: 'right',
    },
];

const seriesTableApi = {
    url: '/series-events',
    deleteUrl: '/series-events',
    params: { mobilizon_group_id: current_organisation.value?.id },
    deleteDialogTitle: t('app.events.series.deleteTitle'),
    deleteDialogDescription: (row: any) => t('app.events.series.deleteDescription', { name: row?.name ?? '' }),
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
        <h1 class="kern-heading text-theme-primary">{{ t('app.events.series.index.title') }}</h1>
        <RouterLink :to="{ name: 'seriesEvents.create' }">
            <Button
                type="primary"
                icon-left="add"
                icon-size="lg"
                :hide-text-on-mobile="true"
                class="mb-2"
                :title="t('app.events.series.index.create')"
            >
                {{ t('app.events.series.index.create') }}
            </Button>
        </RouterLink>
    </div>
    <p class="mb-6">
        <b>{{ $t('app.shared.hint') }}</b> <I18nT scope="global" keypath="app.events.series.index.hint">
            <template #link>
                <LinkToDocs path="Terminverwaltung/Serientermine/" />
            </template>
        </I18nT>
    </p>
    <Table
        ref="tableRef"
        :api="seriesTableApi"
        :columns="columns"
    >
        <template #aktionen="{ row, deleteEntry }">
            <div
                v-if="row.id"
                class="flex justify-content-end flex-wrap gap-2 min-w-10rem"
            >
                <RouterLink :to="{ name: 'seriesEvents.create', query: { templateEventId: row.id } }">
                    <Button
                        :title="t('app.shared.actions.duplicate')"
                        :aria-label="t('app.shared.actions.duplicate')"
                        icon-left="content-copy"
                        icon-size="sm"
                    />
                </RouterLink>
                <RouterLink :to="{ name: 'seriesEvents.show', params: { id: row.id } }">
                    <Button
                        :icon-left="'visibility'"
                        :title="t('app.shared.actions.view')"
                        :aria-label="t('app.shared.actions.view')"
                        icon-size="sm"
                    />
                </RouterLink>
                <Button
                    variant="secondary"
                    :title="t('common.delete')"
                    :aria-label="t('common.delete')"
                    icon-left="delete"
                    icon-size="sm"
                    @click="deleteEntry(row)"
                />
            </div>
        </template>
    </Table>
    <ButtonLegend class="mt-6" />
</template>
