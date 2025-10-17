<script setup lang="ts">
import FormInputLabel from './FormInputLabel.vue';
const model = defineModel<any>();

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
        :input-type="'check'"
    >
        <input
            :id="name"
            v-model="model"
            class="kern-form-check__radio"
            :class="{ 'kern-form-check__radio--error': !!errors }"
            :name="name"
            type="radio"
            v-bind="$attrs"
            :aria-describedby="errors ? `${props.name}-error` : undefined"
        />
    </FormInputLabel>
</template>
