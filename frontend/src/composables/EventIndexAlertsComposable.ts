import { ref, watch } from 'vue';
import { useRoute } from 'vue-router';

import Table from '@/components/KERN/Table.vue';

import type { IAlert } from '@/types/components/Alert';


interface UseEventIndexAlertsOptions {
    deletedMessage: string;
    requestSentMessage?: string;
}

type TableRefLike = {
    value: InstanceType<typeof Table> | null;
};

export const useEventIndexAlerts = (
    tableRef: TableRefLike,
    { deletedMessage, requestSentMessage }: UseEventIndexAlertsOptions
) => {
    const route = useRoute();
    const alertMessage = ref<IAlert | null>(null);

    const syncAlertsAndQuery = async () => {
        const tableError = tableRef.value?.error;

        if (tableError) {
            alertMessage.value = {
                severity: 'danger',
                title: 'Fehler',
                content: tableError,
            };
            return;
        }

        if (route.query.requestSent === 'true' && requestSentMessage) {
            alertMessage.value = {
                severity: 'info',
                title: 'Anfrage gesendet',
                content: requestSentMessage,
            };
            return;
        }

        if (route.query.deleted === 'success') {
            alertMessage.value = {
                severity: 'success',
                title: 'Erfolg',
                content: deletedMessage,
            };
            return;
        }

        alertMessage.value = null;
    };

    watch(
        [
            () => route.query.deleted,
            () => route.query.requestSent,
            () => tableRef.value?.error,
        ],
        () => {
            void syncAlertsAndQuery();
        },
        { immediate: true }
    );

    return {
        alertMessage,
        syncAlertsAndQuery,
    };
};