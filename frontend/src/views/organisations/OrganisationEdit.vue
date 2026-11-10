<script setup lang="ts">
import zod from '@/lib/zod';
import { computed, ref } from 'vue';
import { I18nT, useI18n } from 'vue-i18n';
import { useField, useForm } from 'vee-validate';
import { useRoute } from 'vue-router';
import { toTypedSchema } from '@vee-validate/zod';
import { buildSuggestions } from '@/composables/EventCreateFormComposable';
import { loadImage } from '@/lib/dsgClient';
import { stripHtml } from '@/lib/helper';
import { dsgApi } from '@/lib/dsgApi';
import { user } from '@/composables/UserComposoable';
import { isStrictModeEnabled } from '@/lib/instanceConfig';

import Fieldset from '@/components/KERN/Fieldset.vue';
import InputText from '@/components/KERN/inputs/InputText.vue';
import InputRichText from '@/components/KERN/inputs/InputRichText.vue';
import Button from '@/components/KERN/Button.vue';
import Alert from '@/components/KERN/Alert.vue';
import InputImage from '@/components/KERN/inputs/InputImage.vue';
import Map from '@/components/Map.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';
import OrganisationFeaturedToggle from '@/components/OrganisationFeaturedToggle.vue';

import { ZodType } from 'zod';
import { addressDefaults, type AddressForm, AddressFormSchema } from '@/types/Mobilizon';
import type { AxiosError } from '@/lib/dsgApi';
import type { IOrganisation } from '@/types/General';

interface EditOrganisationForm {
    name: string;
    summary: string;
    preferred_username?: string;
    id?: number | string;
    avatar?: any;
}

const { t } = useI18n();

const showErrorMessage = ref<boolean>(false);
const errorMessageContent = ref<string>('');
const showSuccessMessage = ref<boolean>(false);
const rawAddress = ref<string>('');
const mapRef = ref<InstanceType<typeof Map> | null>(null);
const isMapLoading = computed(() => mapRef.value?.isLoading ?? false);
const mapSuggestions = computed(() => buildSuggestions(mapRef.value?.suggestions ?? []));
const organisation = ref<IOrganisation | null>(null);

const route = useRoute();
const preferredUsername = route.params.preferredUsername as string;

const isFeatured = ref<boolean>(false);
const featuredError = ref<string | null>(null);

const validationSchema = toTypedSchema(
    zod.object({
        name: zod.string().refine((val) => val.trim().length > 0, {
            message: 'app.organisations.validation.nameNotEmpty',
        }),
        summary: zod
            .string()
            .refine((val) => stripHtml(val).length > 0, { message: 'app.organisations.validation.summaryNotEmpty' }),
        avatar: zod.any().optional(),
        physicalAddress: AddressFormSchema.optional(),
    }) satisfies ZodType<EditOrganisationForm>
);

const { handleSubmit, errors, isSubmitting, submitCount } = useForm({
    validationSchema,
});

const { value: name } = useField<string>('name');
const { value: summary } = useField<string>('summary');
const { value: avatar } = useField<EditOrganisationForm['avatar']>('avatar');
const { value: physicalAddress } = useField<AddressForm>('physicalAddress');
const groupId = ref<string | null>(null);

const onSubmit = handleSubmit(async (values) => {
    if (values.physicalAddress) values.physicalAddress.description = '';
    showSuccessMessage.value = false;
    showErrorMessage.value = false;
    try {
        await dsgApi.post(
            'organisations/group',
            <EditOrganisationForm>{
                id: groupId.value,
                ...values,
                preferredUsername: preferredUsername,
                avatar: values.avatar
                    ? {
                          media: {
                              name: values.avatar.name,
                              alt: '',
                              file: values.avatar as File,
                          },
                      }
                    : undefined,
            },
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );

        showSuccessMessage.value = true;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error: AxiosError) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        showErrorMessage.value = true;
        errorMessageContent.value =
            error?.response?.data?.error ||
            t('app.organisations.edit.saveError');
    }
});

