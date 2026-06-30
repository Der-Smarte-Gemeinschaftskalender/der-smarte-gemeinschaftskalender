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

const emit = defineEmits<{
    focus: [event: FocusEvent];
    blur: [event: FocusEvent];
    mouseover: [event: MouseEvent];
    mouseout: [event: MouseEvent];
}>();

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

const hoverEffect = (item: HTMLElement) => {
    const items = document.querySelectorAll(`#${props.name} + ul li`);

    items.forEach((el) => el.classList.remove('kern-form-input__input--active'));

    item.classList.add('kern-form-input__input--active');
};

window.addEventListener('click', (event) => {
    if (props.list && props.list.length > 0) {
        const inputElement = document.getElementById(props.name)!;

        showList.value = !(event.target !== inputElement && !inputElement?.contains(event.target as Node));
    }
});

window.addEventListener('keydown', (event) => {
    const inputElement = document.getElementById(props.name)!;

    showList.value = !(event.key === 'Escape' && event.target === inputElement);

    // arrow key navigation
    if (showList.value && (event.key === 'ArrowDown' || event.key === 'ArrowUp')) {
        event.preventDefault();
        const items = document.querySelectorAll(`#${props.name} + ul li`);

        if (items.length > 0) {
            let index = Array.from(items).findIndex((item) =>
                item.classList.contains('kern-form-input__input--active')
            );

            index = event.key === 'ArrowDown' ? (index + 1) % items.length : (index - 1 + items.length) % items.length;

            items.forEach((item) => item.classList.remove('kern-form-input__input--active'));
            items[index].classList.add('kern-form-input__input--active');
        }
    }

    if (showList.value && event.key === 'Enter') {
        const activeItem = document.querySelector(`#${props.name} + ul li.kern-form-input__input--active`);
        if (activeItem) {
            selectOption(activeItem.textContent || '');
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
        :disabled="($attrs.disabled as boolean) ?? false"
        style="z-index: 499;"
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
            :disabled="($attrs.disabled as boolean) ?? false"
            :aria-label="ariaLabel"
            @focus="
                $emit('focus', $event);
                showList = true;
            "
            @blur="$emit('blur', $event)"
            @mouseover="$emit('mouseover', $event)"
            @mouseout="$emit('mouseout', $event)"
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
                @mouseover="hoverEffect($event.currentTarget as HTMLElement)"
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

        &.kern-form-input__input--active {
            background-color: #f0f0f0;
        }
    }
}
</style>
