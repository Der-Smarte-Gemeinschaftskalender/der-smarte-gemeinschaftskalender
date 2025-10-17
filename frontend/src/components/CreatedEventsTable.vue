<script lang="ts" setup>
import Table from './KERN/Table.vue';
import Button from './KERN/Button.vue';
import { formatDateTime } from '@/lib/helper';
import Icon from './KERN/cosmetics/Icon.vue';

interface Props {
    data: Array<any> | undefined;
    showCreatedAt?: boolean;
}

const { showCreatedAt = false } = defineProps<Props>();

let columns = [
    { name: 'ID', key: 'id' },
    { name: 'Datum', key: 'start', format: (value: string) => formatDateTime(value) },
    { name: 'Uhrzeit', key: 'time' },
    { name: 'Dauer', key: 'duration' },
    { name: '', key: 'aktionen', slot: true },
];
if (showCreatedAt) {
    columns.splice(4, 0, { name: 'Erstellt am', key: 'created_at', format: (value: string) => formatDateTime(value) });
}
</script>
<template>
    <p v-if="!data?.length">Es wurden noch keine Veranstaltungen erstellt</p>
    <Table
        v-else
        :columns="columns"
        :data="data"
    >
        <template #aktionen="{ row }">
            <RouterLink :to="{ name: 'createdEvent.edit', params: { id: row.id } }">
                <Button
                    aria-label="Bearbeiten"
                    class="mr-2"
                >
                    <Icon
                        name="edit"
                        color="white"
                    />
                </Button>
            </RouterLink>
            <RouterLink
                class="ml-2"
                :to="{ name: 'materialGenerator.event', params: { uuid: row.mobilizon_uuid } }"
            >
                <Button
                    icon-left="wall_art"
                    class="mr-2"
                >
                    Werbemittel
                </Button>
            </RouterLink>
            <RouterLink :to="{ name: 'public.event', params: { uuid: row.mobilizon_uuid } }">
                <Button icon-left="open-in-new">Ansehen</Button>
            </RouterLink>
        </template>
    </Table>
</template>
