<script setup lang="ts">
import zod from '@/lib/zod';
import { computed, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { I18nT, useI18n } from 'vue-i18n';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { dsgApi } from '@/lib/dsgApi';
import { formatDateTime, formatInputTime, preventImplicitSubmit, reconstructOptions } from '@/lib/helper';
import { handleSubmitCallback, prepareEventsValues } from '@/lib/dsgClient';
import { buildSuggestions, loadMobilizionGroups } from '@/composables/EventCreateFormComposable';
import {
    mobilizon_category_options,
    mobilizon_event_join_options,
    mobilizon_event_language_options,
    mobilizon_event_status,
} from '@/lib/const';

import Button from '@/components/KERN/Button.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import InputSelect from '@/components/KERN/inputs/InputSelect.vue';
import InputFile from '@/components/KERN/inputs/InputFile.vue';
import Alert from '@/components/KERN/Alert.vue';
import Divider from '@/components/KERN/cosmetics/Divider.vue';
import InputRichText from '@/components/KERN/inputs/InputRichText.vue';
import Icon from '@/components/KERN/cosmetics/Icon.vue';
import Table from '@/components/KERN/Table.vue';
import Map from '@/components/Map.vue';
import InputUrl from '@/components/KERN/inputs/InputUrl.vue';
import InputRadios from '@/components/KERN/inputs/InputRadios.vue';
import InputTags from '@/components/InputTags.vue';
import AddressInput from '@/components/AddressInput.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';

import { MobilizonEventJoinOptions, type Option } from '@/types/General';
import {
    type UploadedEvent,
    type UploadedEventAcceptPayload,
    type UploadedEventForm,
    type UploadedEventRequest,
    UploadedEventFormSchema,
    UploadedEventSchema,
} from '@/types/events/UploadedEvents';
import { type AddressForm, mobilizonFieldsDefaults } from '@/types/Mobilizon';

const router = useRouter();
const { t } = useI18n();
const isSubmitting = ref<boolean>(false);
const isUploading = ref<boolean>(false);
const errorMessageContent = ref<string>('');
const mobilizionGroupOptions = ref<Option[]>([]);
const rawAddress = ref<string>('');
const mapRef = ref<InstanceType<typeof Map> | null>(null);
const isMapLoading = computed(() => mapRef.value?.isLoading ?? false);
const mapSuggestions = computed(() => buildSuggestions(mapRef.value?.suggestions ?? []));
const uploadedEvents = ref<UploadedEvent[]>([]);
const toBeFilteredUploadedEvents = computed(() =>
    uploadedEvents.value.filter((event: UploadedEvent) => event.already_exists)
);
const newUploadedEvents = computed(() => uploadedEvents.value.filter((event: UploadedEvent) => !event.already_exists));

const { handleSubmit, errors, submitCount } = useForm<UploadedEventForm>({
    validationSchema: toTypedSchema(UploadedEventFormSchema),
});

const { value: description } = useField<string>('description');
const { value: mobilizon_group_id } = useField<number>('mobilizon_group_id');
const { value: uploaded_file } = useField<File | null>('uploaded_file');
const { value: category } = useField<string>('category');
const { value: tags } = useField<string[]>('tags');
const { value: joinOptions } = useField<string>('joinOptions');
const { value: language } = useField<string>('language');
const { value: status } = useField<string>('status');
const { value: onlineAddress } = useField<string>('onlineAddress');
const { value: physicalAddress } = useField<AddressForm>('physicalAddress');
const { value: externalParticipationUrl } = useField<string | undefined>('externalParticipationUrl');

// computed so the labels follow a language switch
const columns = computed(() => [
    {
        key: 'summary',
        name: t('app.events.columns.name'),
    },
    {
        key: 'dtstart',
        name: t('app.events.columns.startDate'),
        format: (value: string) => formatDateTime(value),
    },
    {
        key: 'dtend',
        name: t('app.events.columns.endDate'),
        format: (value: string) => formatDateTime(value),
    },
    {
        key: 'start',
        name: t('app.events.columns.start'),
        format: (_: string, row: UploadedEvent) => formatInputTime(row.dtstart),
    },
    {
        key: 'duration',
        name: t('app.events.columns.duration'),
        format: (value: string) => value ?? t('app.shared.unknown'),
    },
]);

const onSubmit = handleSubmit(async (values: UploadedEventForm) => {
    const preparedValues = prepareEventsValues<UploadedEventForm, UploadedEventRequest>(values, ['uploaded_file']);

    try {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        isSubmitting.value = true;
        errorMessageContent.value = '';

        uploadedEvents.value =
            (await handleSubmitCallback('/uploaded-events/upload', {
                headers: { 'Content-Type': 'multipart/form-data' },
                values: preparedValues,
                resDataKey: 'events',
                schema: zod.array(UploadedEventSchema),
            })) ?? [];

        if (uploadedEvents.value.length === 0) {
            errorMessageContent.value = t('app.events.uploaded.create.noUpcomingEvents');
        }
    } catch (error: any) {
        errorMessageContent.value = error;
    } finally {
        isSubmitting.value = false;
    }
});

const onFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    uploaded_file.value = target.files?.[0] ?? null;
};

