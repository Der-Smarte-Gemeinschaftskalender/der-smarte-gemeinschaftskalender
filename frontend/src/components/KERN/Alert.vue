<script setup lang="ts">
import { defineProps } from "vue";

interface Props {
  title: string;
  content?: string;
  severity?: "info" | "success" | "warning" | "danger";
}

defineProps<Props>();
</script>

<template>
  <div :class="`kern-alert kern-alert--${severity ?? 'info'}`" role="alert">
    <div class="kern-alert__heading">
      <span
        :class="`kern-icon kern-icon--${severity ?? 'info'}`"
        aria-hidden="true"
      />
      <span class="kern-alert__title"> {{ title }} </span>
    </div>
    <div v-if="content || $slots.default" class="kern-alert__content">
      <p class="kern-text">
        <slot> {{ content }} </slot>
      </p>
    </div>
  </div>
</template>

<style scoped lang="scss"></style>
