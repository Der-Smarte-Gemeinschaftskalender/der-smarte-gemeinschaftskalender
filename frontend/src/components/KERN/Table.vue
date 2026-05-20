<script lang="ts" setup>
import { computed, ref, watch, isRef, type Ref } from 'vue';
import { dsgApi } from '@/lib/dsgApi';
import { useRouter } from 'vue-router';

import Button from '@/components/KERN/Button.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';

import type { Column } from '@/types/General';

interface Props {
    title?: string;
    columns: Array<Column>;
    page?: number;
    pageSize?: number;
    valuesMaxLength?: number;
    data?: Ref<Array<any>> | Array<any>;
    api?: {
        url?: string;
        params?: Record<string, any>;
        deleteUrl?: string;
        deleteDialogTitle?: string;
        deleteDialogDescription?: ((row: any) => string);
    };
}

interface ResponseData {
    data: Array<any>;
    total: number;
}

const props = defineProps<Props>();

const router = useRouter();
const pageSize = computed(() => props.pageSize ?? 10);
const rawData = computed<Array<any>>(() => {
    if (props.api?.url) return [];
    return isRef(props.data) ? props.data.value : (props.data ?? []);
});

const error = ref<string | null>(null);
const displayData = ref<Array<any>>([]);
const currentPage = ref(props.page ?? 1);
const allPages = ref(1);
const showDeleteConfirmDialog = ref(false);
const pendingDeleteRow = ref<any>(null);

const deleteDialogTitle = computed(() => props.api?.deleteDialogTitle ?? 'Veranstaltung Löschen');

const deleteDialogDescription = computed<string>(() => {
    return props.api?.deleteDialogDescription?.(pendingDeleteRow.value) || '';
});

const showDeleteDialog = ref(false);
const pendingDeleteId = ref<number | null>(null);


const loadData = async (): Promise<void> => {
    const response = await dsgApi.get<ResponseData>(
        buildQuery(<string>props.api!.url, {
            page: currentPage.value,
            pageSize: pageSize.value,
            ...props.api!.params,
        })
    );

    const result = response.data as ResponseData;

    allPages.value = Math.ceil(result.total / pageSize.value) || 1;
    displayData.value = result.data;
};

const buildQuery = (base: string, query: Record<string, any>): string => {
    const params = new URLSearchParams();
    Object.entries(query).forEach(([key, value]) => {
        if (value !== undefined && value !== null) params.set(key, value.toString());
    });

    return `${base}?${params.toString()}`;
};

const handleArrayData = (): void => {
    allPages.value = Math.ceil(rawData.value.length / pageSize.value) || 1;

    const begin = (currentPage.value - 1) * pageSize.value;
    const end = currentPage.value * pageSize.value;
    displayData.value = (<Array<any>>rawData.value).slice(begin, end);
};

const deleteEntry = (row: any) => {
    pendingDeleteRow.value = row;
    showDeleteConfirmDialog.value = true;
};

const confirmDeleteEntry = async () => {
    if (pendingDeleteRow.value === null) {
        return;
    }

    if (!props.api?.deleteUrl) {
        console.warn('deleteUrl prop not provided');
        return;
    }

    error.value = null;
    
    try {
        const response = await dsgApi.delete(`${props.api.deleteUrl}/${pendingDeleteRow.value.id}`, {
            params: props.api.params,
        });

        if (response.status === 202) {
            router.replace({
                query: { requestSent: 'true' },
            });

            return;
        }

        displayData.value = displayData.value.filter((row) => row.id !== pendingDeleteRow.value.id);

        if (displayData.value.length === 0 && currentPage.value > 1) {
            currentPage.value--;
        }

        if (props.api?.url) await loadData();
    } catch (deleteError: any) {
        error.value = deleteError?.response?.data?.error || 'Es ist ein Fehler aufgetreten. Bitte versuche es später erneut.';
        console.error('Fehler beim Löschen:', deleteError);
    } finally {
        pendingDeleteRow.value = null;
    }
};

const executeDelete = async () => {
    if (pendingDeleteId.value !== null) {
        await deleteEntry(pendingDeleteId.value);
    }
    showDeleteDialog.value = false;
    pendingDeleteId.value = null;
};

// Initialization part
watch(
    () => props.data,
    () => {
        if (props.api?.url) loadData();
        else handleArrayData();
    }
);

watch(currentPage, () => {
    if (props.api?.url) loadData();
    else handleArrayData();
});

