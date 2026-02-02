<script setup lang="ts">
import zod from '@/lib/zod';
import { computed, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { dsgApi } from '@/lib/dsgApi';
import { formatDateTime, formatInputTime, reconstructOptions } from '@/lib/helper';
import { handleSubmitCallback, prepareEventsValues } from '@/lib/dsgClient';
import { buildSuggestions, loadMobilizionGroups } from '@/composables/EventCreateFormComposable';
import { searchAddress } from '@/lib/mobilizonClient';
import {
    mobilizon_category_options,
    mobilizon_event_join_options,
    mobilizon_event_language_options,
    mobilizon_event_status,
} from '@/lib/const';

import Button from '@/components/KERN/Button.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import InputText from '@/components/KERN/inputs/InputText.vue';
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
import LinkToDocs from '@/components/LinkToDocs.vue';

import { EventPlaceType, MobilizonEventJoinOptions, type Option } from '@/types/General';
import {
    type UploadedEvent,
    type UploadedEventAcceptPayload,
    type UploadedEventForm,
    type UploadedEventRequest,
    UploadedEventFormSchema,
    UploadedEventSchema,
} from '@/types/events/UploadedEvents';
import { type AddressForm, addressDefaults, mobilizonFieldsDefaults } from '@/types/Mobilizon';

const router = useRouter();
const isSubmitting = ref<boolean>(false);
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

const columns = [
    {
        key: 'summary',
        name: 'Name',
    },
    {
        key: 'dtstart',
        name: 'Startdatum',
        format: (value: string) => formatDateTime(value),
    },
    {
        key: 'dtend',
        name: 'Enddatum',
        format: (value: string) => formatDateTime(value),
    },
    {
        key: 'start',
        name: 'Start',
        format: (_: string, row: UploadedEvent) => formatInputTime(row.dtstart),
    },
    {
        key: 'duration',
        name: 'Dauer',
        format: (value: string) => value ?? 'unbekannt',
    },
];

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
            errorMessageContent.value =
                'Es wurden keine bevorstehende Veranstaltungen in der hochgeladenen Datei gefunden.';
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
        await loadAddresses();
        await dsgApi.post('/uploaded-events/accept', prepareAcceptPayload());
        await router.push({ name: 'uploadedEvents.index' });
    } catch (error: any) {
        errorMessageContent.value = error || 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
        console.error(error);
    }
};

const prepareAcceptPayload = (): UploadedEventAcceptPayload => {
    const filteredEvents = uploadedEvents.value.filter((event: UploadedEvent) => !event.already_exists);

    if (!filteredEvents.length) {
        errorMessageContent.value = 'Es sind keine neuen Veranstaltungen vorhanden.';
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
                physicalAddress: event.location || physicalAddress.value, // this one as well
                tags: tags.value,
            },
        })),
    };
};

const loadAddresses = async () => {
    try {
        const filteredEvents = uploadedEvents.value.filter(
            (event: UploadedEvent) => !event.already_exists && event.location
        );

        for (const event of filteredEvents) {
            event.location = await getAddress(event.location as string);
        }
    } catch (error) {
        return;
    }
};

