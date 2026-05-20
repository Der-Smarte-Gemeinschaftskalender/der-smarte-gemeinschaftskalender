<script setup lang="ts">
import { ref, watch, nextTick, onBeforeUnmount } from 'vue';

interface Props {
    active?: boolean;
    initialFocusSelector?: string;
    restoreFocus?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    active: false,
    restoreFocus: true,
});

const root = ref<HTMLElement | null>(null);
let previouslyFocused: HTMLElement | null = null;

const FOCUSABLE_SELECTOR = [
    'a[href]',
    'button:not([disabled])',
    'textarea:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

const isVisible = (el: HTMLElement) => {
    const style = window.getComputedStyle(el);
    return style.display !== 'none' && style.visibility !== 'hidden';
};

const getFocusableElements = (): HTMLElement[] => {
    if (!root.value) return [];
    return Array.from(root.value.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR)).filter(
        (el) => isVisible(el) && !el.hasAttribute('disabled')
    );
};

const focusInitial = async () => {
    await nextTick();
    if (!root.value) return;

    if (props.initialFocusSelector) {
        const target = root.value.querySelector<HTMLElement>(props.initialFocusSelector);
        if (target) {
            target.focus();
            return;
        }
    }

    const focusables = getFocusableElements();
    if (focusables.length > 0) focusables[0].focus();
    else root.value.focus();
};

const onKeydown = (event: KeyboardEvent) => {
    if (!props.active || event.key !== 'Tab') return;

    const focusables = getFocusableElements();
    if (focusables.length === 0) {
        event.preventDefault();
        root.value?.focus();
        return;
    }

    const first = focusables[0];
    const last = focusables[focusables.length - 1];
    const current = document.activeElement as HTMLElement | null;

    if (event.shiftKey) {
        if (current === first || !root.value?.contains(current)) {
            event.preventDefault();
            last.focus();
        }
    } else {
        if (current === last || !root.value?.contains(current)) {
            event.preventDefault();
            first.focus();
        }
    }
};

watch(
    () => props.active,
    async (active) => {
        if (active) {
            previouslyFocused = document.activeElement as HTMLElement | null;
            await focusInitial();
        } else if (props.restoreFocus) {
            previouslyFocused?.focus?.();
        }
    },
    { immediate: true }
);

onBeforeUnmount(() => {
    if (props.restoreFocus) previouslyFocused?.focus?.();
});
</script>

<template>
    <div
        ref="root"
        tabindex="-1"
        @keydown.capture="onKeydown"
    >
        <slot />
    </div>
</template>
