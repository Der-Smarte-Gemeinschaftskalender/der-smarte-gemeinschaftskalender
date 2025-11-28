<script setup lang="ts">
import { watch } from "vue";

import FormInputLabel from "./FormInputLabel.vue";


const model = defineModel<string>();

interface Props {
  label?: string;
  name: string;
  errors?: string;
}

const props = defineProps<Props>();


watch(
    () => model.value,
    (newValue) => {
        if (newValue) model.value = newValue.replace(/\s+/g, '');
    }
);
</script>
<template>
  <FormInputLabel :id="name" :label="label" :errors="errors">
    <input
      :id="name"
      v-model="model"
      class="kern-form-input__input"
      :class="{ 'kern-form-input__input--error': !!errors }"
      :name="name"
      type="password"
      :disabled="$attrs.disabled as boolean ?? false"
      :required="$attrs.required as boolean ?? false"
      :aria-describedby="errors ? `${props.name}-error` : undefined"
    />
  </FormInputLabel>
</template>