const accept = async () => {
    try {
        isUploading.value = true;
        await dsgApi.post('/uploaded-events/accept', prepareAcceptPayload());
        await router.push({ name: 'uploadedEvents.index' });
    } catch (error: any) {
        errorMessageContent.value = error || t('app.events.uploaded.create.genericError');
        console.error(error);
    } finally {
        isUploading.value = false;
    }
};

const prepareAcceptPayload = (): UploadedEventAcceptPayload => {
    const filteredEvents = uploadedEvents.value.filter((event: UploadedEvent) => !event.already_exists);

    if (!filteredEvents.length) {
        errorMessageContent.value = t('app.events.uploaded.create.noNewEvents');
        throw new Error('No new events found');
    }

    return {
        mobilizon_group_id: mobilizon_group_id.value,
        filename: uploaded_file.value!.name!,
        mobilizon_fields: {
            description: description.value,
            category: category.value,
            tags: tags.value,
            status: status.value,
            joinOptions: joinOptions.value,
            language: language.value,
            visibility: mobilizonFieldsDefaults.visibility,
            onlineAddress: onlineAddress.value,
            physicalAddress: physicalAddress.value,
        },
        events: filteredEvents.map((event: UploadedEvent) => ({
            ...event,
            summary: event.summary || description.value,
            mobilizon_fields: {
                description: event.description || description.value,
                category: event.category || category.value,
                status: event.status || status.value,
                joinOptions: joinOptions.value,
                externalParticipationUrl: externalParticipationUrl.value,
                language: language.value,
                visibility: mobilizonFieldsDefaults.visibility,
                onlineAddress: event.url || onlineAddress.value, // this key gets removed in the backend if undefined
                physicalAddress: event.location || physicalAddress.value?.description, // this one as well
                tags: tags.value,
            },
        })),
    };
};

watch(joinOptions, (newValue) => {
    if (newValue !== MobilizonEventJoinOptions.EXTERNAL) externalParticipationUrl.value = undefined;
    else if (!externalParticipationUrl.value) externalParticipationUrl.value = '';
});

loadMobilizionGroups(mobilizon_group_id, mobilizionGroupOptions);
</script>

