<script setup lang="ts">
import zod from '@/lib/zod';
import { computed, ref, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { findSingleEvent, handleSubmitCallback, loadCreatedEventImageByID, prepareEventsValues } from '@/lib/dsgClient';
import { formatInputDate, reconstructOptions } from '@/lib/helper';

import { buildSuggestions, loadMobilizionGroups } from '@/composables/EventCreateFormComposable';
import {
    mobilizon_category_options,
    mobilizon_event_join_options,
    mobilizon_event_language_options,
    mobilizon_event_status,
} from '@/lib/const';

import Button from '@/components/KERN/Button.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import InputText from '@/components/KERN/inputs/InputText.vue';
import InputTextarea from '@/components/KERN/inputs/InputTextarea.vue';
import InputSelect from '@/components/KERN/inputs/InputSelect.vue';
import InputDate from '@/components/KERN/inputs/InputDate.vue';
import InputTime from '@/components/KERN/inputs/InputTime.vue';
import InputUrl from '@/components/KERN/inputs/InputUrl.vue';
import Alert from '@/components/KERN/Alert.vue';
import Map from '@/components/Map.vue';
import InputRadios from '@/components/KERN/inputs/InputRadios.vue';
import InputImage from '@/components/KERN/inputs/InputImage.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';

import { MobilizonEventJoinOptions, type Option } from '@/types/General';
import { addressDefaults, type AddressForm } from '@/types/Mobilizon';
import {
    type SingleEventRequest,
    type SingleEventForm,
    SingleEventFormSchema,
    SingleEventResponseSchema,
} from '@/types/events/SingleEvents';
import InputTags from '@/components/InputTags.vue';
import Divider from '@/components/KERN/cosmetics/Divider.vue';

const route = useRoute();
const router = useRouter();
const mobilizionGroupOptions = ref<Option[]>([]);
const errorMessageContent = ref<string>('');
const rawAddress = ref<string>('');
const mapRef = ref<InstanceType<typeof Map> | null>(null);
const isMapLoading = computed(() => mapRef.value?.isLoading ?? false);
const mapSuggestions = computed(() => buildSuggestions(mapRef.value?.suggestions ?? []));

const { handleSubmit, errors, isSubmitting, submitCount } = useForm<SingleEventForm>({
    validationSchema: toTypedSchema(SingleEventFormSchema),
});

const { value: picture } = useField<File | undefined>('picture');
const { value: pictureAlt } = useField<string>('pictureAlt');
const { value: name } = useField<string>('name');
const { value: start } = useField<string>('start');
const { value: time } = useField<string>('time');
const { value: duration } = useField<string>('duration');
const { value: mobilizon_group_id } = useField<number>('mobilizon_group_id');
const { value: description } = useField<string>('description');
const { value: category } = useField<string>('category');
const { value: tags } = useField<string[]>('tags');
const { value: joinOptions } = useField<string>('joinOptions');
const { value: language } = useField<string>('language');
const { value: status } = useField<string>('status');
const { value: onlineAddress } = useField<string>('onlineAddress');
const { value: physicalAddress } = useField<AddressForm>('physicalAddress');
const { value: externalParticipationUrl } = useField<string | undefined>('externalParticipationUrl');

const onSubmit = handleSubmit(async (values) => {
    const preparedValues: SingleEventRequest = prepareEventsValues<SingleEventForm, SingleEventRequest>(values, [
        'name',
        'start',
        'time',
        'duration',
    ]);
    try {
        isSubmitting.value = true;
        errorMessageContent.value = '';

        await handleSubmitCallback('/single-events', {
            headers: { 'Content-Type': 'multipart/form-data' },
            values: preparedValues,
            resDataKey: 'singleEvent',
            schema: SingleEventResponseSchema,
        });

        await router.push({ name: 'singleEvents.index' });
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
        const singleEvent = await findSingleEvent(parsedTemplateEventId);

        picture.value = singleEvent.mobilizon_fields.picture?.url!
            ? (await loadCreatedEventImageByID(singleEvent.created_event.id)) || undefined
            : undefined;
        pictureAlt.value = singleEvent.mobilizon_fields.picture?.alt || '';
        name.value = singleEvent.name;
        description.value = singleEvent.mobilizon_fields.description;
        start.value = formatInputDate(singleEvent?.created_event?.start);
        time.value = singleEvent?.created_event?.time;
        duration.value = singleEvent?.created_event?.duration;
        mobilizon_group_id.value = Number(singleEvent.mobilizon_group_id);
        category.value = singleEvent.mobilizon_fields.category;
        tags.value = singleEvent.mobilizon_fields.tags || [];
        joinOptions.value = singleEvent.mobilizon_fields.joinOptions;
        language.value = singleEvent.mobilizon_fields.language;
        status.value = singleEvent.mobilizon_fields.status;
        onlineAddress.value = singleEvent.mobilizon_fields.onlineAddress || '';
        physicalAddress.value = singleEvent.mobilizon_fields.physicalAddress || addressDefaults;

        if (singleEvent.mobilizon_fields.physicalAddress) {
            rawAddress.value = buildSuggestions([physicalAddress.value])[0];
            physicalAddress.value.description = rawAddress.value;
        }

        externalParticipationUrl.value =
            singleEvent.mobilizon_fields.joinOptions === MobilizonEventJoinOptions.EXTERNAL
                ? singleEvent.mobilizon_fields.externalParticipationUrl || ''
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
    <h1 class="kern-heading text-theme-primary">Einzeltermin erstellen</h1>
    <p class="mt-1 mb-6">
        <b>Hinweis:</b>
        Weitere Informationen finden Sie im
        <LinkToDocs
            path="Terminverwaltung/Einzeltermine/"
            fragment="einzeltermine-erstellen"
        />
        .
    </p>
    <form
        novalidate
        @submit.prevent="onSubmit"
    >
        <Fieldset>
            <Alert
                v-if="errorMessageContent.length"
                title="Fehler"
                :content="errorMessageContent"
                severity="danger"
            />
            <div class="flex flex-column gap-5">
                <InputText
                    v-model="name"
                    label="Titel der Veranstaltung"
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
                <InputTextarea
                    v-model="description"
                    label="Beschreibung (optional)"
                    name="description"
                    :errors="submitCount === 0 ? undefined : errors.description"
                />
                <Divider class="my-5" />
                <InputDate
                    name="start"
                    v-model="start"
                    label="Startdatum"
                    :errors="submitCount === 0 ? undefined : errors.start"
                />
                <InputTime
                    v-model="time"
                    label="Beginn (Uhrzeit)"
                    name="time"
                    :errors="submitCount === 0 ? undefined : errors.time"
                />
                <InputTime
                    v-model="duration"
                    label="Dauer"
                    name="duration"
                    :hours-max="999"
                    :errors="submitCount === 0 ? undefined : errors.duration"
                />
                <Divider class="my-5" />
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
                <Divider class="my-5" />
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
                                <LinkToDocs path="" />
                            </p>
                        </Alert>
                    </div>
                </div>
                <Divider class="my-5" />
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
                Einzeltermin anlegen
            </Button>
        </div>
    </form>
</template>