defineExpose({
    refresh: () => {
        if (props.api?.url) loadData();
        else handleArrayData();
    },
    error
});

if (props.api?.url) loadData();
else handleArrayData();
</script>
<template>
    <div class="kern-table--responsive w-full p-0">
        <ConfirmDialog
            v-model="showDeleteConfirmDialog"
            :title="deleteDialogTitle"
            :description="deleteDialogDescription"
            :confirm-text="'Löschen'"
            :cancel-text="'Abbrechen'"
            @cancel="pendingDeleteRow = null"
            @confirm="confirmDeleteEntry"
        />
        <table
            class="kern-table kern-table--striped w-full"
            :columns="columns"
            :data="data"
        >
            <caption
                v-if="title"
                class="kern-table__caption"
            >
                {{ title }}
            </caption>
            <thead class="kern-table__head">
                <tr class="kern-table__row">
                    <template
                        v-for="column in columns"
                        :key="column.key"
                    >
                        <th
                            scope="col"
                            class="kern-table__header vertical-align-middle px-2"
                            :class="[
                                { 'kern-table__header--numeric': !!column.numeric },
                                column.align ? `text-${column.align}` : 'text-left',
                            ]"
                        >
                            {{ column.name }}
                        </th>
                    </template>
                </tr>
            </thead>
            <tbody class="kern-table__body">
                <template v-if="displayData.length">
                    <tr
                        v-for="row in displayData"
                        :key="row.key"
                        class="kern-table__row"
                    >
                        <template
                            v-for="column in columns"
                            :key="column.key"
                        >
                            <td
                                class="kern-table__cell vertical-align-middle px-2"
                                :class="[
                                    { 'kern-table__cell--numeric': !!column.numeric },
                                    column.align ? `text-${column.align}` : 'text-left',
                                ]"
                            >
                                <slot
                                    :name="column.key"
                                    :column="column"
                                    :row="row"
                                    :delete-entry="deleteEntry"
                                >
                                    <template v-if="!column.format">
                                        {{
                                            typeof row[column?.key] === 'string' &&
                                            props.valuesMaxLength &&
                                            row[column?.key].length > props.valuesMaxLength
                                                ? `${row[column?.key].slice(0, props.valuesMaxLength)}...`
                                                : row[column?.key]
                                        }}
                                    </template>
                                    <template v-else>
                                        {{ column.format(row[column?.key], row) }}
                                    </template>
                                </slot>
                            </td>
                        </template>
                    </tr>
                </template>
                <tr
                    v-else
                    class="kern-table__row relative"
                >
                    <td>
                        <p
                            class="p-4 text-center text-600 absolute left-50 w-full"
                            style="transform: translateX(-50%)"
                        >
                            Keine Einträge gefunden.
                        </p>
                    </td>
                </tr>
            </tbody>
            <tfoot class="kern-table__footer">
                <tr class="kern-table__row">
                    <td
                        :colspan="columns.length"
                        class="kern-table__cell"
                    >
                        <div class="pagination-controls flex justify-content-center">
                            <div
                                class="flex align-items-center justify-content-around gap-2 sm:px-3 md:px-6 max-w-56rem w-full"
                            >
                                <Button
                                    :disabled="currentPage <= 1"
                                    class="pagination-button text-3xl px-1"
                                    label="«"
                                    aria-label="Erste Seite"
                                    @click="currentPage = 1"
                                />
                                <Button
                                    :disabled="currentPage <= 1"
                                    class="pagination-button text-3xl px-2"
                                    label="‹"
                                    aria-label="Voherige Seite"
                                    @click="currentPage--"
                                />
                                <span class="pagination-info">Seite {{ currentPage }} von {{ allPages }}</span>
                                <Button
                                    :disabled="currentPage >= allPages"
                                    class="pagination-button text-3xl px-2"
                                    label="›"
                                    aria-label="Nächste Seite"
                                    @click="currentPage++"
                                />
                                <Button
                                    :disabled="currentPage >= allPages"
                                    class="pagination-button text-3xl px-1"
                                    label="»"
                                    aria-label="Letzte Seite"
                                    @click="currentPage = allPages"
                                />
                            </div>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>


        <ConfirmDialog
            v-model="showDeleteDialog"
            title="Möchten Sie wirklich diesen Eintrag löschen?"
            confirm-text="Löschen"
            @confirm="executeDelete"
        />
    </div>
</template>
<style scoped lang="scss">
tr {
    height: 4.5rem;
}
</style>
