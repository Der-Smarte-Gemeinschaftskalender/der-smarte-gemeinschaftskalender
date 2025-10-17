<script lang="ts" setup>
import { defineProps, ref, defineModel } from 'vue';
import { useRouter } from 'vue-router';

import Button from './KERN/Button.vue';
import ConfirmDialog from './ConfirmDialog.vue';
import { dsgApi } from '@/lib/dsgApi';
import Alert from './KERN/Alert.vue';

interface Props {
    eventId: number;
}

const showDialog = ref<boolean>(false);
const { eventId } = defineProps<Props>();
const router = useRouter();

const isSubmitting = ref<boolean>(false);
const showErrorAlert = ref<boolean>(false);

const deleteEvent = async () => {
    try {
        isSubmitting.value = true;

        const { data } = await dsgApi.delete(`/created-events/${eventId}`, <any>{});
        console.log(data);
        router.push({ name: 'dashboard' });
    } catch (error: any) {
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
        @confirm="deleteEvent"
        contentText="Wenn bereits Werbung für diese Veranstaltung gemacht wurde, sollte der Status auf „Abgesagt” geändert werden, statt den Eintrag zu löschen. Nach dem Löschen kann die Veranstaltung auch nicht mehr als Vorlage verwendet werden."
        confirmText="Löschen"
    />
    <Alert
        v-if="showErrorAlert"
        title="Fehler"
        severity="danger"
    >
        Fehler beim Löschen der Veranstaltung.
    </Alert>
    <Button
        @click="showDialog = true"
        :disabled="isSubmitting"
        variant="secondary"
        icon-left="delete"
    >
        Veranstaltung löschen
    </Button>
</template>
