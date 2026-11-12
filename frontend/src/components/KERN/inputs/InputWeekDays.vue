<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import Button from '@/components/KERN/Button.vue';
import { translateMessage } from '@/i18n';
import { weekDayOptions } from '@/lib/const';

interface Props {
    label?: string;
    name: string;
    errors?: string;
    disabled?: boolean;
}

const selectedDay = defineModel<number | null>({ default: null });

const props = defineProps<Props>();
const { t } = useI18n();

const today = new Date().getDay();
selectedDay.value = selectedDay.value === null ? today : selectedDay.value;

const toggleDay = (dayValue: number) => {
    selectedDay.value = dayValue;
};

const isSelected = (dayValue: number) => {
    return selectedDay.value === dayValue;
};
</script>

<template>
    <div class="week-days-selector">
        <label
            v-if="label"
            :for="name"
            class="kern-form-input__label"
        >
            {{ label }}
        </label>
        <div 
            class="week-days-grid"
            role="group"
            :aria-label="label || t('app.shared.weekDays.select')"
        >
            <Button
                v-for="day in weekDayOptions"
                :key="day.value"
                type="button"
                :variant="isSelected(day.value) ? 'primary' : 'secondary'"
                :class="[
                    'week-day-button kern-form-input__button',
                ]"
                :aria-label="day.label"
                :aria-selected="isSelected(day.value)"
                :disabled="disabled"
                @click="toggleDay(day.value)"
            >
                <span class="week-day-button__short">{{ day.shortLabel }}</span>

            </Button>
        </div>
        <p
            v-if="errors"
            :id="`${name}-error`"
            class="kern-form-input__error"
        >
            {{ translateMessage(errors) }}
        </p>
    </div>
</template>
<style scoped lang="scss">
label {
    font-size: 1rem;
}

.week-days-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, 3rem);
    gap: 0.5rem;
    margin-top: 0.5rem;
    width: fit-content;
    max-width: 100%;
}

.week-day-button {
    transition: all 0.1s ease-in-out;
    padding: 0;
    aspect-ratio: 1 / 1;
}
</style>
