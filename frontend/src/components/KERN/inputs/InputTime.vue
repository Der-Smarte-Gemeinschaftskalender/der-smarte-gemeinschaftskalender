<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { isValidPositivNumber } from "@/lib/helper";
import FormInputLabel from "./FormInputLabel.vue";

const model = defineModel<string>();

interface Props {
    hoursMax?: number;
    label?: string;
    name: string;
    errors?: string;
    inputsClass?: Array<string>;
}

const props = defineProps<Props>();

const hours = ref<string>('');
const minutes = ref<string>('');

const hoursMax = props.hoursMax ?? 23;
const hoursMaxLength = hoursMax.toString().length;
const leadingZero = hoursMaxLength === 2;

watch(hours, () => {
    // Remove non-digit characters
    hours.value = hours.value.replace(/\D/g, '');

    // Trim to max `maxHours` digits
    if (hours.value.length && hours.value.length > hoursMaxLength) {
        hours.value = hours.value.slice(1, hoursMaxLength + 1);
    }

    // Parse and clamp to max `hoursMax`
    let h = parseInt(hours.value);
    if (hours.value.length && (isNaN(h) || h > hoursMax)) {
        hours.value = hoursMax.toString();
    }

    // Rebuild model without modifying minutes
    hours.value = isValidPositivNumber(hours.value) ? hours.value : '';
    if (leadingZero) hours.value = hours.value.padStart(2, '0');
    else if (hours.value.length > 1 && hours.value.charAt(0) === '0') hours.value = hours.value.slice(1);
    model.value = `${ hours.value }:${ minutes.value }`;
});

watch(minutes, () => {
    // Remove non-digit characters
    minutes.value = minutes.value.replace(/\D/g, '');

    // Trim to max 2 digits
    if (minutes.value.length && minutes.value.length > 2) {
        minutes.value = minutes.value.slice(1, 3);
    }

    // Parse and clamp to max 59
    let m = parseInt(minutes.value);
    if (minutes.value.length && (isNaN(m) || m > 59)) minutes.value = '59';

    // Rebuild model without modifying hours
    minutes.value = isValidPositivNumber(minutes.value) ? minutes.value.padStart(2, '0') : '';
    model.value = `${ hours.value }:${ minutes.value }`;

});

const externalModify = (newValue: string | undefined) => {
    if (newValue) {
        const [h, m] = newValue.split(':');

        hours.value = isValidPositivNumber(h) ? h : '';
        if (leadingZero && hours.value) hours.value = hours.value.padStart(2, '0');

        minutes.value = isValidPositivNumber(m) ? m.padStart(2, '0') : '';
    } else {
        hours.value = '00';
        minutes.value = '00';
    }
}

// External model watcher to sync initial value
watch(model, (newValue) => externalModify(newValue), { immediate: true });
onMounted(() => externalModify(model.value));


</script>
<template>
  <FormInputLabel :id="name" :label="label" :errors="errors">
    <div class="flex gap-2 align-items-center w-full">
      <input
         :class="[
            'kern-form-input__input kern-col',
            { 'kern-form-input__input--error': !!errors },
            inputsClass
          ]"
          type="text"
          placeholder="hh"
          v-model="hours"
          :aria-describedby="errors ? `${ props.name }-error` : undefined"
      />
      <span class="text-gray-500 text-center">:</span>
      <input
          :class="[
            'kern-form-input__input kern-col',
            { 'kern-form-input__input--error': !!errors },
            inputsClass
          ]"
          type="text"
          placeholder="mm"
          v-model="minutes"
          :aria-describedby="errors ? `${ props.name }-error` : undefined"
      />
    </div>
  </FormInputLabel>
</template>
<style scoped lang="scss">
.kern-form-input__input {
  width: 100%;
}
</style>

