<script setup lang="ts">
import { defineProps } from 'vue';
import { mimeTypes } from '@/types/File';
import FormInputLabel from '@/components/KERN/inputs/FormInputLabel.vue';

interface Props {
    name: string;
    label?: string;
    disabled?: boolean;
    multiple?: boolean;
    errors?: string;
    accept?: keyof typeof mimeTypes | Array<keyof typeof mimeTypes>;
}

const props = defineProps<Props>();

const emit = defineEmits(['fileChange']);


const getAccept = (): string => {
    const accept: string[] = typeof props.accept === 'string' ? props.accept.split(',') : props.accept!;

    const formatedAccept = accept.map((item) => {
        item = item.trim();
        return mimeTypes[item as keyof typeof mimeTypes] ?? item;
    });
    // remove duplicates
    const uniqueAccept = [...new Set(formatedAccept)];
    return uniqueAccept.join(', ');
};
const onFileChanged = ($event: Event) => {
    const target = $event.target as HTMLInputElement;
    if (target && target.files) {
        emit('fileChange', target.files);
    }
};
</script>

<template>
    <FormInputLabel
        :id="name"
        :label="label"
        :errors="errors"
    >
        <div
            v-if="accept"
            :id="`input-${name}`"
            class="kern-form-input__hint w-full"
        >
            Erlaubte Formate: {{ getAccept() }}
        </div>

        <input
            :id="name"
            class="kern-form-input__input w-full"
            :name="name"
            type="file"
            :accept="typeof accept === 'string' ? accept : accept?.join(', ')"
            :aria-describedby="`input-${name} file-input`"
            :disabled="disabled"
            :multiple="multiple ?? false"
            @change="onFileChanged"
        />
    </FormInputLabel>
</template>

<style scoped lang="scss"></style>
