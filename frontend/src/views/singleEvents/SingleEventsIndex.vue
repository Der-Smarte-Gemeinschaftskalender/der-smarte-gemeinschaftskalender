<script lang="ts" setup>
import { ref } from 'vue';
import { I18nT, useI18n } from 'vue-i18n';
import { formatDateTime } from '@/lib/helper';
import { current_organisation } from '@/composables/OrganisationComposable';
import { useEventIndexAlerts } from '@/composables/EventIndexAlertsComposable';

import Table from '@/components/KERN/Table.vue';
import Button from '@/components/KERN/Button.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';

import type { CreatedEvent } from '@/types/events/CreatedEvents';
import ButtonLegend from '@/components/ButtonLegend.vue';

import type { Column } from '@/types/General';
import Alert from '@/components/KERN/Alert.vue';

type Row = { created_event: CreatedEvent };

const { t } = useI18n();
const tableRef = ref<InstanceType<typeof Table> | null>(null);

const { alertMessage } = useEventIndexAlerts(tableRef, {
    deletedMessage: t('app.events.singleEventDeleted'),
    requestSentMessage: t('app.events.requestSentMessage'),
});

const columns: Array<Column> = [
    {
        key: 'name',
        name: t('app.events.columns.title'),
        align: 'left',
    },
    {
        key: 'start',
        name: t('app.events.columns.startDate'),
        format: (value: string, row: Row) => formatDateTime(row?.created_event?.start),
        align: 'center',
    },
    {
        key: 'time',
        name: t('app.events.columns.time'),
        format: (value: string, row: Row) => row?.created_event?.time,
        align: 'center',
    },
    {
        key: 'duration',
        name: t('app.events.columns.duration'),
        format: (value: string, row: Row) => row?.created_event?.duration,
        align: 'center',
    },
    {
        key: 'aktionen',
        name: '',
        align: 'right',
    },
];

const singleTableApi = {
    url: '/single-events',
    deleteUrl: '/created-events',
    params: { mobilizon_group_id: current_organisation.value?.id },
    deleteDialogTitle: t('app.events.single.index.deleteTitle'),
    deleteDialogDescription: (row: any) => t('app.events.single.index.deleteDescription', { name: row?.name ?? '' }),
};
</script>
<template>
    <div class="flex align-items-center justify-content-between gap-2 mb-1">
        <h1 class="kern-heading text-theme-primary">{{ t('app.events.single.index.title') }}</h1>
        <RouterLink :to="{ name: 'singleEvents.create' }">
            <Button
                type="primary"
                icon-left="add"
                icon-size="lg"
                class="mb-2"
                :title="t('app.events.single.index.create')"
                :hide-text-on-mobile="true"
            >
                {{ t('app.events.single.index.create') }}
            </Button>
        </RouterLink>
    </div>
    <p class="mb-6">
        <b>{{ $t('app.shared.hint') }}</b> <I18nT scope="global" keypath="app.shared.moreInfoInDocs">
            <template #link>
                <LinkToDocs path="Terminverwaltung/Einzeltermine/" />
            </template>
        </I18nT>
    </p>
    <Alert
        class="mb-4"
        v-if="alertMessage"
        :severity="alertMessage.severity"
        :title="alertMessage.title"
        :content="alertMessage.content"
    />

    <Table
        ref="tableRef"
        :columns="columns"
        :api="singleTableApi"
    >
        <template #aktionen="{ row, deleteEntry }">
            <div class="flex justify-content-end flex-wrap gap-2 min-w-10rem">
                <RouterLink
                    v-if="row.id && row.created_event?.id"
                    class=""
                    :to="{ name: 'singleEvents.create', query: { templateEventId: row.id } }"
                >
                    <Button
                        :title="t('app.shared.actions.duplicate')"
                        :aria-label="t('app.shared.actions.duplicate')"
                        icon-left="content-copy"
                        icon-size="sm"
                    />
                </RouterLink>
                <RouterLink
                    v-if="row?.created_event?.id"
                    :to="{ name: 'createdEvent.edit', params: { id: row?.created_event?.id } }"
                >
                    <Button
                        :title="t('common.edit')"
                        :aria-label="t('common.edit')"
                        icon-left="edit"
                        icon-size="sm"
                    />
                </RouterLink>
                <template v-if="row?.created_event?.mobilizon_uuid">
                    <RouterLink
                        :to="{ name: 'materialGenerator.event', params: { uuid: row.created_event.mobilizon_uuid } }"
                    >
                        <Button
                            :title="t('app.shared.actions.generatePromoMaterials')"
                            :aria-label="t('app.shared.actions.generatePromoMaterials')"
                            icon-left="wall_art"
                            icon-size="sm"
                        />
                    </RouterLink>
                    <RouterLink :to="{ name: 'public.event', params: { uuid: row.created_event.mobilizon_uuid } }">
                        <Button
                            :title="t('app.shared.actions.toEvent')"
                            :aria-label="t('app.shared.actions.toEvent')"
                            icon-left="open-in-new"
                            icon-size="sm"
                        />
                    </RouterLink>
                </template>
                <Button
                    v-if="row?.created_event?.id"
                    :title="t('common.delete')"
                    :aria-label="t('common.delete')"
                    icon-left="delete"
                    icon-size="sm"
                    variant="secondary"
                    @click="deleteEntry({ ...row.created_event, name: row.name })"
                />
            </div>
        </template>
    </Table>
    <ButtonLegend class="mt-6" />
</template>
