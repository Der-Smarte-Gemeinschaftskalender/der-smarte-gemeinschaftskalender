<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import Icon from '@/components/KERN/cosmetics/Icon.vue';

interface Props {
    options: { label: string; value: string | number }[];
    selected?: string | number;
    open?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    open: false,
    selected: null,
});

const isOpen = ref(props.open);
const selectedValue = ref<string | number | null>(props.selected);

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const closeDropdown = () => {
    isOpen.value = false;
};

const openDropdown = () => {
    isOpen.value = true;
};

watch(
    () => props.open,
    (open) => {
        isOpen.value = open;
    }
);

onMounted(() => {
    if (props.open) {
        openDropdown();
    }
});

const emit = defineEmits<{
    select: [value: string | number];
}>();

defineExpose({
    toggleDropdown,
    closeDropdown,
    openDropdown,
});

</script>
<template>
    <ul
        v-if="isOpen"
        class="relative bg-white border-round-sm list-none overflow-hidden shadow-3 w-full"
    >
        <li
            v-for="option in options"
            :key="option.value"
            class="w-full p-2 cursor-pointer flex align-items-center"
            :class="{
                'hover:bg-gray-200': option.value !== selectedValue,
                'hover:bg-blue-200': option.value === selectedValue,
                'bg-blue-100': option.value === selectedValue,
            }"
            @click="
                selectedValue = option.value;
                emit('select', option.value);
                closeDropdown();
            "
        >
            {{ option.label }}

            <Icon
                :name="option.value === selectedValue ? 'check' : 'none'"
                size="sm"
                class="ml-2 text-green-500"
            />
        </li>
    </ul>
</template>
