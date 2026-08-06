<script lang="ts" setup>
import Dialog from './KERN/Dialog.vue';
import Loader from './KERN/cosmetics/Loader.vue';

interface Props {
    title: string;
    description: string;
    hint?: string;
}

withDefaults(defineProps<Props>(), {
    hint: 'Bitte schließen Sie das Browserfenster nicht und laden Sie die Seite nicht neu.',
});

const model = defineModel<boolean>();
</script>
<template>
    <Dialog
        v-model="model"
        :title="title"
        :closable="false"
        :show-footer="false"
    >
        <div class="flex align-items-center gap-4 flex-wrap">
            <Loader
                class="loading-dialog__loader"
                :screen-reader-text="description"
            />
            <div
                class="loading-dialog__text flex-1"
                aria-live="polite"
            >
                <p>{{ description }}</p>
                <p
                    v-if="hint"
                    class="mt-3 font-bold"
                >
                    {{ hint }}
                </p>
            </div>
        </div>
    </Dialog>
</template>
<style scoped>
.loading-dialog__text {
    min-width: 12rem;
}

.loading-dialog__loader {
    flex-shrink: 0;
    width: var(--kern-dimensions-48, 48px);
    height: var(--kern-dimensions-48, 48px);
    border-width: 6px;
}
</style>
