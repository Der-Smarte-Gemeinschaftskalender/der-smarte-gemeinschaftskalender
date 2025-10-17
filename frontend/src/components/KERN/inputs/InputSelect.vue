<script setup lang="ts">
import FormInputLabel from "./FormInputLabel.vue";
import { type Option } from "@/types/General.ts";
const model = defineModel<any>();

interface Props {
  label?: string;
  name: string;
  options: Option[];
  errors?: string;
  clean?: boolean;
}

defineProps<Props>();
</script>
<template>
  <FormInputLabel :id="name" :label="label" :errors="errors">
    <div
      class="kern-form-input__select-wrapper"
      :class="{
        'border-bottom-none': clean,
        'bg-transparent': clean,
      }"
    >
      <select
        v-model="model"
        class="kern-form-input__select pr-6"
        :class="{
          'kern-form-input__select--error': !!errors,
          'outline-none': clean,
          'bg-transparent': clean,
        }"
        :name="name"
        v-bind="$attrs"
        :aria-describedby="errors ? `${name}-error` : undefined"
      >
        <option
          v-for="option in options"
          :key="option.value"
          :value="option.value"
        >
          {{ option.text }}
        </option>
      </select>
    </div>
  </FormInputLabel>
</template>
<style scoped lang="scss">
</style>