<script setup lang="ts">
import FormInputLabel from "./FormInputLabel.vue";
const model = defineModel<number>();

interface Props {
  label?: string;
  name: string;
  errors?: string;
}

const props = defineProps<Props>();
</script>
<template>
  <FormInputLabel :id="name" :label="label" :errors="errors">
    <input
      :id="name"
      v-model="model"
      class="kern-form-input__input"
      :class="{ 'kern-form-input__input--error': !!errors }"
      :name="name"
      type="text"
      inputmode="numeric"
      v-bind="$attrs"
      :aria-describedby="errors ? `${props.name}-error` : undefined"
    />
  </FormInputLabel>
</template>
