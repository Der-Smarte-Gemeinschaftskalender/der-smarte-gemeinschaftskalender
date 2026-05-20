<script setup lang="ts">

interface Props {
    title?: string;
    severity?: 'info' | 'success' | 'warning' | 'danger';
    bodyClass?: string;
}

defineProps<Props>();
</script>

<template>
    <span :class="`kern-badge kern-badge--${severity ?? 'info'}`">
        <span
            class="kern-badge__title"
            :class="bodyClass"
        >
            <slot>{{ title }}</slot>
        </span>
    </span>
</template>

<style scoped lang="scss"></style>
