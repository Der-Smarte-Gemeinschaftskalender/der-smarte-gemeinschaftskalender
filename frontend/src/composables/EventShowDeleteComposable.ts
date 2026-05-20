import { ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { dsgApi } from '@/lib/dsgApi';
import type { IAlert } from '@/types/components/Alert';

interface UseEventShowDeleteOptions {
    getDeleteUrl: () => string;
    getParams?: () => Record<string, unknown>;
    redirectRouteName: string;
    deletedMessage?: string;
    requestSentMessage?: string;
    deleteErrorTitle?: string;
    fallbackErrorTitle?: string;
    fallbackErrorText: string;
}

export const useEventShowDelete = ({
    getDeleteUrl,
    getParams,
    redirectRouteName,
    deletedMessage = 'Einzeltermin erfolgreich gelöscht.',
    requestSentMessage = 'Ihre Terminanfrage wurde gesendet. Sie werden per E-Mail benachrichtigt, sobald Ihre Anfrage bearbeitet wurde.',
    deleteErrorTitle = 'Fehler beim Löschen',
    fallbackErrorTitle = 'Fehler beim Löschen',
    fallbackErrorText,
}: UseEventShowDeleteOptions) => {
    const route = useRoute();
    const router = useRouter();
    const deleting = ref(false);
    const showDeleteDialog = ref(false);
    const alertMessage = ref<IAlert | null>(null);

    const syncQueryAlerts = () => {
        if (route.query.requestSent === 'true' && requestSentMessage) {
            alertMessage.value = {
                severity: 'info',
                title: 'Anfrage gesendet',
                content: requestSentMessage,
            };
            return;
        }

        if (route.query.deleted === 'success' && deletedMessage) {
            alertMessage.value = {
                severity: 'success',
                title: 'Erfolg',
                content: deletedMessage,
            };
            return;
        }

        alertMessage.value = null;
    };

    const handleDelete = async () => {
        try {
            deleting.value = true;

            const params = getParams ? getParams() : undefined;
            const { data } = await dsgApi.delete<{ error?: string; success?: string }>(getDeleteUrl(), {
                params,
            });

            if (data.error) {
                alertMessage.value = {
                    severity: 'danger',
                    title: deleteErrorTitle,
                    content: data.error,
                };
                return;
            }

            await router.push({
                name: redirectRouteName,
                query: { deleted: 'success' },
            });
        } catch (error) {
            console.error(error);
            alertMessage.value = {
                severity: 'danger',
                title: fallbackErrorTitle,
                content: fallbackErrorText,
            };
        } finally {
            deleting.value = false;
        }
    };

    watch(
        [
            () => route.query.deleted,
            () => route.query.requestSent,
        ],
        () => {
            syncQueryAlerts();
        },
        { immediate: true }
    );

    return {
        deleting,
        showDeleteDialog,
        alertMessage,
        handleDelete,
        syncQueryAlerts,
    };
};
