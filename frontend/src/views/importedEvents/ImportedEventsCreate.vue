<script lang="ts" setup>
import { ref, watch, computed } from 'vue';
import { I18nT } from 'vue-i18n';
import { toTypedSchema } from '@vee-validate/zod';
import { useRouter } from 'vue-router';
import { handleSubmitCallback, prepareEventsValues } from '@/lib/dsgClient';
import { reconstructOptions } from '@/lib/helper';
import { buildSuggestions, loadMobilizionGroups } from '@/composables/EventCreateFormComposable';
import { useField, useForm } from 'vee-validate';
import {
    mobilizon_category_options,
    mobilizon_event_join_options,
    mobilizon_event_language_options,
    mobilizon_event_status,
} from '@/lib/const';

import InputUrl from '@/components/KERN/inputs/InputUrl.vue';
import Alert from '@/components/KERN/Alert.vue';
import Button from '@/components/KERN/Button.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import InputRichText from '@/components/KERN/inputs/InputRichText.vue';
import InputSelect from '@/components/KERN/inputs/InputSelect.vue';
import Divider from '@/components/KERN/cosmetics/Divider.vue';
import InputRadios from '@/components/KERN/inputs/InputRadios.vue';
import Map from '@/components/Map.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';
import InputTags from '@/components/InputTags.vue';
import AddressInput from '@/components/AddressInput.vue';

import { MobilizonEventJoinOptions, type Option } from '@/types/General';
import type { RemoveKeys } from '@/types/Generics';

import { type AddressForm } from '@/types/Mobilizon';
import {
    type ImportedEvent,
    ImportedEventSchema,
    type ImportedEventForm,
    ImportedEventFormSchema
} from '@/types/events/ImportedEvents';

const errorMessageContent = ref<string>('');
const mobilizionGroupOptions = ref<Option[]>([]);
const rawAddress = ref<string>('');
const mapRef = ref<InstanceType<typeof Map> | null>(null);
const isMapLoading = computed(() => mapRef.value?.isLoading ?? false);
const mapSuggestions = computed(() => buildSuggestions(mapRef.value?.suggestions ?? []));
const router = useRouter();

const { handleSubmit, errors, submitCount, isSubmitting } = useForm<ImportedEventForm>({
    validationSchema: toTypedSchema(ImportedEventFormSchema),
});

const { value: url } = useField<string>('url');
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

const onSubmit = handleSubmit(async (values: ImportedEventForm) => {
    values.is_active = true;
    const preparedValues: RemoveKeys<ImportedEvent, 'id'> = prepareEventsValues<
        ImportedEventForm,
        RemoveKeys<ImportedEvent, 'id'>
    >(values, ['url', 'is_active']);

    try {
        isSubmitting.value = true;
        errorMessageContent.value = '';

        const data = await handleSubmitCallback('/imported-events', {
            values: preparedValues,
            resDataKey: 'importedEvent',
            schema: ImportedEventSchema,
        });

        await router.push({ name: 'importedEvents.index', params: { id: data.id } });
    } catch (error: any) {
        errorMessageContent.value = error;
    } finally {
        isSubmitting.value = false;
    }
});

watch(joinOptions, (newValue) => {
    if (newValue !== MobilizonEventJoinOptions.EXTERNAL) externalParticipationUrl.value = undefined;
    else if (!externalParticipationUrl.value) externalParticipationUrl.value = '';
});

loadMobilizionGroups(mobilizon_group_id, mobilizionGroupOptions);
</script>
<template>
    <h1 class="kern-heading text-theme-primary">{{ $t('app.events.imported.title') }}</h1>
    <p class="mt-1 mb-6">
        <b>{{ $t('app.shared.hint') }}</b> <I18nT scope="global" keypath="app.events.imported.hint">
            <template #link>
                <LinkToDocs
                    path="Terminverwaltung/Kalenderintegration/"
                    fragment="kalender-integrieren-ical-url-anbinden"
                />
            </template>
        </I18nT>
    </p>
    <form
        novalidate
        @submit.prevent="onSubmit"
    >
        <Fieldset>
            <Alert
                v-if="errorMessageContent.length"
                :title="$t('common.error')"
                :content="errorMessageContent"
                severity="danger"
            />
            <InputRichText
                v-model="description"
                class="mt-3"
                :label="$t('app.events.form.descriptionOptional')"
                name="description"
                :errors="submitCount === 0 ? undefined : errors.description"
            />
            <Divider class="my-5" />
            <div class="flex flex-column md:flex-row gap-6">
                <div class="w-full">
                    <InputUrl
                        v-model="url"
                        :label="$t('app.events.imported.create.urlLabel')"
                        name="url"
                        :errors="submitCount === 0 ? undefined : errors.url"
                    />
                </div>
                <div class="md:col-6 px-0 mt-2">
                    <Alert
                        :title="$t('app.shared.information')"
                        severity="info"
                    >
                        <p>
                            {{ $t('app.events.imported.create.urlInfo') }}
                        </p>
                        <p>
                            <I18nT scope="global" keypath="app.shared.moreInfoInDocs">
                                <template #link>
                                    <LinkToDocs
                                        path="Terminverwaltung/Kalenderintegration/"
                                        fragment="kalender-integrieren-ical-url-anbinden"
                                    />
                                </template>
                            </I18nT>
                        </p>
                    </Alert>
                </div>
            </div>

            <Divider class="my-5" />

            <div class="flex flex-column gap-5">
                <InputSelect
                    v-model="mobilizon_group_id"
                    :label="$t('app.events.form.organisation')"
                    name="mobilizon_group_id"
                    :options="mobilizionGroupOptions"
                    :errors="submitCount === 0 ? undefined : errors.mobilizon_group_id"
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
                    name="status"
                    :options="mobilizon_event_status"
                    :errors="submitCount === 0 ? undefined : errors.status"
                />
                <InputUrl
                    v-model="onlineAddress"
                    :label="$t('app.events.form.website')"
                    name="onlineAddress"
                    :errors="submitCount === 0 ? undefined : errors.onlineAddress"
                />
            </div>
            <AddressInput
                v-model="rawAddress"
                v-model:address="physicalAddress"
                name="physicalAddress"
                :label="$t('app.events.form.address')"
                :suggestions="mapSuggestions"
                :mobilizon-group-id="mobilizon_group_id"
                :errors="submitCount === 0 ? undefined : errors.physicalAddress"
            />
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
                icon-left="add"
            >
                {{ $t('app.events.imported.create.submit') }}
            </Button>
        </div>
    </form>
</template>
