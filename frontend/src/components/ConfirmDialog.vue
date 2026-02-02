<script lang="ts" setup>
import Dialog from './KERN/Dialog.vue';
import Button from './KERN/Button.vue';
const model = defineModel<boolean>();
interface Props {
    confirmText: string;
    cancelText?: string;
    contentText?: string;
}
defineEmits(['confirm', 'cancel']);
withDefaults(defineProps<Props>(), {
    cancelText: 'Abbrechen',
});
</script>
<template>
    <Dialog
        v-model="model"
        title="Bestätigen"
        :show-content="!!contentText"
    >
        {{ contentText }}
        <template #footer>
            <Button
                variant="secondary"
                @click="
                    $emit('cancel');
                    model = false;
                "
            >
                {{ cancelText }}
            </Button>
            <Button
                variant="primary"
                @click="
                    $emit('confirm');
                    model = false;
                "
            >
                {{ confirmText }}
            </Button>
        </template>
    </Dialog>
</template>
<style>
.dialog-overlay {
    z-index: 1099 !important;
}
</style>
