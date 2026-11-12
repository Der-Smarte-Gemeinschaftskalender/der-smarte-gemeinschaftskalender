<script lang="ts" setup>
import { computed } from 'vue';
import Table from './KERN/Table.vue';
import Button from './KERN/Button.vue';
import { useI18n } from 'vue-i18n';
import { formatDateTime } from '@/lib/helper';
import { getEventTypeLabel } from '@/types/events/CreatedEvents';
import ButtonLegend from './ButtonLegend.vue';

interface Props {
    data: Array<any> | undefined;
    type: 'series_event' | 'uploaded_event' | 'imported_event';
    showCreatedAt?: boolean;
}

const { showCreatedAt = false, type } = defineProps<Props>();
const { t } = useI18n();

// computed so the labels follow a language switch
const columns = computed(() => [
    { name: 'ID', key: 'id' },
    { name: t('app.events.columns.date'), key: 'start', format: (value: string) => formatDateTime(value) },
    { name: t('app.events.columns.time'), key: 'time' },
    { name: t('app.events.columns.duration'), key: 'duration' },
    ...(showCreatedAt
        ? [{ name: t('app.events.columns.createdAt'), key: 'created_at', format: (value: string) => formatDateTime(value) }]
        : []),
    { name: '', key: 'aktionen', slot: true },
]);

const createdEventsTableApi = computed(() => ({
    deleteUrl: '/created-events',
    deleteDialogTitle: t('app.shared.deleteCreatedEvent.title', { type: getEventTypeLabel(type) }),
    deleteDialogDescription: (_: any) => t('app.shared.deleteCreatedEvent.description', { type: getEventTypeLabel(type) }),
}));
</script>
<template>
    <p v-if="!data?.length">{{ t('app.shared.createdEventsTable.empty') }}</p>
    <Table
        v-else
        :columns="columns"
        :data="data"
        :api="createdEventsTableApi"
    >
        <template #aktionen="{ row, deleteEntry }">
            <div class="flex justify-content-end flex-wrap gap-2 min-w-10rem">
                <RouterLink :to="{ name: 'createdEvent.edit', params: { id: row.id } }">
                    <Button
                        :title="t('common.edit')"
                        :aria-label="t('common.edit')"
                        icon-left="edit"
                        icon-size="sm"
                    />
                </RouterLink>
                <RouterLink
                    :to="{ name: 'materialGenerator.event', params: { uuid: row.mobilizon_uuid } }"
                >
                    <Button
                        :title="t('app.shared.actions.generatePromoMaterials')"
                        :aria-label="t('app.shared.actions.generatePromoMaterials')"
                        icon-left="wall_art"
                        icon-size="sm"
                    />
                </RouterLink>
                <RouterLink :to="{ name: 'public.event', params: { uuid: row.mobilizon_uuid } }">
                    <Button
                        :title="t('app.shared.actions.view')"
                        :aria-label="t('app.shared.actions.view')"
                        icon-left="open-in-new"
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
    <ButtonLegend 
        v-if="data?.length"
        class="mt-6" 
    />
</template>
