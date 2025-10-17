<script setup lang="ts">
interface Props {
  forId: string;
  errors?: string;
}

const props = defineProps<Props>();
</script>
<template>
  <p v-if="errors" :id="`${props.forId}-error`" class="kern-error" role="alert">
    <span
      class="kern-icon kern-icon--danger kern-icon--md"
      aria-hidden="true"
    />
    <span class="kern-text">{{ errors }}</span>
  </p>
</template>
