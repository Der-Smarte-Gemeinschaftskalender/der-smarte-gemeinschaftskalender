<script lang="ts" setup>
import { computed } from 'vue';
import Icon from './cosmetics/Icon.vue';

interface Props {
    variant?: 'primary' | 'secondary' | 'tertiary';
    label?: string;
    iconLeft?: string;
    iconSize?: 'sm' | 'md' | 'lg' | 'xl' | undefined;
    hideTextOnMobile?: boolean;
    iconRight?: string;
    bodyClass?: string;
    type?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'primary',
    label: undefined,
    iconLeft: undefined,
    iconRight: undefined,
    type: 'button',
});

const buttonType = computed(() => (props.type === 'submit' || props.type === 'reset' ? props.type : 'button'));
</script>
<template>
    <button
        :type="buttonType"
        :class="`kern-btn kern-btn--${variant}`"
    >
        <Icon
            v-if="iconLeft"
            :name="iconLeft"
            :size="iconSize"
            :color="variant === 'primary' ? 'white' : 'black'"
        />
        <span
            v-if="label?.length || $slots.default"
            class="kern-btn__title"
            :class="[bodyClass, hideTextOnMobile ? 'hidden md:inline' : '']"
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
