<script setup lang="ts">
defineProps({
  screenReaderText: {
    type: String,
    default: "Wird geladen...",
  },
});
</script>
<template>
  <div class="kern-loader kern-loader--visible" role="status">
    <span class="kern-sr-only">{{ screenReaderText }}</span>
  </div>
</template>
