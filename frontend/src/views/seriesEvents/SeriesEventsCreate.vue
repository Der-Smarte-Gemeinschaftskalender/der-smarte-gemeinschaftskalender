<script setup lang="ts">
import zod from '@/lib/zod';

import { computed, ref, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { findSeriesEvent, handleSubmitCallback, loadCreatedEventImageByID, prepareEventsValues } from '@/lib/dsgClient';
import { buildSuggestions, loadMobilizionGroups } from '@/composables/EventCreateFormComposable';
import { formatInputDate, reconstructOptions } from '@/lib/helper';
import {
    intervall_options,
    mobilizon_category_options,
    mobilizon_event_join_options,
    mobilizon_event_language_options,
    mobilizon_event_status,
} from '@/lib/const';

import Button from '@/components/KERN/Button.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import InputText from '@/components/KERN/inputs/InputText.vue';
import InputRichText from '@/components/KERN/inputs/InputRichText.vue';
import InputSelect from '@/components/KERN/inputs/InputSelect.vue';
import InputDate from '@/components/KERN/inputs/InputDate.vue';
import InputTime from '@/components/KERN/inputs/InputTime.vue';
import Alert from '@/components/KERN/Alert.vue';
import Divider from '@/components/KERN/cosmetics/Divider.vue';
import InputUrl from '@/components/KERN/inputs/InputUrl.vue';
import Map from '@/components/Map.vue';
import InputRadios from '@/components/KERN/inputs/InputRadios.vue';

import { MobilizonEventJoinOptions, type Option } from '@/types/General';
import type { RemoveKeys } from '@/types/Generics';
import { addressDefaults, type AddressForm } from '@/types/Mobilizon';
import {
    type SeriesEvent,
    type SeriesEventForm,
    SeriesEventFormSchema,
    SeriesEventSchema,
    SeriesEventsDefaults,
} from '@/types/events/SeriesEvents';
import InputImage from '@/components/KERN/inputs/InputImage.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';
import InputTags from '@/components/InputTags.vue';

const route = useRoute();
const router = useRouter();
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

const onSubmit = handleSubmit(async (values: SeriesEventForm) => {
    const preparedValues: RemoveKeys<SeriesEvent, 'id'> = prepareEventsValues<
        SeriesEventForm,
        RemoveKeys<SeriesEvent, 'id'>
    >(values, ['name', 'start', 'end', 'time', 'intervall', 'duration', 'user_id']);

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

watch(joinOptions, (newValue) => {
    if (newValue !== MobilizonEventJoinOptions.EXTERNAL) externalParticipationUrl.value = undefined;
    else if (!externalParticipationUrl.value) externalParticipationUrl.value = '';
});

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
        />
        .
    </p>
    <form
        novalidate
        @submit.prevent="onSubmit"
    >
        <Alert
            v-if="errorMessageContent.length"
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
                class="mt-3"
                v-model="description"
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
                                name="start"
                                v-model="start"
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
                    <div class="my-5">
                        <InputSelect
                            v-model="intervall"
                            label="Intervall"
                            name="intervall"
                            :options="intervall_options"
                            :errors="submitCount === 0 ? undefined : errors.intervall"
                        />
                    </div>
                </div>
                <div class="md:col-6 lg:col-12 xl:col-6 px-0 mt-2">
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
                            />
                            .
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
                            :disabled="joinOptions === MobilizonEventJoinOptions.FREE"
                            name="externalParticipationUrl"
                            v-model="externalParticipationUrl"
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
                                />
                                .
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
                    @input="updateAddress(rawAddress)"
                    name="physicalAddress"
                    label="Adresse (optional)"
                    :list="mapSuggestions"
                    :errors="submitCount === 0 ? undefined : errors.physicalAddress"
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
