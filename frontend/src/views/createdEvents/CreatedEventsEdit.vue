<script lang="ts" setup>
import { computed, ref, watch, onBeforeMount } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { formatDateTime, formatInputDate, reconstructOptions } from '@/lib/helper';
import { dsgApi } from '@/lib/dsgApi';
import { handleSubmitCallback, loadCreatedEventImageByID, prepareEventsValues } from '@/lib/dsgClient';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { buildSuggestions } from '@/composables/EventCreateFormComposable';
import {
    mobilizon_category_options,
    mobilizon_event_join_options,
    mobilizon_event_language_options,
    mobilizon_event_status,
} from '@/lib/const';

import DescriptionList from '@/components/KERN/DescriptionList.vue';
import Button from '@/components/KERN/Button.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import InputText from '@/components/KERN/inputs/InputText.vue';
import InputRichText from '@/components/KERN/inputs/InputRichText.vue';
import InputSelect from '@/components/KERN/inputs/InputSelect.vue';
import InputDate from '@/components/KERN/inputs/InputDate.vue';
import InputTime from '@/components/KERN/inputs/InputTime.vue';
import InputUrl from '@/components/KERN/inputs/InputUrl.vue';
import Alert from '@/components/KERN/Alert.vue';
import Divider from '@/components/KERN/cosmetics/Divider.vue';
import Map from '@/components/Map.vue';
import Loader from '@/components/KERN/cosmetics/Loader.vue';
import ChangeEventStatus from '@/components/ChangeEventStatus.vue';
import EventStatusBadge from '@/components/EventStatusBadge.vue';
import InputRadios from '@/components/KERN/inputs/InputRadios.vue';
import InputImage from '@/components/KERN/inputs/InputImage.vue';
import InputTags from '@/components/InputTags.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';
import DeleteCreatedEvent from '@/components/DeleteCreatedEvent.vue';

import { type DescriptionListData } from '@/types/KERN';
import { type EventTag, MobilizonEventJoinOptions } from '@/types/General';
import {
    type CreatedEventForm,
    type CreatedEventRequest,
    type CreatedEventDetails,
    CreatedEventFormSchema,
} from '@/types/events/CreatedEvents';
import { type AddressForm, type MobilizonFields, addressDefaults } from '@/types/Mobilizon';

const route = useRoute();
const router = useRouter();

const createdEvent = ref<CreatedEventDetails>();
const descriptionListData = ref<DescriptionListData[]>([]);
const loading = ref<boolean>(false);

const createdEventData = ref<CreatedEventDetails | null>(null);
const createdEventMetaData = ref<MobilizonFields | null>(null);
const createdEventStatus = ref(null);
const createdEventType = ref('');
const rawAddress = ref<string>('');
const mapRef = ref<InstanceType<typeof Map> | null>(null);
const isMapLoading = computed(() => mapRef.value?.isLoading ?? false);
const mapSuggestions = computed(() => buildSuggestions(mapRef.value?.suggestions ?? []));

const errorMessageContent = ref<string>('');

