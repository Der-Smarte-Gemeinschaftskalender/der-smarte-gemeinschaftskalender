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
            class="kern-form-check__checkbox"
            :class="{ 'kern-form-check__checkbox--error': !!errors }"
            :name="name"
            type="checkbox"
            v-bind="$attrs"
            :aria-describedby="errors ? `${props.name}-error` : undefined"
        />
        <template #label>
            <slot name="label" />
        </template>
    </FormInputLabel>
</template>
<style scoped lang="scss">
.kern-form-check__checkbox::before {
    top: 10px !important;
    left: 4px !important;
}
</style>
