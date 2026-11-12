<script lang="ts" setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { dsgApi } from '@/lib/dsgApi';

import Button from './KERN/Button.vue';
import ConfirmDialog from './ConfirmDialog.vue';

import { IAlert } from '@/types/components/Alert';


interface Props {
    importedEventId: string | number;
    currentStatus: 'active' | 'inactive';
}

const props = defineProps<Props>();
const { t } = useI18n();

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
                t('app.shared.changeImportedStatus.error');
            alertMessage.value = {
                severity: 'danger',
                title: t('common.error'),
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
        {{ t('app.shared.changeImportedStatus.deactivate') }}
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
        {{ t('app.shared.changeImportedStatus.activate') }}
    </Button>
    <ConfirmDialog
        v-model="showDialog"
        :title="t('app.shared.changeStatus.title')"
        :confirm-text="newStatus === 'active' ? t('app.shared.changeImportedStatus.activate') : t('app.shared.changeImportedStatus.deactivate')"
        @confirm="changeStatus"
    />
</template>
