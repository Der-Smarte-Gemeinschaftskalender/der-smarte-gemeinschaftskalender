<script lang="ts" setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { I18nT, useI18n } from 'vue-i18n';
import { formatDateTime, formatInputDate, preventImplicitSubmit, reconstructOptions } from '@/lib/helper';
import { dsgApi } from '@/lib/dsgApi';
import { handleSubmitCallback, loadCreatedEventImageByID, prepareEventsValues } from '@/lib/dsgClient';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { isStrictModeEnabled } from '@/lib/instanceConfig';
import { INSTANCE_TIMEZONE } from '@/lib/timezone';
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
import InputTimezone from '@/components/KERN/inputs/InputTimezone.vue';
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
import AddressInput from '@/components/AddressInput.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';
import DeleteCreatedEvent from '@/components/DeleteCreatedEvent.vue';

import { type DescriptionListData } from '@/types/KERN';
import { type EventTag, MobilizonEventJoinOptions } from '@/types/General';
import {
    type CreatedEventForm,
    type CreatedEventRequest,
    type CreatedEventDetails,
    CreatedEventFormSchema,
    CreatedEventUpdateResponseSchema,
} from '@/types/events/CreatedEvents';
import { type AddressForm, type MobilizonFields, addressDefaults } from '@/types/Mobilizon';

const route = useRoute();
const router = useRouter();
const { t } = useI18n();

const createdEvent = ref<CreatedEventDetails>();
const descriptionListData = ref<DescriptionListData[]>([]);
const loading = ref<boolean>(false);

const createdEventData = ref<CreatedEventDetails | null>(null);
const createdEventMetaData = ref<MobilizonFields | null>(null);
const createdEventStatus = ref(null);
const parentEventType = ref('');
const parentEventId = ref<any | null>(null);
const rawAddress = ref<string>('');
const mapRef = ref<InstanceType<typeof Map> | null>(null);
const isMapLoading = computed(() => mapRef.value?.isLoading ?? false);
const mapSuggestions = computed(() => buildSuggestions(mapRef.value?.suggestions ?? []));

const errorMessageContent = ref<string>('');
const showApprovalRequestSuccessMessage = ref<boolean>(false);
const pictureLoadFailed = ref<boolean>(false);

const { handleSubmit, errors, isSubmitting, submitCount } = useForm<CreatedEventForm>({
    validationSchema: toTypedSchema(CreatedEventFormSchema),
});

const { value: picture } = useField<File | undefined>('picture');
const { value: pictureAlt } = useField<string>('pictureAlt');
const { value: name } = useField<string>('name');
const { value: start } = useField<string>('start');
const { value: time } = useField<string>('time');
const { value: duration } = useField<string>('duration');
const { value: timezone } = useField<string>('timezone');
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
    // Bild nur löschen, wenn es der Nutzer entfernt hat – nicht, wenn es
    // lediglich nicht in das Formular geladen werden konnte.
    preparedValues.picture_removed =
        !!createdEventData.value?.picture?.url && !values.picture && !pictureLoadFailed.value;

    try {
        isSubmitting.value = true;
        errorMessageContent.value = '';

        const data = await handleSubmitCallback(`/created-events/${createdEvent.value.id}`, {
            headers: { 'Content-Type': 'multipart/form-data' },
            values: preparedValues,
            resDataKey: 'createdEvent',
            schema: CreatedEventUpdateResponseSchema,
        });

        if (isStrictModeEnabled) {
            showApprovalRequestSuccessMessage.value = true;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            await router.push({ name: 'public.event', params: { uuid: data.mobilizon_uuid } });
        }
    } catch (error: any) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        errorMessageContent.value = error ?? t('app.events.created.edit.saveError');
    } finally {
        isSubmitting.value = false;
    }
});

