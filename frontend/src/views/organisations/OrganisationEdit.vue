<script setup lang="ts">
import zod from '@/lib/zod';
import { computed, ref } from 'vue';
import { useField, useForm } from 'vee-validate';
import { useRoute } from 'vue-router';
import { toTypedSchema } from '@vee-validate/zod';
import { buildSuggestions } from '@/composables/EventCreateFormComposable';
import { dsgApi } from '@/lib/dsgApi';

import Fieldset from '@/components/KERN/Fieldset.vue';
import InputText from '@/components/KERN/inputs/InputText.vue';
import InputRichText from '@/components/KERN/inputs/InputRichText.vue';
import Button from '@/components/KERN/Button.vue';
import Alert from '@/components/KERN/Alert.vue';
import InputImage from '@/components/KERN/inputs/InputImage.vue';
import Map from '@/components/Map.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';

import { ZodType } from 'zod';
import { addressDefaults, type AddressForm, AddressFormSchema } from '@/types/Mobilizon';
import type { AxiosError } from '@/lib/dsgApi';
import { loadImage } from '@/lib/dsgClient';


interface EditOrganisationForm {
    name: string;
    summary: string;
    id?: number | string;
    avatar?: any;
}


const showErrorMessage = ref<boolean>(false);
const errorMessageContent = ref<string>('');
const showSuccessMessage = ref<boolean>(false);
const rawAddress = ref<string>('');
const mapRef = ref<InstanceType<typeof Map> | null>(null);
const isMapLoading = computed(() => mapRef.value?.isLoading ?? false);
const mapSuggestions = computed(() => buildSuggestions(mapRef.value?.suggestions ?? []));

const route = useRoute();
const preferredUsername = route.params.preferredUsername as string;

const validationSchema = toTypedSchema(
    zod.object({
        name: zod.string().nonempty(),
        summary: zod.string().nonempty(),
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
        console.log(values);
        console.log({
            avatar: {
                media: {
                    file: values.avatar,
                    name: values.avatar?.name || '',
                },
            },
        });
        await dsgApi.post(
            'organisations/group',
            <EditOrganisationForm>{
                id: groupId.value,
                ...values,
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
    } catch (error: any | AxiosError) {
        console.error(error);
        showErrorMessage.value = true;
        errorMessageContent.value =
            error?.response?.data?.error ||
            'Es ist ein Fehler aufgetreten. Bevor du deinen Benutzer weiter einrichten kannst, musst du erst deine E-Mail-Adresse bestätigen.';
    }
});
const loadOrganisation = async () => {
    if (!groupId) {
        showErrorMessage.value = true;
        errorMessageContent.value = 'Organisation konnte nicht geladen werden.';
        return;
    }
    try {
        const { data } = await dsgApi.get('organisations/group', {
            params: {
                preferred_username: preferredUsername,
            },
        });

        groupId.value = data.id;
        summary.value = data.summary;
        name.value = data.name;
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
        errorMessageContent.value = 'Organisation konnte nicht geladen werden.';
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
    <h1 class="kern-heading text-theme-primary">{{ name }} - Organisation bearbeiten</h1>
    <p class="mt-1 mb-6">
        <b>Hinweis:</b>
        In diesem Formular können Sie die öffentlichen Angaben Ihrer Organisation anpassen. Weitere Informationen finden
        Sie im
        <LinkToDocs path="" />.
    </p>

    <form
        novalidate
        @submit.prevent="onSubmit"
    >
        <Alert
            v-if="showErrorMessage"
            title="Fehler"
            :content="errorMessageContent || 'Es ist ein Fehler aufgetreten.'"
            severity="danger"
        />

        <Fieldset>
            <div class="flex flex-column gap-5">
                <InputText
                    v-model="name"
                    label="Name der Organisation"
                    name="name"
                    :errors="submitCount === 0 ? undefined : errors.name"
                />
                <InputText
                    v-model="preferredUsername"
                    label="Benutzername der Organisation"
                    name="preferredUsername"
                    disabled
                />
                <InputRichText
                    v-model="summary"
                    label="Beschreibung (optional)"
                    name="summary"
                    :errors="submitCount === 0 ? undefined : errors.summary"
                    rows="10"
                />

                <InputImage
                    v-model="avatar"
                    label="Profilbild (optional)"
                    name="avatar"
                    :accept="['image/gif', 'image/png', 'image/jpeg', 'image/webp']"
                    :errors="submitCount === 0 ? undefined : errors.avatar"
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
        <Alert
            v-if="showSuccessMessage"
            title="Gespeichert"
            :content="'Die Änderungen wurden erfolgreich gespeichert.'"
            severity="success"
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
</template>
