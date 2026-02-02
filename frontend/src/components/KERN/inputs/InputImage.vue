<script setup lang="ts">
import { ref, watch } from 'vue';
import InputFile from '@/components/KERN/inputs/InputFile.vue';
import Button from '@/components/KERN/Button.vue';
import { mimeTypes } from "@/types/File";

const model = defineModel<File | undefined>();
const altModel = defineModel<string>('alt');

interface Props {
    name: string;
    label?: string;
    disabled?: boolean;
    errors?: string;
    accept?: keyof typeof mimeTypes | Array<keyof typeof mimeTypes>;
}

const props = defineProps<Props>();

const emit = defineEmits(['update:modelValue']);

const imagePreview = ref<string | null>(null);

const onFileChange = (files: FileList) => {
    const file = files.item(0);
    emit('update:modelValue', file ?? undefined);

    imagePreview.value = null;

    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            if (typeof e.target?.result === 'string') imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const clearModel = () => {
    emit('update:modelValue', undefined);
    imagePreview.value = null;
    altModel.value = '';
};

watch(model, (newFile) => {
    if (!(newFile instanceof File)) {
        imagePreview.value = null;
        altModel.value = '';
    } else if (newFile) {
        const reader = new FileReader();
        reader.onload = (e) => {
            if (typeof e.target?.result === 'string') imagePreview.value = e.target.result;
        };

        reader.readAsDataURL(newFile);
    }
});
</script>

<template>
    <div class="flex flex-column md:flex-row gap-5 justify-content-between">
        <div class="flex flex-column justify-content-between gap-5">
            <InputFile
                :name="name"
                :label="label"
                :disabled="disabled"
                :errors="errors"
                :multiple="false"
                :accept="props.accept || ['image/gif', 'image/png', 'image/jpeg', 'image/webp']"
                @file-change="onFileChange"
                @click="clearModel"
            />
            <!--
            <InputText
                v-if="imagePreview"
                v-model="altModel"
                :name="`${name}Alt`"
                label="Alternativtext (optional)"
            />
            -->
        </div>
        <div
            v-if="imagePreview"
            class="border rounded overflow-hidden relative col-6"
        >
            <img
                :src="imagePreview"
                class="object-cover w-full h-full"
                alt="Ereignisvorschau"
            />
            <Button
                class="w-fit absolute delete-button"
                style="bottom: 1rem; right: 1rem"
                icon-left="delete"
                @click="clearModel"
            />
        </div>
    </div>
</template>
<style lang="scss" scoped>
.image-wrapper {
    height: 18rem;
}
.delete-button {
    right: 0.5rem;
    bottom: 0.5rem;
}
</style>