const loadCreatedEvent = async () => {
    loading.value = true;

    try {
        const { data } = await dsgApi.get(`/created-events/${route.params.id}`, {
            params: { 
                event_id: route.params.id,
            }
        });
        createdEvent.value = data.createdEvent;
        createdEventData.value = data.mobilizon_fields;
        createdEventMetaData.value = data.createdEventMetaData;
        parentEventType.value = data.eventType;
        parentEventId.value = data.event?.id ?? null;

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
                name: t('app.events.columns.createdAt'),
                value: formatDateTime(createdEventData.value!.created_at!),
            },
            {
                name: t('app.events.columns.eventType'),
                value: getEventTypeLabel(parentEventType.value),
            },
            {
                name: t('app.events.columns.createdBy'),
                value: `${createdEvent.value!.user.mobilizon_name}`,
            },
            {
                name: t('app.events.columns.status'),
                key: 'status',
                value: createdEventStatus.value,
                slot: true,
            },
        ];

        pictureLoadFailed.value = false;
        picture.value = createdEventData.value?.picture?.url
            ? (await loadCreatedEventImageByID(createdEvent.value?.id!)) || undefined
            : undefined;
        pictureLoadFailed.value = !!createdEventData.value?.picture?.url && !picture.value;
        pictureAlt.value = createdEventData.value?.picture?.alt || '';
        name.value = createdEventData.value?.title;
        description.value = createdEventData.value?.description;
        start.value = formatInputDate(createdEvent.value?.start);
        time.value = createdEvent.value?.time!;
        duration.value = createdEvent.value?.duration!;
        timezone.value = data.mobilizon_fields?.options?.timezone || INSTANCE_TIMEZONE;
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

const getEventTypeLabel = (eventType: string): string => {
    const types = ['series_event', 'single_event', 'imported_event', 'uploaded_event'];

    return types.includes(eventType) ? t(`app.shared.eventTypes.${eventType}`) : t('app.shared.eventTypes.unknown');
};

watch(joinOptions, (newValue) => {
    if (newValue !== MobilizonEventJoinOptions.EXTERNAL) externalParticipationUrl.value = undefined;
    else if (!externalParticipationUrl.value) externalParticipationUrl.value = '';
});

