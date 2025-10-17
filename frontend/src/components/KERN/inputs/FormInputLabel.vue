<script setup lang="ts">
import InputDisplayError from './InputDisplayError.vue';
interface Props {
    label?: string;
    id: string;
    errors?: string;
    inputType?: 'input' | 'check';
    disabled?: boolean;
}

withDefaults(defineProps<Props>(), {
    inputType: 'input',
});
</script>
<template>
    <div
        :class="{
            'kern-form-input--error': !!errors,
            'kern-form-input': inputType === 'input',
            'kern-form-check': inputType === 'check',
        }"
    >
        <label
            v-if="label && inputType === 'input'"
            :for="id"
            class="kern-form-input__label"
            :class="{ 'text-gray-500': disabled }"
        >
            {{ label }}
            <slot name="label" />
        </label>
        <slot />

        <label
            v-if="label && inputType === 'check'"
            :for="id"
            class="kern-form-check__label"
        >
            <slot name="label">{{ label }}</slot>
        </label>
        <InputDisplayError
            v-if="!!errors"
            :errors="errors"
            :for-id="id"
        />
    </div>
</template>
<style scoped lang="scss">
label {
    font-size: 1rem;
}
.kern-form-input:not(.kern-form-input--error) {
    padding: 0;
    transition: padding-left 0.2s;
}

.kern-form-input--error {
    padding-left: 1rem;
    transition: padding-left 0.2s;
}
</style>
