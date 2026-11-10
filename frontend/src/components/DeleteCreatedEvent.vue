<script lang="ts" setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { dsgApi } from '@/lib/dsgApi';

import Button from './KERN/Button.vue';
import ConfirmDialog from './ConfirmDialog.vue';

interface Props {
    eventId: number;
    eventType: 'single_event' | 'series_event' | 'uploaded_event' | 'imported_event';
    parentEventId: number | null;
    onlyIcon?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    onlyIcon: false,
});

const router = useRouter();
const { t } = useI18n();
const showDialog = ref<boolean>(false);
const isSubmitting = ref<boolean>(false);
const errorMessageContent = defineModel<string | null>('errorMessageContent', {
    type: String,
    default: null,
});

const rerouteOptions: Record<string, string> = {
    single_event: 'singleEvents.index',
    series_event: 'seriesEvents.show',
    uploaded_event: 'uploadedEvents.show',
    imported_event: 'importedEvents.show',
};

const getEventTypeLabel = (eventType: string): string => {
    const types = ['series_event', 'single_event', 'imported_event', 'uploaded_event'];

    return types.includes(eventType) ? t(`app.shared.eventTypes.${eventType}`) : t('app.shared.eventTypes.unknown');
};

const deleteEvent = async () => {
    try {
        isSubmitting.value = true;
        const response = await dsgApi.delete(`/created-events/${props.eventId}`);

        router.push({
            name: rerouteOptions[props.eventType],
            query: response.status === 202 ? { requestSent: 'true' } : { deleted: 'success' },
            params: props.parentEventId ? { id: props.parentEventId } : {},
        });
    } catch (error: any) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        errorMessageContent.value =
            error?.response?.data?.error || t('app.shared.genericErrorTryLater');
        console.error(error);
    } finally {
        isSubmitting.value = false;
    }
};

</script>
<template>
    <ConfirmDialog
        v-model="showDialog"
        :title="t('app.shared.deleteCreatedEvent.title', { type: getEventTypeLabel(props.eventType) })"
        :description="t('app.shared.deleteCreatedEvent.description', { type: getEventTypeLabel(props.eventType) })"
        :confirm-text="t('common.delete')"
        @confirm="deleteEvent"
    />
    <Button
        v-if="!onlyIcon"
        :disabled="isSubmitting"
        variant="secondary"
        type="button"
        icon-left="delete"
        @click="showDialog = true"
    >
        {{ t('app.shared.deleteCreatedEvent.button') }}
    </Button>
    <Button
        v-else
        :disabled="isSubmitting"
        variant="secondary"
        type="button"
        icon-left="delete"
        @click="showDialog = true"
    />
</template>