const { handleSubmit, errors, isSubmitting, submitCount } = useForm<CreatedEventForm>({
    validationSchema: toTypedSchema(CreatedEventFormSchema),
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
const { value: onlineAddress } = useField<string>('onlineAddress');
const { value: physicalAddress } = useField<AddressForm>('physicalAddress');
const { value: externalParticipationUrl } = useField<string | undefined>('externalParticipationUrl');

const onSubmit = handleSubmit(async (values: CreatedEventForm) => {
    if (!createdEvent.value) {
        console.error('No created event found');
        return;
    }

    const preparedValues: CreatedEventRequest = prepareEventsValues<CreatedEventForm, CreatedEventRequest>(values, [
        'name',
        'start',
        'time',
        'duration',
    ]);
    preparedValues.mobilizon_id = createdEvent.value.mobilizon_id;

    try {
        isSubmitting.value = true;
        errorMessageContent.value = '';

        const data = await handleSubmitCallback(`/created-events/${createdEvent.value.id}`, {
            headers: { 'Content-Type': 'multipart/form-data' },
            values: preparedValues,
            resDataKey: 'createdEvent',
            //schema: CreatedEventSchema,
        });

        await router.push({ name: 'public.event', params: { uuid: data.mobilizon_uuid } });
    } catch (error: any) {
        errorMessageContent.value = error;
    } finally {
        isSubmitting.value = false;
    }
});

const loadCreatedEvent = async () => {
    loading.value = true;

    try {
        const { data } = await dsgApi.get(`/created-events/${route.params.id}`);
        createdEvent.value = data.createdEvent;
        createdEventData.value = data.mobilizon_fields;
        createdEventMetaData.value = data.createdEventMetaData;
        createdEventType.value = data.eventType;

        if (!createdEvent.value && !createdEventMetaData.value) {
            console.error('No valid created event type found');
            return;
        }

        createdEventStatus.value = createdEventData.value!.status;

        descriptionListData.value = [
            {
                name: 'ID',
                value: createdEvent.value!.id,
            },
            {
                name: 'Erstellt am',
                value: formatDateTime(createdEventData.value!.created_at!),
            },
            {
                name: 'Art des Termines',
                value: getEventTypeLabel(createdEventType.value),
            },
            {
                name: 'Erstellt von',
                value: `${createdEvent.value!.user.mobilizon_name}`,
            },
            {
                name: 'Status',
                key: 'status',
                value: createdEventStatus.value,
                slot: true,
            },
        ];

        picture.value = createdEventData.value?.picture?.url
            ? (await loadCreatedEventImageByID(createdEvent.value?.id!)) || undefined
            : undefined;
        pictureAlt.value = createdEventData.value?.picture?.alt || '';
        name.value = createdEventData.value?.title;
        description.value = createdEventData.value?.description;
        start.value = formatInputDate(createdEvent.value?.start);
        time.value = createdEvent.value?.time!;
        duration.value = createdEvent.value?.duration!;
        mobilizon_group_id.value = data.event?.mobilizon_group_id;
        category.value = createdEventData.value?.category;
        tags.value = createdEventData.value?.tags.map((tag: EventTag) => tag.title) || [];
        joinOptions.value = createdEventData.value?.joinOptions;
        language.value = createdEventData.value?.language;
        onlineAddress.value = createdEventData.value?.onlineAddress || '';
        physicalAddress.value = createdEventData.value?.physicalAddress || addressDefaults;

        if (createdEventData.value?.physicalAddress) {
            rawAddress.value = buildSuggestions([createdEventData.value?.physicalAddress])[0];
            physicalAddress.value.description = rawAddress.value;
        }

        externalParticipationUrl.value =
            createdEventData.value?.joinOptions === MobilizonEventJoinOptions.EXTERNAL
                ? createdEventData.value?.externalParticipationUrl || ''
                : undefined;
    } catch (error) {
        console.error('Error loading created event:', error);
    } finally {
        loading.value = false;
    }
};

const updateAddress = (address: string) => {
    physicalAddress.value = {
        ...addressDefaults,
        description: address,
    };
};

const getEventTypeLabel = (eventType: string): string => {
    const types = {
        series_event: 'Serientermin',
        single_event: 'Einzeltermin',
        imported_event: 'Importierter Termin',
        uploaded_event: 'Hochgeladener Termin',
    };

    return types[eventType as keyof typeof types] || 'Unbekannter Termin';
};

watch(joinOptions, (newValue) => {
    if (newValue !== MobilizonEventJoinOptions.EXTERNAL) externalParticipationUrl.value = undefined;
    else if (!externalParticipationUrl.value) externalParticipationUrl.value = '';
});

loadCreatedEvent();
</script>
<template>
    <h2 class="kern-heading text-theme-primary">Einzeltermin bearbeiten</h2>
    <p class="mt-1 mb-6">
        <b>Hinweis:</b>
        Weitere Informationen finden Sie im
        <LinkToDocs
            path="Terminverwaltung/Einzeltermine/"
            fragment="kalender-integrieren-ical-url-anbinden"
        />
        .
    </p>
    <template v-if="loading">
        <Loader />
    </template>
    <template v-else>
        <DescriptionList :data="descriptionListData">
            <template #status>
                <div class="flex flex-column gap-2">
                    <span v-if="!!createdEventStatus">
                        Aktueller Status:
                        <EventStatusBadge
                            :status="createdEventStatus"
                            class="ml-2"
                        />
                    </span>
                    <div
                        v-if="!!createdEvent"
                        class="flex align-items-center gap-2"
                    >
                        <span class="mr-2">Status ändern:</span>
                        <span
                            v-for="{ value } in mobilizon_event_status"
                            :key="value"
                        >
                            <ChangeEventStatus
                                v-if="createdEventStatus !== value"
                                v-model:mobilizon-group-id="mobilizon_group_id"
                                :event-id="createdEvent.id"
                                :status="value"
                                :mobilizon-id="createdEvent.mobilizon_id"
                                @statusChanged="loadCreatedEvent"
                            />
                        </span>
                    </div>
                </div>
            </template>
        </DescriptionList>
    </template>
    <Divider class="mt-4 mb-4" />
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
            <div class="flex flex-column gap-5">
                <InputText
                    v-model="name"
                    label="Titel"
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
                    label="Beschreibung"
                    name="description"
                    :errors="submitCount === 0 ? undefined : errors.description"
                />
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
                :disabled="isSubmitting || isMapLoading"
                class="mt-5"
                type="submit"
            >
                Änderung Speichern
            </Button>
        </div>
    </form>
    <Divider class="mt-3" />
    <div class="text-center pt-3">
        <DeleteCreatedEvent
            v-if="createdEvent?.id"
            :event-id="createdEvent.id"
        />
    </div>
</template>
