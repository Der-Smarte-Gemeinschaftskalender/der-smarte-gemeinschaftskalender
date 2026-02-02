<script lang="ts" setup>
import Button from './Button.vue';
import { watch } from 'vue';
const model = defineModel<boolean>();

interface Props {
    title: string;
    showContent?: boolean;
}

withDefaults(defineProps<Props>(), {
    showContent: true,
});

watch(model, (newVal) => {
    document.documentElement.style.overflow = newVal ? 'hidden' : '';
});
</script>
<template>
    <div
        v-if="model"
        class="dialog-overlay h-full w-full fixed top-0 left-0 flex align-items-center justify-content-center"
    >
        <dialog
            id="modal1"
            class="kern-dialog fixed z-5 top-50"
            aria-labelledby="modal1_heading"
            :open="!!model"
            :title="title"
            style="transform: translateY(-50%)"
        >
            <header class="kern-dialog__header">
                <h2
                    id="modal1_heading"
                    class="kern-dialog__heading"
                >
                    {{ title }}
                </h2>
                <Button
                    icon-left="close"
                    variant="tertiary"
                    class="close-button"
                    @click="model = false"
                ></Button>
            </header>
            <section
                v-if="showContent"
                class="kern-dialog__content"
            >
                <slot />
            </section>
            <footer class="kern-dialog__footer">
                <slot name="footer">
                    <Button
                        variant="secondary"
                        @click="model = false"
                    >
                        Abbrechen
                    </Button>
                </slot>
            </footer>
        </dialog>
    </div>
</template>
<style scoped>
.kern-dialog {
    z-index: 99;
}

.dialog-overlay {
    background-color: rgba(0, 0, 0, 0.4);
    z-index: 98;
}
.close-button {
    box-shadow: none !important;
}
</style>
