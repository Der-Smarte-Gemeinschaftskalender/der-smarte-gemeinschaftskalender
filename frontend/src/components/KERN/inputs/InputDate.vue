<script setup lang="ts">
import FormInputLabel from './FormInputLabel.vue';

export interface InputDateProps {
    label?: string;
    name: string;
    errors?: string;
}

const model = defineModel<string>();

const props = defineProps<InputDateProps>();
</script>
<template>
    <FormInputLabel
        :id="name"
        :label="label"
        :errors="errors"
    >
        <input
            :id="name"
            v-model="model"
            class="kern-form-input__input"
            :class="{ 'kern-form-input__input--error': !!errors }"
            :name="name"
            type="date"
            v-bind="$attrs"
            :aria-describedby="errors ? `${props.name}-error` : undefined"
        />
    </FormInputLabel>
</template>
