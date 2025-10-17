<script setup lang="ts">
import FormInputLabel from './FormInputLabel.vue';
const model = defineModel<string>();

interface Props {
    label?: string;
    name: string;
    errors?: string;
}

const props = defineProps<Props>();
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
            type="color"
            :aria-describedby="errors ? `${props.name}-error` : undefined"
        />
    </FormInputLabel>
</template>
<style scoped>
input {
    width: 80px;
}
</style>
