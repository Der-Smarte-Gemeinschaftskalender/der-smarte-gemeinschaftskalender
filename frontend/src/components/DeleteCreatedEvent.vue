<script lang="ts" setup>
import { defineProps, ref, defineModel } from 'vue';
import { useRouter } from 'vue-router';
import { dsgApi } from '@/lib/dsgApi';
import { isStrictModeEnabled } from '@/lib/instanceConfig';

import Button from './KERN/Button.vue';
import ConfirmDialog from './ConfirmDialog.vue';

interface Props {
    eventId: number;
}

const errorMessageContent = defineModel<string | null>('errorMessageContent', {
    type: String,
    default: null,
});

const showDialog = ref<boolean>(false);
const { eventId } = defineProps<Props>();
const router = useRouter();

const isSubmitting = ref<boolean>(false);

const deleteEvent = async () => {
    try {
        isSubmitting.value = true;

        await dsgApi.delete(`/created-events/${eventId}`);

        router.push({ 
            name: 'singleEvents.index',
            query: isStrictModeEnabled ? { requestSent: 'true' } : {},
        });
    } catch (error: any) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        errorMessageContent.value = error?.response?.data?.error || 'Unbekannter Fehler';
        console.log('Error deleting created event:', errorMessageContent.value);
        console.error(error);
    } finally {
        isSubmitting.value = false;
    }
};
</script>
<template>
    <ConfirmDialog
        v-model="showDialog"
        title="Veranstaltung Löschen"
        content-text="Wenn bereits Werbung für diese Veranstaltung gemacht wurde, sollte der Status auf „Abgesagt” geändert werden, statt den Eintrag zu löschen. Nach dem Löschen kann die Veranstaltung auch nicht mehr als Vorlage verwendet werden."
        confirm-text="Löschen"
        @confirm="deleteEvent"
    />
    <Button
        :disabled="isSubmitting"
        variant="secondary"
        icon-left="delete"
        @click="showDialog = true"
    >
        Veranstaltung löschen
    </Button>
</template>
