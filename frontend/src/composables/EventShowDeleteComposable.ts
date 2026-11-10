import { ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { dsgApi } from '@/lib/dsgApi';
import i18n from '@/i18n';
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
    deletedMessage = i18n.global.t('app.events.singleEventDeleted'),
    requestSentMessage = i18n.global.t('app.events.requestSentMessage'),
    deleteErrorTitle = i18n.global.t('app.shared.deleteErrorTitle'),
    fallbackErrorTitle = i18n.global.t('app.shared.deleteErrorTitle'),
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
                title: i18n.global.t('app.shared.requestSent'),
                content: requestSentMessage,
            };
            return;
        }

        if (route.query.deleted === 'success' && deletedMessage) {
            alertMessage.value = {
                severity: 'success',
                title: i18n.global.t('common.success'),
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