<template>
    <section
        v-if="!uploadedEvents.length"
        class="upload-section"
    >
        <div class="mb-1">
            <h1 class="kern-heading text-theme-primary">{{ t('app.events.uploaded.create.title') }}</h1>
        </div>
        <p class="mb-6">
            <b>{{ $t('app.shared.hint') }}</b> <I18nT scope="global" keypath="app.events.uploaded.create.hint">
                <template #link>
                    <LinkToDocs
                        path="Terminverwaltung/iCal-Dateien/"
                        fragment="ical-datei-hochladen"
                    />
                </template>
            </I18nT>
        </p>
        <form
            novalidate
            @submit.prevent="onSubmit"
            @keydown.enter="preventImplicitSubmit"
        >
            <Alert
                v-if="errorMessageContent.length"
                :title="$t('common.error')"
                class="mb-4"
                severity="danger"
                :content="errorMessageContent"
            />

            <Fieldset>
                <InputRichText
                    v-model="description"
                    name="description"
                    :label="$t('app.events.form.descriptionOptional')"
                    :errors="submitCount === 0 ? undefined : errors.description"
                />

                <Divider class="my-4" />

                <div class="flex flex-column md:flex-row gap-6">
                    <div class="w-full">
                        <InputFile
                            name="file"
                            :label="t('app.events.uploaded.create.fileLabel')"
                            :accept="['text/calendar', 'application/ics']"
                            :errors="submitCount === 0 ? undefined : errors.uploaded_file"
                            @change="onFileChange"
                        />
                    </div>
                    <div class="md:col-6 px-0 mt-2">
                        <Alert
                            :title="$t('app.shared.hintTitle')"
                            severity="info"
                        >
                            <p class="mb-4">
                                {{ t('app.events.uploaded.create.fileInfo') }}
                            </p>
                            <p>
                                <I18nT scope="global" keypath="app.shared.moreInfoInDocs">
                                    <template #link>
                                        <LinkToDocs
                                            path="Terminverwaltung/iCal-Dateien/"
                                            fragment="ical-datei-hochladen"
                                        />
                                    </template>
                                </I18nT>
                            </p>
                        </Alert>
                    </div>
                </div>

                <Divider class="my-4" />

                <div class="flex flex-column gap-5">
                    <InputSelect
                        v-model="mobilizon_group_id"
                        :label="$t('app.events.form.organisation')"
                        name="mobilizon_group_id"
                        :options="mobilizionGroupOptions"
                        disabled
                    />
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
                    <InputSelect
                        v-model="status"
                        :label="$t('app.events.form.status')"
                        name="state"
                        :options="mobilizon_event_status"
                        :errors="submitCount === 0 ? undefined : errors.status"
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
            <Button
                type="submit"
                :disabled="isSubmitting || isMapLoading"
                class="mx-auto flex align-items-center"
            >
                <span class="flex gap-2">
                    <Icon
                        name="visibility"
                        color="white"
                    />
                    {{ t('app.events.uploaded.create.showEvents') }}
                </span>
            </Button>
        </form>
    </section>

    <section
        v-else
        class="preview-section"
    >
        <div class="mb-1">
            <h2 class="kern-heading">{{ t('app.events.uploaded.create.previewTitle') }}</h2>
        </div>
        <p class="mb-4">
            <b>{{ $t('app.shared.hint') }}</b> <I18nT scope="global" keypath="app.events.uploaded.create.previewHint">
                <template #link>
                    <LinkToDocs
                        path="Terminverwaltung/iCal-Dateien/"
                        fragment="ical-datei-hochladen"
                    />
                </template>
            </I18nT>
        </p>

        <template v-if="newUploadedEvents.length">
            <Table
                :columns="columns"
                :data="newUploadedEvents"
                class="w-full"
            ></Table>

            <Button
                class="my-8"
                :disabled="isUploading"
                @click="accept"
            >
                <span class="flex gap-2 align-items-center">
                    <Icon
                        name="add"
                        color="white"
                        size="lg"
                    />
                    {{ t('app.events.uploaded.create.acceptButton') }}
                </span>
            </Button>
        </template>
        <div v-else>
            <Alert
                :title="$t('app.shared.hintTitle')"
                severity="info"
                class="mb-4"
            >
                <p>{{ t('app.events.uploaded.create.noNewEvents') }}</p>
                <p>{{ t('app.events.uploaded.create.allAlreadyUploaded') }}</p>
            </Alert>
        </div>

        <section v-if="toBeFilteredUploadedEvents.length">
            <div class="self-start">
                <h3 class="kern-heading">{{ t('app.events.uploaded.create.existingTitle') }}</h3>
            </div>
            <p class="mb-4">
                <b>{{ $t('app.shared.hint') }}</b> <I18nT scope="global" keypath="app.events.uploaded.create.existingHint">
                    <template #link>
                        <LinkToDocs
                            path="Terminverwaltung/iCal-Dateien/"
                            fragment="bereits-vorhandene-termine"
                        />
                    </template>
                </I18nT>
            </p>

            <Table
                :columns="columns"
                :data="toBeFilteredUploadedEvents"
                class="w-full"
            ></Table>
        </section>
    </section>
</template>

<style scoped lang="scss"></style>