const loadOrganisation = async () => {
    if (!preferredUsername) {
        showErrorMessage.value = true;
        errorMessageContent.value = t('app.organisations.loadError');
        return;
    }
    try {
        const { data } = await dsgApi.get<IOrganisation>('organisations/group', {
            params: {
                preferred_username: preferredUsername,
            },
        });

        groupId.value = data.id;
        summary.value = data.summary || '';
        name.value = data.name;
        organisation.value = data;
        isFeatured.value = data.is_featured ?? false;

        if (data.physicalAddress) {
            physicalAddress.value = data.physicalAddress || '';
            rawAddress.value = buildSuggestions([physicalAddress.value])[0];
        }

        avatar.value = await loadImage('organisations/avatar', {
            preferred_username: preferredUsername,
        });
    } catch (error) {
        console.error('Error loading organisation:', error);
        showErrorMessage.value = true;
        errorMessageContent.value = t('app.organisations.loadError');
    }
};

const updateAddress = (address: string) => {
    physicalAddress.value = {
        ...addressDefaults,
        description: address,
    };
};

loadOrganisation();
</script>
<template>
    <h1 class="kern-heading text-theme-primary">{{ t('app.organisations.edit.title', { name }) }}</h1>
    <p class="mt-1 mb-6">
        <b>{{ t('app.organisations.hint') }}</b> <I18nT scope="global" keypath="app.organisations.edit.hint">
            <template #docs>
                <LinkToDocs
                    path="Terminverwaltung/Organisation/"
                    fragment="organisation-bearbeiten"
                />
            </template>
        </I18nT>
    </p>

    <form
        novalidate
        @submit.prevent="onSubmit"
    >
        <Alert
            v-if="showErrorMessage"
            :title="t('common.error')"
            :content="errorMessageContent || t('app.organisations.errorOccurred')"
            severity="danger"
        />
        <Alert
            v-else-if="showSuccessMessage"
            class="mb-4"
            :title="t('app.organisations.saved')"
            :content="
                !isStrictModeEnabled
                    ? t('app.organisations.edit.savedContent')
                    : t('app.organisations.edit.savedContentStrict')
            "
            :severity="!isStrictModeEnabled ? 'success' : 'info'"
        />
        <Alert
            v-if="featuredError"
            :title="t('common.error')"
            :content="featuredError"
            severity="danger"
            class="mb-4"
        />

        <Fieldset
            v-if="user?.type === 'admin'"
            class="mb-4"
        >
            <div class="flex flex-column gap-4">
                <div class="flex align-items-center gap-3">
                    <OrganisationFeaturedToggle
                        v-if="user?.type === 'admin' && groupId"
                        v-model:error="featuredError"
                        v-model:organisation="organisation as IOrganisation"
                    />
                </div>
            </div>
        </Fieldset>

        <Fieldset>
            <div class="flex flex-column gap-5">
                <InputText
                    v-model="name"
                    :label="t('app.organisations.edit.name')"
                    name="name"
                    :errors="submitCount === 0 ? undefined : errors.name"
                />
                <InputText
                    v-model="preferredUsername"
                    :label="t('app.organisations.edit.username')"
                    name="preferredUsername"
                    disabled
                />
                <InputRichText
                    v-model="summary"
                    :label="t('app.organisations.edit.summary')"
                    name="summary"
                    :errors="submitCount === 0 ? undefined : errors.summary"
                    rows="10"
                />

                <InputImage
                    v-model="avatar"
                    :label="t('app.organisations.edit.avatar')"
                    name="avatar"
                    :accept="['image/gif', 'image/png', 'image/jpeg', 'image/webp']"
                    :errors="submitCount === 0 ? undefined : errors.avatar"
                />
                <InputText
                    v-model="rawAddress"
                    name="physicalAddress"
                    :label="t('app.organisations.edit.address')"
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
                :disabled="isSubmitting || isMapLoading"
                class="mt-5"
                type="submit"
            >
                {{ t('app.organisations.edit.submit') }}
            </Button>
        </div>
    </form>
</template>
