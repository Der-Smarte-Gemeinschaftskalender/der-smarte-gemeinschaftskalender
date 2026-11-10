<script lang="ts" setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Dialog from './KERN/Dialog.vue';
import Loader from './KERN/cosmetics/Loader.vue';

interface Props {
    title: string;
    description: string;
    hint?: string;
}

const props = defineProps<Props>();
const { t } = useI18n();

const hintText = computed(() => props.hint ?? t('app.shared.loadingDialog.hint'));

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
                    v-if="hintText"
                    class="mt-3 font-bold"
                >
                    {{ hintText }}
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
