<script setup lang="ts">
import { ref } from 'vue';
import { dsgApi } from '@/lib/dsgApi';
import Button from '@/components/KERN/Button.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import InputText from '@/components/KERN/inputs/InputText.vue';
import InputSelect from '@/components/KERN/inputs/InputSelect.vue';
import InputEmail from '@/components/KERN/inputs/InputEmail.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';
import { useRoute, useRouter } from 'vue-router';
import zod from '@/lib/zod';
import { ZodType } from 'zod';
import { user_types_options, user_is_active_options } from '@/lib/const';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import Alert from '@/components/KERN/Alert.vue';
import axios from 'axios';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { user } from '@/composables/UserComposoable';

interface ServerError {
    message: string;
    errors: Record<string, string[]>;
}

interface User {
    email: string;
    type: string;
    is_active: boolean;
    mobilizon_name?: string;
    mobilizon_preferred_username?: string;
}

const router = useRouter();
const userID: string = useRoute().path.split('/').pop() ?? '';
const showErrorMessage = ref<boolean>(false);
const showSuccessMessage = ref<boolean>(false);
const errorMessageContent = ref<string | undefined>(undefined);
const showDeleteOption = ref<boolean>(user.value?.id.toString() !== userID);
const showDeleteDialog = ref<boolean>(false);

const validationSchema = toTypedSchema(
    zod.object({
        email: zod.string().nonempty().email(),
        type: zod.string().nonempty().default('user'),
        is_active: zod.boolean().default(true),
        mobilizon_name: zod.string().optional(),
        mobilizon_preferred_username: zod.string().optional(),
    }) satisfies ZodType<User>
);

const { handleSubmit, errors, isSubmitting, submitCount } = useForm({
    validationSchema,
});
const { value: email } = useField<string>('email');
const { value: type } = useField<string>('type');
const { value: is_active } = useField<boolean>('is_active');
const { value: mobilizon_name } = useField<string>('mobilizon_name');
const { value: mobilizon_preferred_username } = useField<string>('mobilizon_preferred_username');

const loadUser = async () => {
    try {
        const data = await dsgApi.get(`/admin/user/${userID}`);
        const user: User = data.data;

        email.value = user.email;
        type.value = user.type;
        is_active.value = user.is_active;
        mobilizon_name.value = user.mobilizon_name || '';
        mobilizon_preferred_username.value = user.mobilizon_preferred_username || '';
    } catch (error) {
        console.error(error);
        await router.push({ name: 'admin.index' });
    }
};

const deleteUser = async () => {
    try {
        await dsgApi.delete(`/admin/user/${userID}`);
        await router.push({ name: 'admin.index' });
    } catch (error) {
        console.error(error);
    }
};

const onSubmit = handleSubmit(async (values) => {
    try {
        await dsgApi.patch(`/admin/user/${userID}`, <User>{
            ...values,
        });
        showSuccessMessage.value = true;
    } catch (error) {
        showErrorMessage.value = true;
        if (axios.isAxiosError<ServerError, Record<string, unknown>>(error)) {
            errorMessageContent.value = error?.response?.data?.message;
        } else {
            console.error(error);
            errorMessageContent.value = 'Benutzer konnte nicht gespeichert werden.';
        }
    }
});

loadUser();
</script>
<template>
    <h2 class="kern-heading text-theme-primary">Nutzer*in bearbeiten</h2>
    <p class="mb-4">
        <b>Hinweis:</b>
        Als Administrator*in einer Instanz können Sie Einstellungen für Nutzer*innen aller Organisationen vornehmen.
        Weitere Informationen finden Sie im
        <LinkToDocs
            path="Terminverwaltung/Instanz/"
            fragment="nutzer-innen-verwalten"
        />
        .
    </p>
    <Alert
        v-if="showErrorMessage"
        title="Fehler"
        :content="errorMessageContent"
        severity="danger"
    />
    <Alert
        v-if="showSuccessMessage"
        title="Erfolg"
        content="Benutzer erfolgreich aktualisiert."
        severity="success"
    />
    <form
        novalidate
        @submit.prevent="onSubmit"
    >
        <Fieldset>
            <div class="flex flex-column align-items-center md:flex-row gap-5 md:gap-6">
                <div class="w-full">
                    <InputText
                        v-model="mobilizon_name"
                        label="Name"
                        name="mobilizon_name"
                        :disabled="true"
                    />
                </div>
                <div class="col-12 md:col-6 px-0">
                    <InputText
                        v-model="mobilizon_preferred_username"
                        label="Benutzername"
                        name="mobilizon_preferred_username"
                        :disabled="true"
                    />
                </div>
            </div>

            <div class="flex flex-column md:flex-row gap-6">
                <div class="w-full flex flex-column gap-5">
                    <InputEmail
                        v-model="email"
                        label="E-Mail"
                        name="email"
                        :errors="submitCount === 0 ? undefined : errors.email"
                        :disabled="true"
                    />
                </div>
                <div class="md:col-6 px-0 mt-2">
                    <Alert
                        title="Information"
                        severity="info"
                    >
                        <p>Diese E-Mail-Adresse benötigen Sie für den Login.</p>
                    </Alert>
                </div>
            </div>
            <InputSelect
                v-model="type"
                label="Rolle (Instanz)"
                name="type"
                :options="user_types_options"
                :errors="submitCount === 0 ? undefined : errors.type"
            />
            <InputSelect
                v-model="is_active"
                label="Status"
                name="is_active"
                :options="user_is_active_options"
                :errors="submitCount === 0 ? undefined : errors.is_active"
            />
        </Fieldset>

        <ConfirmDialog
            v-model="showDeleteDialog"
            title="Willst du diesen Benutzer wirklich löschen?"
            @confirm="deleteUser"
            :confirmText="'Löschen'"
        />
        <div class="flex flex-wrap justify-content-center gap-4 mt-6">
            <Button
                type="submit"
                :disabled="isSubmitting"
            >
                Änderungen speichern
            </Button>
            <Button
                v-if="showDeleteOption"
                type="button"
                class="kern-button--danger"
                variant="secondary"
                icon-left="delete"
                @click="showDeleteDialog = true"
            >
                Benutzer löschen
            </Button>
        </div>
    </form>
</template>
