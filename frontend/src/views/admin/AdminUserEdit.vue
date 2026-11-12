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
import { I18nT, useI18n } from 'vue-i18n';
import zod from '@/lib/zod';
import { ZodType } from 'zod';
import { user_types_options, user_is_active_options } from '@/lib/const';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import Alert from '@/components/KERN/Alert.vue';
import axios from 'axios';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { user } from '@/composables/UserComposoable';
import { localeOptions, SUPPORTED_LOCALES, toSupportedLocale, type SupportedLocale } from '@/i18n';

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
    locale?: SupportedLocale | null;
}

const { t } = useI18n();
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
        locale: zod.enum(SUPPORTED_LOCALES).optional(),
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
const { value: locale } = useField<SupportedLocale>('locale');

const loadUser = async () => {
    try {
        const data = await dsgApi.get(`/admin/user/${userID}`);
        const user: User = data.data;

        email.value = user.email;
        type.value = user.type;
        is_active.value = user.is_active;
        mobilizon_name.value = user.mobilizon_name || '';
        mobilizon_preferred_username.value = user.mobilizon_preferred_username || '';
        // A language disabled on this instance since it was stored is shown (and saved) as the default
        locale.value = toSupportedLocale(user.locale);
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
            errorMessageContent.value = t('app.admin.users.saveError');
        }
    }
});

loadUser();
</script>
<template>
    <h2 class="kern-heading text-theme-primary">{{ t('app.admin.users.editTitle') }}</h2>
    <p class="mb-4">
        <b>{{ t('app.admin.shared.note') }}</b> <I18nT
            keypath="app.admin.users.editHint"
            scope="global"
        >
            <template #link>
                <LinkToDocs
                    path="Terminverwaltung/Instanz/"
                    fragment="nutzer-innen-verwalten"
                />
            </template>
        </I18nT>
    </p>
    <Alert
        v-if="showErrorMessage"
        :title="t('common.error')"
        :content="errorMessageContent"
        severity="danger"
    />
    <Alert
        v-if="showSuccessMessage"
        :title="t('common.success')"
        :content="t('app.admin.users.updateSuccess')"
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
                        :label="t('app.admin.users.columns.name')"
                        name="mobilizon_name"
                        :disabled="true"
                    />
                </div>
                <div class="col-12 md:col-6 px-0">
                    <InputText
                        v-model="mobilizon_preferred_username"
                        :label="t('app.admin.users.columns.username')"
                        name="mobilizon_preferred_username"
                        :disabled="true"
                    />
                </div>
            </div>

            <div class="flex flex-column md:flex-row gap-6">
                <div class="w-full flex flex-column gap-5">
                    <InputEmail
                        v-model="email"
                        :label="t('app.admin.users.columns.email')"
                        name="email"
                        :errors="submitCount === 0 ? undefined : errors.email"
                        :disabled="true"
                    />
                </div>
                <div class="md:col-6 px-0 mt-2">
                    <Alert
                        :title="t('app.admin.shared.information')"
                        severity="info"
                    >
                        <p>{{ t('app.admin.users.emailLoginInfo') }}</p>
                    </Alert>
                </div>
            </div>
            <InputSelect
                v-model="type"
                :label="t('app.admin.users.columns.role')"
                name="type"
                :options="user_types_options"
                :errors="submitCount === 0 ? undefined : errors.type"
            />
            <InputSelect
                v-model="is_active"
                :label="t('app.admin.users.columns.status')"
                name="is_active"
                :options="user_is_active_options"
                :errors="submitCount === 0 ? undefined : errors.is_active"
            />
            <InputSelect
                v-model="locale"
                :label="t('app.admin.users.emailLanguage')"
                name="locale"
                :options="localeOptions"
                :errors="submitCount === 0 ? undefined : errors.locale"
            />
        </Fieldset>

        <ConfirmDialog
            v-model="showDeleteDialog"
            :title="t('app.admin.users.deleteConfirm')"
            :confirm-text="t('common.delete')"
            @confirm="deleteUser"
        />
        <div class="flex flex-wrap justify-content-center gap-4 mt-6">
            <Button
                type="submit"
                :disabled="isSubmitting"
            >
                {{ t('app.admin.shared.saveChanges') }}
            </Button>
            <Button
                v-if="showDeleteOption"
                type="button"
                class="kern-button--danger"
                variant="secondary"
                icon-left="delete"
                @click="showDeleteDialog = true"
            >
                {{ t('app.admin.users.deleteButton') }}
            </Button>
        </div>
    </form>
</template>
