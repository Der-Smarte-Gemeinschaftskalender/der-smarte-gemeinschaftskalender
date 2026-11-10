<script setup lang="ts">
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { seriesEventsHolidaysFilter } from '@/lib/instanceConfig';
import { formatDateTime } from '@/lib/helper';

import InputSelect from '@/components/KERN/inputs/InputSelect.vue';
import InputCheckbox from '@/components/KERN/inputs/InputCheckbox.vue';
import Accordion from '@/components/KERN/Accordion.vue';

interface HolidayEntry {
    date: string;
    fname: string;
}

interface SchoolHolidayEntry {
    start: string;
    end: string;
    name_cp: string;
}

export interface HolidaysPayload {
    holiday: Array<HolidayEntry>;
    school_holiday: Array<SchoolHolidayEntry>;
}

const modelState = defineModel<string>('state', {
    default: seriesEventsHolidaysFilter.state,
});

const holidaysCheck = defineModel<boolean>('holidaysCheck', {
    default: false,
});
const schoolHolidaysCheck = defineModel<boolean>('schoolHolidaysCheck', {
    default: false,
});

const props = defineProps<{
    holidays: HolidaysPayload;
}>();

const { t } = useI18n();

const states = [
    'all_states',
    'bw',
    'by',
    'be',
    'bb',
    'hb',
    'hh',
    'he',
    'mv',
    'ni',
    'nw',
    'rp',
    'sh',
    'sl',
    'sn',
    'st',
    'th',
    'augsburg',
    'none',
].map((value) => ({ value, text: t(`app.shared.holidays.states.${value}`) }));

const holidayItems = computed(() => props.holidays.holiday ?? []);
const schoolHolidayItems = computed(() => props.holidays.school_holiday ?? []);
const hasHolidayResults = computed(() => holidayItems.value.length > 0 || schoolHolidayItems.value.length > 0);
const holidayAccordion = computed(() => [
    {
        header: t('app.shared.holidays.holidaysHeader'),
        open: false,
    },
]);

const schoolHolidayAccordion = computed(() => [
    {
        header: t('app.shared.holidays.schoolHolidaysHeader'),
        open: false,
    },
]);

const toggleState = () => {
    if (modelState.value === 'none' && (holidaysCheck.value || schoolHolidaysCheck.value)) {
        modelState.value = seriesEventsHolidaysFilter.state;
    } 
    else if (!holidaysCheck.value && !schoolHolidaysCheck.value) {
        modelState.value = 'none';
    }
};

watch([holidaysCheck, schoolHolidaysCheck], toggleState);

</script>
<template>
    <section class="flex flex-column gap-4 bg-gray-200 p-4 rounded">
        <p class="font-bold pb-0"> {{ t('app.shared.holidays.noEventsOn') }} </p>
        <div class="flex flex-column sm:flex-row gap-6">
            <InputCheckbox
                v-model="holidaysCheck"
                :name="'holidaysEnabled'"
                :label="t('app.shared.holidays.holidaysCheckbox')"
                body-class="align-items-center"
            />
            <InputCheckbox
                v-model="schoolHolidaysCheck"
                :name="'schoolHolidaysEnabled'"
                :label="t('app.shared.holidays.schoolHolidaysCheckbox')"
                body-class="align-items-center"
            />
        </div>
        <InputSelect
            v-if="holidaysCheck || schoolHolidaysCheck"
            v-model="modelState"
            :label="t('app.shared.holidays.stateLabel')"
            class="w-full"
            :name="'state'"
            :options="states.filter((state) => state.value !== 'none')"
        />
        <div
            v-if="holidaysCheck || schoolHolidaysCheck"
            class="bg-white p-3 border-round"
        >
            <p class="font-semibold m-0 mb-3">{{ t('app.shared.holidays.affectedDays') }}</p>
            <p
                v-if="!hasHolidayResults"
                class="m-0 text-700"
            >
                {{ t('app.shared.holidays.noEntries') }}
            </p>
            <div v-else>
                <Accordion
                    v-if="holidayItems.length > 0"
                    :accordions="holidayAccordion"
                >
                    <template #content-1>
                        <ul class="m-0 pl-3 flex flex-column gap-2">
                            <li
                                v-for="entry in holidayItems"
                                :key="`${entry.date}-${entry.fname}`"
                            >
                                <strong>{{ formatDateTime(entry.date) }}</strong> - {{ entry.fname }}
                            </li>
                        </ul>
                    </template>
                </Accordion>

                <Accordion
                    v-if="schoolHolidayItems.length > 0"
                    :accordions="schoolHolidayAccordion"
                >
                    <template #content-1>
                        <ul class="m-0 pl-3 flex flex-column gap-2">
                            <li
                                v-for="(entry, index) in schoolHolidayItems"
                                :key="`${entry.start}-${entry.end}-${entry.name_cp}-${index}`"
                            >
                                <strong>{{ t('app.shared.holidays.dateRange', { start: formatDateTime(entry.start), end: formatDateTime(entry.end) }) }}</strong>
                                - {{ entry.name_cp }}
                            </li>
                        </ul>
                    </template>
                </Accordion>
            </div>
        </div>
    </section>
</template>
