<script setup lang="ts">
import zod from '@/lib/zod';

import { dsgApi } from '@/lib/dsgApi';
import { computed, ref, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { findSeriesEvent, handleSubmitCallback, loadCreatedEventImageByID, prepareEventsValues } from '@/lib/dsgClient';
import { buildSuggestions, loadMobilizionGroups } from '@/composables/EventCreateFormComposable';
import { formatInputDate, reconstructOptions } from '@/lib/helper';
import { useTimezoneCheck } from '@/composables/TimezoneComposable';
import { createEventDefaults, seriesEventsHolidaysFilter, seriesEventsDaysControlsEnabled } from '@/lib/instanceConfig';
import {
    intervall_options,
    mobilizon_category_options,
    mobilizon_event_join_options,
    mobilizon_event_language_options,
    mobilizon_event_status,
} from '@/lib/const';
import {
    type SeriesEvent,
    SeriesEventSchema,
    type SeriesEventForm,
    SeriesEventFormSchema,
    SeriesEventsDefaults
} from '@/types/events/SeriesEvents';

import Button from '@/components/KERN/Button.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import InputText from '@/components/KERN/inputs/InputText.vue';
import InputRichText from '@/components/KERN/inputs/InputRichText.vue';
import InputSelect from '@/components/KERN/inputs/InputSelect.vue';
import InputDate from '@/components/KERN/inputs/InputDate.vue';
import InputTime from '@/components/KERN/inputs/InputTime.vue';
import InputWeekDays from '@/components/KERN/inputs/InputWeekDays.vue';
import InputMonthOccurrence from '@/components/KERN/inputs/InputMonthOccurrence.vue';
import Alert from '@/components/KERN/Alert.vue';
import Divider from '@/components/KERN/cosmetics/Divider.vue';
import InputUrl from '@/components/KERN/inputs/InputUrl.vue';
import Map from '@/components/Map.vue';
import InputRadios from '@/components/KERN/inputs/InputRadios.vue';

import { Intervall, MobilizonEventJoinOptions, type Option } from '@/types/General';
import type { RemoveKeys } from '@/types/Generics';
import { addressDefaults, type AddressForm } from '@/types/Mobilizon';
import InputImage from '@/components/KERN/inputs/InputImage.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';
import InputTags from '@/components/InputTags.vue';
import InputCheckbox from '@/components/KERN/inputs/InputCheckbox.vue';
import HolidaysSelect, { HolidaysPayload } from '@/components/HolidaysSelect.vue';


const route = useRoute();
const router = useRouter();
const { t } = useI18n();
const { isMismatch: tzMismatch, detectedTz } = useTimezoneCheck();
const mobilizionGroupOptions = ref<Option[]>([]);
const errorMessageContent = ref<string>('');
const rawAddress = ref<string>('');
const mapRef = ref<InstanceType<typeof Map> | null>(null);
const isMapLoading = computed(() => mapRef.value?.isLoading ?? false);
const mapSuggestions = computed(() => buildSuggestions(mapRef.value?.suggestions ?? []));

const { handleSubmit, errors, isSubmitting, submitCount } = useForm<SeriesEventForm>({
    validationSchema: toTypedSchema(SeriesEventFormSchema),
    initialValues: SeriesEventsDefaults,
});

const { value: picture } = useField<File | undefined>('picture');
const { value: pictureAlt } = useField<string>('pictureAlt');
const { value: name } = useField<string>('name');
const { value: start } = useField<string>('start');
const { value: end } = useField<string>('end');
const { value: time } = useField<string>('time');
const { value: duration } = useField<string>('duration');
const { value: mobilizon_group_id } = useField<number>('mobilizon_group_id');
const { value: description } = useField<string>('description');
const { value: category } = useField<string>('category');
const { value: tags } = useField<string[]>('tags');
const { value: joinOptions } = useField<string>('joinOptions');
const { value: language } = useField<string>('language');
const { value: status } = useField<string>('status');
const { value: intervall } = useField<string>('intervall');
const { value: onlineAddress } = useField<string>('onlineAddress');
const { value: physicalAddress } = useField<AddressForm>('physicalAddress');
const { value: externalParticipationUrl } = useField<string | undefined>('externalParticipationUrl');
const { value: holidays_check } = useField<boolean>('holidays_check');
const { value: school_holidays_check } = useField<boolean>('school_holidays_check');
const { value: holidays_state } = useField<string>('holidays_state');
const { value: weekly_day } = useField<number>('weekly_day');
const { value: monthly_weeks } = useField<number[]>('monthly_weeks');
const { value: monthly_week_day } = useField<number>('monthly_week_day');
const { value: monthly_use_start_date_as_default } = useField<boolean>('monthly_use_start_date_as_default');

const holidaysYearToFetch = ref<number>(start.value ? new Date(start.value).getFullYear() : new Date().getFullYear());
const holidays = ref<HolidaysPayload>({
    holiday: [],
    school_holiday: [],
});

// Set default values, if configured
if (createEventDefaults.category) {
    category.value = createEventDefaults.category;
}

const onSubmit = handleSubmit(async (values: SeriesEventForm) => {
    const preparedValues: RemoveKeys<SeriesEvent, 'id'> = prepareEventsValues<
        SeriesEventForm,
        RemoveKeys<SeriesEvent, 'id'>
    >(values, ['name', 'start', 'end', 'time', 'intervall', 'duration', 'user_id', 'holidays_check', 'school_holidays_check', 'holidays_state', 'weekly_day', 'monthly_weeks', 'monthly_week_day', 'monthly_use_start_date_as_default']);

    try {
        isSubmitting.value = true;
        errorMessageContent.value = '';

        const data = await handleSubmitCallback('/series-events', {
            headers: { 'Content-Type': 'multipart/form-data' },
            values: preparedValues,
            schema: SeriesEventSchema,
            resDataKey: 'seriesEvent',
        });

        await router.push({ name: 'seriesEvents.show', params: { id: data.id } });
    } catch (error: any) {
        errorMessageContent.value = error;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } finally {
        isSubmitting.value = false;
    }
});

const createFromTemplate = async () => {
    const { templateEventId } = route.query;

    if (!templateEventId) return;

    const parsedTemplateEventId = zod.string().parse(templateEventId);
    try {
        const seriesEvent = await findSeriesEvent(parsedTemplateEventId);

        picture.value =
            seriesEvent.mobilizon_fields.picture?.url && seriesEvent.created_events.length
                ? (await loadCreatedEventImageByID(seriesEvent.created_events[0].id)) || undefined
                : undefined;
        pictureAlt.value = seriesEvent.mobilizon_fields.picture?.alt || '';
        name.value = seriesEvent.name;
        description.value = seriesEvent.mobilizon_fields.description || '';
        start.value = formatInputDate(seriesEvent.start);
        end.value = formatInputDate(seriesEvent.end);
        time.value = seriesEvent.time;
        duration.value = seriesEvent.duration;
        intervall.value = seriesEvent.intervall;
        mobilizon_group_id.value = Number(seriesEvent.mobilizon_group_id);
        category.value = seriesEvent.mobilizon_fields.category;
        tags.value = seriesEvent.mobilizon_fields.tags || [];
        joinOptions.value = seriesEvent.mobilizon_fields.joinOptions;
        language.value = seriesEvent.mobilizon_fields.language;
        status.value = seriesEvent.mobilizon_fields.status;
        onlineAddress.value = seriesEvent.mobilizon_fields.onlineAddress || '';
        physicalAddress.value = seriesEvent.mobilizon_fields.physicalAddress || addressDefaults;
        holidays_check.value = seriesEvent.holidays_check ?? holidays_check.value;
        school_holidays_check.value = seriesEvent.school_holidays_check ?? school_holidays_check.value;
        holidays_state.value = seriesEvent.holidays_state ?? holidays_state.value;
        weekly_day.value = seriesEvent.weekly_day ?? weekly_day.value;
        monthly_weeks.value = seriesEvent.monthly_weeks ?? monthly_weeks.value;
        monthly_week_day.value = seriesEvent.monthly_week_day ?? monthly_week_day.value;
        monthly_use_start_date_as_default.value = seriesEvent.monthly_use_start_date_as_default ?? monthly_use_start_date_as_default.value;

        if (seriesEvent.mobilizon_fields.physicalAddress) {
            rawAddress.value = buildSuggestions([physicalAddress.value])[0];
            physicalAddress.value.description = rawAddress.value;
        }

        externalParticipationUrl.value =
            seriesEvent.mobilizon_fields.joinOptions === MobilizonEventJoinOptions.EXTERNAL
                ? seriesEvent.mobilizon_fields.externalParticipationUrl || ''
                : undefined;
    } catch (error) {
        console.error(error);
    }
};

const updateAddress = (address: string) => {
    physicalAddress.value = {
        ...addressDefaults,
        description: address,
    };
};

const loadHolidays = async () => {
    if (!seriesEventsHolidaysFilter.enabled) return;

    try {
        const { data } = await dsgApi.get('/series-events/holidays/', {
            params: {
                holidays_check: holidays_check.value,
                school_holidays_check: school_holidays_check.value,
                state: holidays_state.value,
                year: holidaysYearToFetch.value,
            },
        });

        holidays.value = data.holidays ?? data;
    } catch (error) {
        console.error('Error loading holidays:', error);
    }
};

watch([holidays_check, school_holidays_check, holidays_state, holidaysYearToFetch], loadHolidays, { immediate: true });
watch(joinOptions, (newValue) => {
    if (newValue !== MobilizonEventJoinOptions.EXTERNAL) externalParticipationUrl.value = undefined;
    else if (!externalParticipationUrl.value) externalParticipationUrl.value = '';
});

let holidaysDebounceTimeout: number | undefined; 
watch(
    () => start.value,
    (newStart) => {
        clearTimeout(holidaysDebounceTimeout);
        holidaysDebounceTimeout = window.setTimeout(() => {
            const newStartYear = newStart ? new Date(newStart).getFullYear() : new Date().getFullYear();

            if (newStartYear !== holidaysYearToFetch.value) {
                holidaysYearToFetch.value = newStartYear;
            }
        }, 500);
    }
);

createFromTemplate();
loadMobilizionGroups(mobilizon_group_id, mobilizionGroupOptions);
</script>
<template>
    <h1 class="kern-heading text-theme-primary">Serientermin erstellen</h1>
    <p class="mt-1 mb-6">
        <b>Hinweis:</b>
        Serientermine eignen sich für wiederkehrende Veranstaltungen mit gleichbleibender Uhrzeit und Dauer.Weitere
        Informationen finden Sie im
        <LinkToDocs
            path="Terminverwaltung/Serientermine/"
            fragment="serientermin-erstellen"
        />.
    </p>
    <form
        novalidate
        @submit.prevent="onSubmit"
    >
        <Alert
            v-if="errorMessageContent.length"
            class="mb-4"
            title="Fehler"
            :content="errorMessageContent"
            severity="danger"
        />
        <Fieldset>
            <InputText
                v-model="name"
                label="Titel des Serientermins"
                name="name"
                :errors="submitCount === 0 ? undefined : errors.name"
            />
            <InputImage
                v-model="picture"
                v-model:alt="pictureAlt"
                label="Bild (optional)"
                name="picture"
                :accept="['image/gif', 'image/png', 'image/jpeg', 'image/webp']"
                :errors="submitCount === 0 ? undefined : errors.picture"
            />
            <InputRichText
                v-model="description"
                class="mt-3"
                label="Beschreibung"
                name="description"
                :errors="submitCount === 0 ? undefined : errors.description"
            />
            <Divider class="mt-5 mb-5" />
            <div class="flex flex-column md:flex-row lg:flex-column xl:flex-row gap-6">
                <div class="w-full gap-4">
                    <div class="kern-row mb-2">
                        <div class="kern-col">
                            <InputDate
                                v-model="start"
                                name="start"
                                label="Startdatum"
                                :errors="submitCount === 0 ? undefined : errors.start"
                            />
                        </div>
                        <div class="kern-col-1 center-dash"><span>—</span></div>
                        <div class="kern-col">
                            <InputDate
                                v-model="end"
                                label="Enddatum"
                                name="end"
                                :errors="submitCount === 0 ? undefined : errors.end"
                            />
                        </div>
                    </div>
                    <div class="my-5 kern-row">
                        <HolidaysSelect
                            v-if="seriesEventsHolidaysFilter.enabled"
                            v-model:holidaysCheck="holidays_check"
                            v-model:schoolHolidaysCheck="school_holidays_check"
                            v-model:state="holidays_state"
                            :holidays="holidays"
                            :errors="submitCount === 0 ? undefined : errors.start"
                        />
                        <InputTime
                            v-model="time"
                            label="Beginn (Uhrzeit)"
                            name="time"
                            class="kern-col px-0-75rem"
                            :inputs-class="['px-2']"
                            :leading-zero="true"
                            :errors="submitCount === 0 ? undefined : errors.time"
                        />
                        <div class="kern-col-1 center-dash"><span>—</span></div>
                        <InputTime
                            v-model="duration"
                            label="Dauer"
                            name="duration"
                            class="kern-col px-0-75rem"
                            :inputs-class="['px-2']"
                            :hours-max="999"
                            :errors="submitCount === 0 ? undefined : errors.duration"
                        />
                    </div>
                    <p class="kern-text kern-text--small text-color-secondary">
                        {{ t('public.timezoneFormHint.stored') }}
                    </p>
                    <Alert
                        v-if="tzMismatch"
                        severity="warning"
                        :title="$t('public.timezoneNotice.title')"
                        :content="t('public.timezoneFormHint.browserMismatch', { detectedTz })"
                    />
                    <div class="my-5">
                        <InputSelect
                            v-model="intervall"
                            label="Intervall"
                            name="intervall"
                            :options="intervall_options"
                            :errors="submitCount === 0 ? undefined : errors.intervall"
                        />
                    </div>
                    <div 
                        v-if="intervall === Intervall.WEEKLY && seriesEventsDaysControlsEnabled"
                        class="my-5"
                    >
                        <InputWeekDays
                            v-model="weekly_day"
                            label="Wochentag"
                            name="weekDays"
                        />
                    </div>                   
                    <div 
                        v-if="intervall === Intervall.MONTHLY && seriesEventsDaysControlsEnabled"
                        class="my-5"
                    >
                        <InputMonthOccurrence
                            v-model:weeks="monthly_weeks"
                            v-model:week-day="monthly_week_day"
                            :disabled="monthly_use_start_date_as_default"
                            name-weeks="monthly_weeks"
                            name-week-day="monthly_week_day"
                            class="mb-4"
                        />
                        <InputCheckbox
                            v-model="monthly_use_start_date_as_default"
                            label="Tag des Monats aus dem Startdatum übernehmen"
                            name="monthly_use_start_date_as_default"
                        />
                    </div>                                    
                </div>
                <div class="md:col-5 lg:col-12 xl:col-5 px-0 mt-2">
                    <Alert
                        title="Information"
                        severity="info"
                    >
                        <p>
                            Das Start- und Enddatum beziehen sich auf den Zeitraum der gesamten Terminserie. Beginn und
                            Dauer werden für alle Termine der Terminserie übernommen.
                        </p>
                        <p>
                            Weitere Informationen finden Sie im
                            <LinkToDocs
                                path="Terminverwaltung/Serientermine/"
                                fragment="zeitraum-und-intervall-festlegen"
                            />.
                        </p>
                    </Alert>
                </div>
            </div>

            <Divider class="my-5" />

            <div class="flex flex-column gap-5">
                <InputSelect
                    v-model="mobilizon_group_id"
                    label="Organisation"
                    name="mobilizon_group_id"
                    :options="mobilizionGroupOptions"
                    :errors="submitCount === 0 ? undefined : errors.mobilizon_group_id"
                    disabled
                />
                <InputSelect
                    v-model="category"
                    label="Kategorie"
                    name="category"
                    :options="mobilizon_category_options"
                    :errors="submitCount === 0 ? undefined : errors.category"
                />
                <InputTags v-model="tags" />
                <div class="flex flex-column md:flex-row gap-6">
                    <div class="w-full flex flex-column gap-5">
                        <InputRadios
                            v-model="joinOptions"
                            label="Anmeldeoptionen"
                            name="joinOptions"
                            :radios="reconstructOptions(mobilizon_event_join_options)"
                            :errors="submitCount === 0 ? undefined : errors?.joinOptions"
                        />

                        <InputUrl
                            v-model="externalParticipationUrl"
                            :disabled="joinOptions === MobilizonEventJoinOptions.FREE"
                            name="externalParticipationUrl"
                            label="Externe Anmeldeseite (URL)"
                            :errors="submitCount === 0 ? undefined : errors.externalParticipationUrl"
                        />
                    </div>
                    <div class="md:col-6 px-0 mt-2">
                        <Alert
                            title="Information"
                            severity="info"
                        >
                            <p>
                                Wenn Sie eine externe Anmeldeseite verwenden, geben Sie hier den entsprechenden Link an.
                                Wenn Sie keine Online-Anmeldung nutzen, informieren Sie die Teilnehmer*innen in der
                                Veranstaltungsbeschreibung über das Anmeldeverfahren und ob eine Anmeldung erforderlich
                                ist.
                            </p>
                            <p>
                                Weitere Informationen finden Sie im
                                <LinkToDocs
                                    path="Terminverwaltung/Einzeltermine/"
                                    fragment="beitrittsoptionen"
                                />.
                            </p>
                        </Alert>
                    </div>
                </div>
                <InputSelect
                    v-model="language"
                    label="Sprache"
                    name="language"
                    :options="mobilizon_event_language_options"
                    :errors="submitCount === 0 ? undefined : errors.language"
                />
                <InputSelect
                    v-model="status"
                    label="Status"
                    name="status"
                    :options="mobilizon_event_status"
                    :errors="submitCount === 0 ? undefined : errors.status"
                />
                <InputUrl
                    v-model="onlineAddress"
                    label="Webseite (optional)"
                    name="onlineAddress"
                    :errors="submitCount === 0 ? undefined : errors.onlineAddress"
                />
                <InputText
                    v-model="rawAddress"
                    name="physicalAddress"
                    label="Adresse (optional)"
                    :list="mapSuggestions"
                    :errors="submitCount === 0 ? undefined : errors.physicalAddress"
                    @input="updateAddress(rawAddress)"
                />
            </div>
        </Fieldset>
        <Map
            ref="mapRef"
            :search-value="rawAddress"
            :physical-address="physicalAddress"
            class="my-5"
        />
        <div class="flex justify-content-center">
            <Button
                type="submit"
                icon-left="add"
                :disabled="isSubmitting || isMapLoading"
            >
                Serientermin anlegen
            </Button>
        </div>
    </form>
</template>
<style scoped lang="scss">
.center-dash {
    display: flex;
    align-items: center;
    padding-top: 35px;
    text-align: center;
    span {
        width: 100%;
    }
}
</style>
