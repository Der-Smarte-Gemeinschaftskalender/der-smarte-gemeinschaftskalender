<script lang="ts" setup>
import { defineProps, ref, defineModel } from 'vue';
import { handleSubmitCallback } from '@/lib/dsgClient';
import { mobilizon_event_status } from '@/lib/const';

import Button from './KERN/Button.vue';
import ConfirmDialog from './ConfirmDialog.vue';

import { EventStatus } from '@/types/General';

const mobilizonGroupIdModel = defineModel<number | undefined>('mobilizonGroupId');

interface Props {
    status: EventStatus;
    eventId: number;
    mobilizonId: number;
    mobilizonGroupId?: any;
}

const showDialog = ref<boolean>(false);
const { status, eventId, mobilizonId } = defineProps<Props>();
const statusText = mobilizon_event_status.find((statusOption) => statusOption.value === status)?.text;
const emit = defineEmits(['statusChanged']);

const isSubmitting = ref(false);

const changeEventStatus = async () => {
    try {
        isSubmitting.value = true;

        await handleSubmitCallback(`/created-events/update-status/${eventId}`, {
            values: {
                mobilizon_id: mobilizonId,
                mobilizon_fields: {
                    status,
                },
                mobilizon_group_id: mobilizonGroupIdModel.value,
            },
            resDataKey: 'createdEvent',
        });
        emit('statusChanged');
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
        title="Status ändern"
        :confirm-text="`Status auf ${statusText} ändern`"
        @confirm="changeEventStatus"
    />
    <Button
        :disabled="isSubmitting"
        @click="showDialog = true"
    >
        {{ statusText }}
    </Button>
</template>
