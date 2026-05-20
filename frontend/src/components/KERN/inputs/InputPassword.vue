<script setup lang="ts">
import { watch, ref } from 'vue';

import FormInputLabel from './FormInputLabel.vue';
import Button from '../Button.vue';

const model = defineModel<string>();

interface Props {
    label?: string;
    name: string;
    errors?: string;
}

const props = defineProps<Props>();

const showPassword = ref(false);

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

watch(
    () => model.value,
    (newValue) => {
        if (newValue) model.value = newValue.replace(/\s+/g, '');
    }
);
</script>
<template>
    <FormInputLabel
        :id="name"
        :label="label"
        :errors="errors"
    >
        <div class="flex w-full">
            <input
                :id="name"
                v-model="model"
                class="kern-form-input__input w-full"
                :class="{ 'kern-form-input__input--error': !!errors }"
                :name="name"
                :type="showPassword ? 'text' : 'password'"
                :disabled="($attrs.disabled as boolean) ?? false"
                :required="($attrs.required as boolean) ?? false"
                :aria-describedby="errors ? `${props.name}-error` : undefined"
                autocomplete="current-password"
            />
            <Button
                type="button"
                color="white"
                class="switch-password-visibility px-2"
                :icon-left="showPassword ? 'visibility-off' : 'visibility'"
                :title="showPassword ? 'Passwort verbergen' : 'Passwort anzeigen'"
                :aria-label="showPassword ? 'Passwort verbergen' : 'Passwort anzeigen'"
                @click="togglePasswordVisibility"
            >
            </Button>
        </div>
    </FormInputLabel>
</template>
<style lang="scss" scoped>
.switch-password-visibility {
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
}
input.kern-form-input__input {
    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
}
</style>
