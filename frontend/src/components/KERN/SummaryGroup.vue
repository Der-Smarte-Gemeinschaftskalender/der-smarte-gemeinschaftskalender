<script setup lang="ts">
import { defineProps } from "vue";

interface Props {
  title: string;
}

defineProps<Props>();
</script>

<template>
  <div class="kern-summary-group">
    <div class="kern-summary-group__header">
      <h2 class="kern-summary-group__title">
        {{ title }}
      </h2>
    </div>

    <slot />
  </div>
</template>

<style scoped lang="scss"></style>
