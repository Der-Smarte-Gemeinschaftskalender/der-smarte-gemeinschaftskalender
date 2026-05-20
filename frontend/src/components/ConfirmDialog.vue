<script lang="ts" setup>
import Dialog from './KERN/Dialog.vue';
import Button from './KERN/Button.vue';

interface Props {
    title?: string;
    description?: string;
    confirmText: string;
    cancelText?: string;
}

withDefaults(defineProps<Props>(), {
    title: 'Bestätigen',
    cancelText: 'Abbrechen',
    description: '',
});

const model = defineModel<boolean>();

defineEmits(['confirm', 'cancel']);
</script>
<template>
    <Dialog
        v-model="model"
        :title="title"
        :show-content="!!description"
    >
        <p class="whitespace-pre-line">
            {{ description }}
        </p>
        <template #footer>
            <Button
                class="reject-button"
                variant="secondary"
                :label="cancelText"
                @click="
                    $emit('cancel');
                    model = false;
                "
            />
            <Button
                class="confirm-button"
                variant="primary"
                :label="confirmText"
                @click="
                    $emit('confirm');
                    model = false;
                "
            />
        </template>
    </Dialog>
</template>
<style>
.dialog-overlay {
    z-index: 1099 !important;
}
.whitespace-pre-line {
    white-space: pre-line;
}
</style>
