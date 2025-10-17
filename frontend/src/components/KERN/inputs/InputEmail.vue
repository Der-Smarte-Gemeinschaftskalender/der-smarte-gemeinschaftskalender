<script setup lang="ts">
import FormInputLabel from "./FormInputLabel.vue";
const model = defineModel<string>();

interface Props {
  label?: string;
  name: string;
  errors?: string;
  required?: boolean;
}

defineProps<Props>();

</script>
<template>
  <FormInputLabel :id="name" :label="label" :errors="errors" :disabled="$attrs.disabled as boolean ?? false">
    <input
      :id="name"
      v-model="model"
      class="kern-form-input__input"
      :required="required"
      :class="{ 'kern-form-input__input--error': !!errors }"
      :name="name"
      type="email"
      v-bind="$attrs"
      :aria-describedby="errors ? `${name}-error` : undefined"
    />
  </FormInputLabel>
</template>
