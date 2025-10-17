<script setup lang="ts">
import FormInputLabel from "./FormInputLabel.vue";
const model = defineModel<any>();

interface RadioProps {
  label: string;
  value: any;
}

interface Props {
  name: string;
  label?: string;
  direction?: "horizontal" | "vertical";
  radios: RadioProps[];
  errors?: string;
}

defineProps<Props>();
</script>

<template>
  <div>
    <legend v-if="label" class="kern-fieldset__legend mb-3">
      {{ label }}
    </legend>
    <div
        class="kern-fieldset__content"
        :class="direction === 'horizontal' ? 'kern-fieldset__content--horizontal' : ''"
    >
      <div
          v-for="radio in radios"
          :key="radio.value"
          class="kern-form-check"
      >
        <input
            class="kern-form-check__radio"
            type="radio"
            :id="`${name}-${radio.value}`"
            :name="name"
            :value="radio.value"
            v-model="model"
        />
        <label
            class="kern-form-check__label"
            :for="`${name}-${radio.value}`"
        >
          {{ radio.label }}
        </label>
      </div>
    </div>
  </div>
</template>