const getAddress = function (location: string | null): Promise<AddressForm | null> {
    if (!location || location.trim().length === 0) return Promise.resolve(null);

    return new Promise<AddressForm | null>((resolve, reject) => {
        searchAddress(location)
            .then((response) => {
                if (!response || response.length === 0) {
                    console.warn('No address found for location:', location);
                    return resolve(null);
                }

                const bestMatch = response[0];
                resolve({
                    description: location.trim(),
                    street: bestMatch.street?.trim() || '',
                    postalCode: bestMatch.postalCode?.trim() || '',
                    locality: bestMatch.locality?.trim() || '',
                    region: bestMatch.region?.trim() || '',
                    country: bestMatch.country?.trim() || '',
                    geom: bestMatch.geom?.trim() || '',
                    type: (bestMatch.type?.trim() || addressDefaults.type) as EventPlaceType,
                    id: addressDefaults.id, // No ID in the response, using default
                    timezone: bestMatch.timezone?.trim() || addressDefaults.timezone,
                });
            })
            .catch((error) => {
                console.error('Error fetching address:', error);
                reject(error);
            });
    });
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

loadMobilizionGroups(mobilizon_group_id, mobilizionGroupOptions);
</script>

<template>
    <section
        v-if="!uploadedEvents.length"
        class="upload-section"
    >
        <div class="mb-1">
            <h1 class="kern-heading text-theme-primary">Kalenderdatei hochladen</h1>
        </div>
        <p class="mb-6">
            <b>Hinweis:</b>
            Die Kalenderdateien Funktion Upload eignet sich, um mehrere Termine gesammelt aus einem externen
            Kalenderprogramm zu
            <br />
            übernehmen. Weitere Informationen finden Sie im
            <LinkToDocs
                path="Terminverwaltung/iCal-Dateien/"
                fragment="ical-datei-hochladen"
            />.
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
                <InputRichText
                    v-model="description"
                    name="description"
                    label="Beschreibung (optional)"
                    :errors="submitCount === 0 ? undefined : errors.description"
                />

                <Divider class="my-4" />

                <div class="flex flex-column md:flex-row gap-6">
                    <div class="w-full">
                        <InputFile
                            name="file"
                            label="iCal-Datei hochladen"
                            :accept="['text/calendar', 'application/ics']"
                            :errors="submitCount === 0 ? undefined : errors.uploaded_file"
                            @change="onFileChange"
                        />
                    </div>
                    <div class="md:col-6 px-0 mt-2">
                        <Alert
                            title="Hinweis"
                            severity="info"
                        >
                            <p class="mb-4">
                                Sie können Ihre iCal-Datei entweder per Drag & Drop hier hineinziehen oder über den
                                Button “Durchsuchen...” auswählen. Bitte beachten Sie: Nur iCal-Dateien (.ics) werden
                                unterstützt.
                            </p>
                            <p>
                                Weitere Informationen finden Sie im
                                <LinkToDocs
                                    path="Terminverwaltung/iCal-Dateien/"
                                    fragment="ical-datei-hochladen"
                                />.
                            </p>
                        </Alert>
                    </div>
                </div>

                <Divider class="my-4" />

                <div class="flex flex-column gap-5">
                    <InputSelect
                        v-model="mobilizon_group_id"
                        label="Organisation"
                        name="mobilizon_group_id"
                        :options="mobilizionGroupOptions"
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
                                    Wenn Sie eine externe Anmeldeseite verwenden, geben Sie hier den entsprechenden Link
                                    an. Wenn Sie keine Online-Anmeldung nutzen, informieren Sie die Teilnehmer*innen in
                                    der Veranstaltungsbeschreibung über das Anmeldeverfahren und ob eine Anmeldung
                                    erforderlich ist.
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
                        name="state"
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
            <Button
                type="primary"
                :disabled="isSubmitting || isMapLoading"
                class="mx-auto flex align-items-center"
            >
                <span class="flex gap-2">
                    <Icon
                        name="visibility"
                        color="white"
                    />
                    Veranstaltungen anzeigen
                </span>
            </Button>
        </form>
    </section>

    <section
        v-else
        class="preview-section"
    >
        <div class="mb-1">
            <h2 class="kern-heading">iCal-Datei: Veranstaltungen</h2>
        </div>
        <p class="mb-4">
            <b>Hinweis:</b>
            Die Tabelle zeigt Ihnen alle Termine, die aus der hochgeladenen iCal-Datei übernommen werden. Nach dem
            Import können einzelne Termine bei Bedarf manuell bearbeitet oder ergänzt werden. Weitere Informationen
            finden Sie im
            <LinkToDocs
                path="Terminverwaltung/iCal-Dateien/"
                fragment="ical-datei-hochladen"
            />.
        </p>

        <template v-if="newUploadedEvents.length">
            <Table
                :columns="columns"
                :data="newUploadedEvents"
                class="w-full"
            ></Table>

            <Button
                class="my-8"
                @click="accept"
            >
                <span class="flex gap-2 align-items-center">
                    <Icon
                        name="add"
                        color="white"
                        size="lg"
                    />
                    Vorschautermine anlegen
                </span>
            </Button>
        </template>
        <div v-else>
            <Alert
                title="Hinweis"
                severity="info"
            >
                <p>Es sind keine neuen Veranstaltungen vorhanden.</p>
                <p>Alle Termine in dieser Datei sind schon hochgeladen wurden.</p>
            </Alert>
        </div>

        <section v-if="toBeFilteredUploadedEvents.length">
            <div class="self-start">
                <h3 class="kern-heading">Bereits vorhandene Termine</h3>
            </div>
            <p class="mb-4">
                <b>Hinweis:</b>
                Die folgenden Termine der iCal-Datei wurden erkannt, sind jedoch bereits im Kalender vorhanden und
                werden beim Anlegen übersprungen. Weitere Informationen finden Sie im
                <LinkToDocs
                    path="Terminverwaltung/iCal-Dateien/"
                    fragment="bereits-vorhandene-termine"
                />.
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