loadCreatedEvent();
</script>
<template>
    <h1 class="kern-heading text-theme-primary">{{ t('app.events.created.edit.title') }}</h1>
    <p class="mt-1 mb-6">
        <b>{{ $t('app.shared.hint') }}</b> <I18nT scope="global" keypath="app.shared.moreInfoInDocs">
            <template #link>
                <LinkToDocs
                    path="Terminverwaltung/Einzeltermine/"
                    fragment="kalender-integrieren-ical-url-anbinden"
                />
            </template>
        </I18nT>
    </p>
    <Alert
        v-if="errorMessageContent.length"
        class="mb-4"
        :title="$t('common.error')"
        :content="errorMessageContent"
        severity="danger"
    />
    <Alert
        v-else-if="showApprovalRequestSuccessMessage"
        class="mb-4"
        :title="t('app.events.created.edit.approvalRequestTitle')"
        :content="t('app.events.created.edit.approvalRequestContent')"
        severity="info"
    />
    <template v-if="loading">
        <Loader />
    </template>
    <template v-else>
        <DescriptionList :data="descriptionListData">
            <template #status>
                <div class="flex flex-column gap-2">
                    <span v-if="!!createdEventStatus">
                        {{ t('app.events.created.edit.currentStatus') }}
                        <EventStatusBadge
                            :status="createdEventStatus"
                            class="ml-2"
                        />
                    </span>
                    <div
                        v-if="!!createdEvent"
                        class="flex align-items-center gap-2"
                    >
                        <span class="mr-2">{{ t('app.events.created.edit.changeStatus') }}</span>
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
                                @status-changed="loadCreatedEvent"
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
        @keydown.enter="preventImplicitSubmit"
    >
        <Fieldset>
            <div class="flex flex-column gap-5">
                <InputText
                    v-model="name"
                    :label="$t('app.events.form.title')"
                    name="name"
                    :errors="submitCount === 0 ? undefined : errors.name"
                />
                <Alert
                    v-if="pictureLoadFailed"
                    :title="t('app.events.created.edit.pictureLoadFailedTitle')"
                    :content="t('app.events.created.edit.pictureLoadFailedContent')"
                    severity="warning"
                />
                <InputImage
                    v-model="picture"
                    v-model:alt="pictureAlt"
                    :label="$t('app.events.form.picture')"
                    name="picture"
                    :accept="['image/gif', 'image/png', 'image/jpeg', 'image/webp']"
                    :errors="submitCount === 0 ? undefined : errors.picture"
                />
                <InputRichText
                    v-model="description"
                    :label="$t('app.events.form.description')"
                    name="description"
                    :errors="submitCount === 0 ? undefined : errors.description"
                />
                <InputDate
                    v-model="start"
                    name="start"
                    :label="$t('app.events.form.startDate')"
                    :errors="submitCount === 0 ? undefined : errors.start"
                />
                <InputTime
                    v-model="time"
                    :label="$t('app.events.form.startTime')"
                    name="time"
                    :errors="submitCount === 0 ? undefined : errors.time"
                />
                <InputTime
                    v-model="duration"
                    :label="$t('app.events.form.duration')"
                    name="duration"
                    :hours-max="999"
                    :errors="submitCount === 0 ? undefined : errors.duration"
                />
                <InputTimezone
                    v-model="timezone"
                    :errors="submitCount === 0 ? undefined : errors.timezone"
                />
                <p class="kern-text kern-text--small text-color-secondary">
                    {{ $t('public.timezoneFormHint.stored', { timezone }) }}
                </p>
                <InputSelect
                    v-model="category"
                    :label="$t('app.events.form.category')"
                    name="category"
                    :options="mobilizon_category_options"
                    :errors="submitCount === 0 ? undefined : errors.category"
                />
                <InputTags v-model="tags" />
                <div class="flex flex-column md:flex-row gap-6">
                    <div class="w-full flex flex-column gap-5">
                        <InputRadios
                            v-model="joinOptions"
                            :label="$t('app.events.form.joinOptions')"
                            name="joinOptions"
                            :radios="reconstructOptions(mobilizon_event_join_options)"
                            :errors="submitCount === 0 ? undefined : errors?.joinOptions"
                        />

                        <InputUrl
                            v-model="externalParticipationUrl"
                            :disabled="joinOptions === MobilizonEventJoinOptions.FREE"
                            name="externalParticipationUrl"
                            :label="$t('app.events.form.externalParticipationUrl')"
                            :errors="submitCount === 0 ? undefined : errors.externalParticipationUrl"
                        />
                    </div>
                    <div class="md:col-6 px-0 mt-2">
                        <Alert
                            :title="$t('app.shared.information')"
                            severity="info"
                        >
                            <p>
                                {{ $t('app.events.form.joinOptionsInfo') }}
                            </p>
                            <p>
                                <I18nT scope="global" keypath="app.shared.moreInfoInDocs">
                                    <template #link>
                                        <LinkToDocs
                                            path="Terminverwaltung/Einzeltermine/"
                                            fragment="beitrittsoptionen"
                                        />
                                    </template>
                                </I18nT>
                            </p>
                        </Alert>
                    </div>
                </div>
                <InputSelect
                    v-model="language"
                    :label="$t('app.events.form.language')"
                    name="language"
                    :options="mobilizon_event_language_options"
                    :errors="submitCount === 0 ? undefined : errors.language"
                />
                <InputUrl
                    v-model="onlineAddress"
                    :label="$t('app.events.form.website')"
                    name="onlineAddress"
                    :errors="submitCount === 0 ? undefined : errors.onlineAddress"
                />
                <AddressInput
                    v-model="rawAddress"
                    v-model:address="physicalAddress"
                    name="physicalAddress"
                    :label="$t('app.events.form.address')"
                    :suggestions="mapSuggestions"
                    :mobilizon-group-id="mobilizon_group_id"
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
        <div class="flex justify-content-center align-items-center gap-4 mt-6">
            <Button
                :disabled="isSubmitting || isMapLoading"
                type="submit"
            >
                {{ t('app.events.created.edit.submit') }}
            </Button>

            <DeleteCreatedEvent
                v-if="createdEvent?.id"
                v-model:error-message-content="errorMessageContent"
                :event-id="createdEvent.id"
                :parent-event-id="parentEventId"
                :event-type="parentEventType"
            />
        </div>
    </form>
</template>
