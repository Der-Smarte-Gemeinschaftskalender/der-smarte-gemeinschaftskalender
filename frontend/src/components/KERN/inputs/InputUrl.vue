<script setup lang="ts">
import { ref, watch } from 'vue';

import FormInputLabel from './FormInputLabel.vue';

interface Props {
    label?: string
    name: string
    errors?: string
    required?: boolean
}


const model = defineModel<string>();
const props = defineProps<Props>();

const value = ref(model.value ?? '');
const protocol = ref<'https://' | 'http://'>('https://')

const protocolOptions = [
    {text: 'https://', value: 'https://'},
    {text: 'http://', value: 'http://'},
];

watch(model, (newVal) => {
    if (!newVal) {
        value.value = '';
        return;
    }

    if (newVal.startsWith('https://')) {
        protocol.value = 'https://';
        value.value = newVal.replace(/^https?:\/\//, '');
    }
    else if (newVal.startsWith('http://')) {
        protocol.value = 'http://';
        value.value = newVal.replace(/^https?:\/\//, '');
    }
    else {
        value.value = newVal;
    }

}, { immediate: true });

watch([protocol, value], ([proto, val]) => {
    model.value = val.trim() === ''
        ? ''
        : proto + val.replace(/^https?:\/\//, '');
});

</script>

<template>
    <FormInputLabel
        :id="name"
        :label="label"
        :errors="errors"
        :disabled="$attrs.disabled as boolean ?? false"
    >
        <div class="flex align-items-center w-full">
            <div
                class="kern-form-input__select-wrapper"
                :class="{
                    'disabled': $attrs.disabled as boolean ?? false,
                }"
            >
                <select
                    v-model="protocol"
                    class="kern-form-input__select bg-theme-primary outline-none text-white pr-6"
                    :name="name"
                    v-bind="$attrs"
                    :aria-describedby="errors ? `${name}-error` : undefined"
                >
                    <option
                        v-for="option in protocolOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.text }}
                    </option>
                </select>
            </div>
            <input
                :id="name"
                v-model="value"
                class="kern-form-input__input flex-1 p-2 outline-none"
                :required="required"
                :name="name"
                type="url"
                v-bind="$attrs"
                :disabled="$attrs.disabled as boolean  ?? false"
                :aria-describedby="errors ? `${props.name}-error` : undefined"
                :class="{
                    'kern-form-input__input--error': !!errors,
                    'bg-gray-100': $attrs.disabled ?? false
                }"
                placeholder="gemeinschaftskalender.de"
            />
        </div>
    </FormInputLabel>
</template>

<style scoped lang="scss">
.kern-form-input__select-wrapper {
    border-radius: 2px 0 0 2px !important;

    &.disabled {
        opacity: 0.4;
    }

    &:after {
        background-color: white;
        position: absolute;
        right: 1rem;
        top: 55%;
    }

    .kern-form-input__select {
        min-width: 7rem;
        border-radius: 2px 0 0 0 !important;
    }
}

.kern-form-input__input {
    border-radius: 0 2px 2px 0 !important;
}
</style>
