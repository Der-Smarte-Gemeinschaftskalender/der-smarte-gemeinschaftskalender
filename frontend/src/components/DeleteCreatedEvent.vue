<script lang="ts" setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
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
    const types = {
        series_event: 'Einzeltermin aus Terminserie',
        single_event: 'Einzeltermin',
        imported_event: 'Einzeltermin aus Kalenderintegration',
        uploaded_event: 'Einzeltermin aus Kalenderdatei',
    };

    return types[eventType as keyof typeof types] || 'Unbekannter Termin';
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
            error?.response?.data?.error || 'Es ist ein Fehler aufgetreten. Bitte versuche es später erneut.';
        console.error(error);
    } finally {
        isSubmitting.value = false;
    }
};

</script>
<template>
    <ConfirmDialog
        v-model="showDialog"
        :title="`${getEventTypeLabel(props.eventType)} löschen`"
        :description='`Sie sind dabei einen ${getEventTypeLabel(props.eventType)} zu löschen.\nEine Wiederherstellung ist nicht möglich.\n\n Wenn bereits Werbung für die Veranstaltung gemacht wurde, sollten Sie den Status der Veranstaltung auf "Abgesagt" setzen, statt die Veranstaltung zu löschen. Nach dem Löschen kann die Veranstaltung auch nicht mehr als Vorlage verwendet werden.`'
        confirm-text="Löschen"
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
        Veranstaltung löschen
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
