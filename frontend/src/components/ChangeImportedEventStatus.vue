<script lang="ts" setup>
import { ref } from 'vue';
import { dsgApi } from '@/lib/dsgApi';

import Button from './KERN/Button.vue';
import ConfirmDialog from './ConfirmDialog.vue';

import { IAlert } from '@/types/components/Alert';


interface Props {
    importedEventId: string | number;
    currentStatus: 'active' | 'inactive';
}

const props = defineProps<Props>();

const alertMessage = defineModel<IAlert | null>('alertMessage', { default: null });

const showDialog = ref<boolean>(false);
const newStatus = ref<string | null>(null);

const errorMessage = ref<string>('');
const emit = defineEmits(['statusChanged']);

const changeStatus = async () => {
    if (newStatus.value) {
        try {
            await dsgApi.post(`/imported-events/${props.importedEventId}/status`, {
                status: newStatus.value,
            });
            emit('statusChanged', newStatus.value);
        } catch (error: any) {
            console.error('Error changing status:', error);

            const message =
                error.response?.data?.error ||
                'Der Status konnte nicht geändert werden. Bitte versuchen Sie es später erneut oder kontaktieren Sie den Support.';
            alertMessage.value = {
                severity: 'danger',
                title: 'Fehler',
                content: message,
            };
        }
    }
};

defineExpose({
    errorMessage,
});
</script>
<template>
    <Button
        v-if="currentStatus === 'active'"
        variant="primary"
        icon-left="link"
        @click="
            showDialog = true;
            newStatus = 'inactive';
        "
    >
        Deaktivieren
    </Button>
    <Button
        v-else-if="currentStatus === 'inactive'"
        variant="primary"
        icon-left="link"
        @click="
            showDialog = true;
            newStatus = 'active';
        "
    >
        Aktivieren
    </Button>
    <ConfirmDialog
        v-model="showDialog"
        title="Status ändern"
        :confirm-text="newStatus === 'active' ? 'Aktivieren' : 'Deaktivieren'"
        @confirm="changeStatus"
    />
</template>
