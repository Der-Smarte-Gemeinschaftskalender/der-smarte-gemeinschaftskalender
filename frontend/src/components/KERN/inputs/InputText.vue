<script setup lang="ts">
import { computed, ref } from 'vue';

import FormInputLabel from './FormInputLabel.vue';

const model = defineModel<any>();

interface Props {
    label?: string;
    name: string;
    errors?: string;
    list?: string[];
    placeholder?: string;
    inputClass?: string;
    ariaLabel?: string;
}

const props = defineProps<Props>();

const list = computed(() => {
    // filter if list item is same as model
    if (props.list && model.value) {
        return props.list.filter((item) => item.toLowerCase() !== model.value.toLowerCase());
    }
    return [];
});

const showList = ref(false);

const selectOption = (option: string) => {
    model.value = option;
};

window.addEventListener('click', (event) => {
    if (props.list && props.list.length > 0) {
        const inputElement = document.getElementById(props.name)!;
        if (event.target !== inputElement && !inputElement?.contains(event.target as Node)) {
            showList.value = false;
        }
    }
});
</script>
<template>
    <FormInputLabel
        :id="name"
        :label="label"
        :errors="errors"
        class="relative"
        :disabled="$attrs.disabled as boolean ?? false"
    >
        <input
            :id="name"
            v-model="model"
            autocomplete="off"
            :name="name"
            class="kern-form-input__input w-full"
            :class="(!!errors ? 'kern-form-input__input--error ' : '') + inputClass"
            type="text"
            :placeholder="placeholder"
            :aria-describedby="errors ? `${name}-error` : undefined"
            :disabled="$attrs.disabled as boolean ?? false"
            :aria-label="ariaLabel"
            @focus="showList = true"
        />
        <ul
            v-if="list && list.length > 0"
            class="z-10 w-full border list-none absolute"
            :class="showList ? 'block' : 'hidden'"
            @blur="showList = false"
        >
            <li
                v-for="item in list"
                :key="item"
                class="cursor-pointer p-2 flex align-items-center"
                @click="selectOption(item)"
            >
                {{ item }}
            </li>
        </ul>
    </FormInputLabel>
</template>
<style scoped lang="scss">
ul {
    position: absolute;
    top: 105%; // slightly below the input (adjust if needed)
    left: 0;
    width: 100%;
    background-color: #fff9e6;
    border: 1px solid #e0e0e0;
    border-radius: 0.375rem; // ~rounded-md
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    z-index: 1100;
    padding: 0.25rem 0;

    li {
        padding: 0.5rem 0.75rem;
        display: flex;
        align-items: center;
        cursor: pointer;
        transition: background-color 0.2s ease;

        &:hover {
            background-color: #f0f0f0;
        }
    }
}
</style>
