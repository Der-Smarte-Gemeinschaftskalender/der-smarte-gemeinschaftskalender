<script setup lang="ts">
import InputWeekDays from './InputWeekDays.vue';
import InputCheckbox from './InputCheckbox.vue';
import { monthlyIntervalWeekOptions } from '@/lib/const';


interface Props {
    label?: string;
    nameWeeks?: string;
    nameWeekDay?: string;
    errors?: string;
    disabled?: boolean;
}

withDefaults(defineProps<Props>(), {
    nameWeeks: 'monthWeeks',
    nameWeekDay: 'monthWeekDay',
    errors: '',
    disabled: false,
    label: '',
});

const weeks = defineModel<number[]>('weeks', { default: () => [1] });
const weekDay = defineModel<number>('weekDay', { default: 0 });


const weekOrder = new Map(monthlyIntervalWeekOptions.map((option, index) => [option.value, index]));

const isWeekSelected = (value: number): boolean => {
    return weeks.value.includes(value);
};

const updateWeek = (value: number, checked: boolean) => {
    if (checked) {
        if (isWeekSelected(value)) return;

        const nextWeeks = [...weeks.value, value];
        weeks.value = nextWeeks.sort((a, b) => (weekOrder.get(a) ?? 0) - (weekOrder.get(b) ?? 0));
        return;
    }

    if (!isWeekSelected(value)) return;
    if (weeks.value.length === 1) return;

    weeks.value = weeks.value.filter((selectedWeek) => selectedWeek !== value);
};
</script>

<template>
    <div class="month-occurrence-selector">
        <label
            v-if="label"
            class="kern-form-input__label"
        >
            {{ label }}
        </label>
        <div class="month-occurrence-content">
            <p class="kern-form-input__label month-occurrence-content__title">
                Woche(n)
            </p>
            <div class="month-occurrence-content__weeks-grid">
                <InputCheckbox
                    v-for="option in monthlyIntervalWeekOptions"
                    :key="option.value"
                    :name="`${nameWeeks}-${option.value}`"
                    :label="option.text"
                    :model-value="isWeekSelected(option.value)"
                    :disabled="disabled"
                    body-class="month-occurrence-content__week-item border border-gray-300 rounded-md bg-gray-100"
                    @update:model-value="(checked) => updateWeek(option.value, checked)"
                />
            </div>
            <InputWeekDays
                v-model="weekDay"
                label="Wochentag"
                class="mt-3"
                :name="nameWeekDay"
                :disabled="disabled"
            />
        </div>
        <p
            v-if="errors"
            class="kern-form-input__error"
        >
            {{ errors }}
        </p>
    </div>
</template>

<style scoped lang="scss">
.month-occurrence-selector {
    .month-occurrence-content {
        margin-top: 0.5rem;

        .month-occurrence-content__title {
            margin-bottom: 0.5rem;
        }

        .month-occurrence-content__weeks-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.5rem 1rem;
        }
    }
}

label.kern-form-input__label {
    font-size: 1rem;
}

:deep(.month-occurrence-content__week-item) {
    margin-bottom: 0;
    padding: 0.4rem 0.6rem;
}
</style>
