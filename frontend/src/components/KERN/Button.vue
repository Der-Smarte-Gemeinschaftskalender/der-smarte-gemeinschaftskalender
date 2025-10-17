<script lang="ts" setup>
import { defineProps, withDefaults } from 'vue';

import Icon from './cosmetics/Icon.vue';

interface Props {
    variant?: 'primary' | 'secondary' | 'tertiary';
    label?: string;
    iconLeft?: string;
    iconSize?: "sm" | "md" | "lg" | "xl" | undefined;
    hideTextOnMobile?: boolean;
    iconRight?: string;
    bodyClass?: string;
}

withDefaults(defineProps<Props>(), {
    variant: 'primary',
    label: undefined,
    iconLeft: undefined,
    iconRight: undefined,
});

</script>
<template>
    <button :class="`kern-btn kern-btn--${ variant }`">
        <Icon
            v-if="iconLeft"
            :name="iconLeft"
            :size="iconSize"
            :color="variant === 'primary' ? 'white' : 'black'"
        />
        <span
            v-if="label?.length || $slots.default"
            class="kern-btn__title"
            :class="[
                bodyClass,
                hideTextOnMobile ? 'hidden md:inline' : ''
            ]"
        >
            <slot>
                {{ label }}
            </slot>
        </span>
        <Icon
            v-if="iconRight"
            :name="iconRight"
            :size="iconSize"
            :color="variant === 'primary' ? 'white' : 'black'"
        />
    </button>
</template>
