<script setup lang="ts">
interface Props {
  legend?: string;
}

defineProps<Props>();
</script>
<template>
  <fieldset class="kern-fieldset">
    <legend v-if="legend" class="kern-fieldset__legend">
      {{ legend }}
    </legend>
    <div class="kern-fieldset__content">
      <slot />
    </div>
  </fieldset>
</template>
