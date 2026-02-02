<script lang="ts" setup>
import { ref } from 'vue';
import Button from './KERN/Button.vue';
import ConfirmDialog from './ConfirmDialog.vue';
import { dsgApi } from '@/lib/dsgApi';

const showDialog = ref<boolean>(false);
const newStatus = ref<string | null>(null);

const emit = defineEmits(['statusChanged']);

const changeStatus = async () => {
    if (newStatus.value) {
        try {
            await dsgApi.post(`/imported-events/${props.importedEventId}/status`, {
                status: newStatus.value,
            });
            emit('statusChanged', newStatus.value);
        } catch (error) {
            console.error('Error changing status:', error);
        }
    }
};

interface Props {
    importedEventId: string | number;
    currentStatus: 'active' | 'inactive';
}
const props = defineProps<Props>();
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
