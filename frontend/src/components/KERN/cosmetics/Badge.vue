<script setup lang="ts">
import { defineProps } from "vue";

interface Props {
  title?: string;
  severity?: "info" | "success" | "warning" | "danger";
}

defineProps<Props>();
</script>

<template>
  <span :class="`kern-badge kern-badge--${severity ?? 'info'}`">
    <span class="kern-badge__title">
      <slot>{{ title }}</slot>
    </span>
  </span>
</template>

<style scoped lang="scss"></style>
