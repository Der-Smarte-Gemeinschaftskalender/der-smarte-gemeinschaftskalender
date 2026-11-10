<script setup lang="ts">
import zod from '@/lib/zod';
import { ref, watch } from 'vue';
import { I18nT, useI18n } from 'vue-i18n';
import { checkAuthDsgApi, dsgApi } from '@/lib/dsgApi';
import { checkLogin, setUserData } from '@/composables/UserComposoable';
import { useRouter } from 'vue-router';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { stripHtml, convertToUsername, preferredUsernameSchema } from '@/lib/helper';
import { setOrganisationData } from '@/composables/OrganisationComposable';

import Card from '@/components/KERN/Card.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import InputEmail from '@/components/KERN/inputs/InputEmail.vue';
import InputPassword from '@/components/KERN/inputs/InputPassword.vue';
import InputText from '@/components/KERN/inputs/InputText.vue';
import InputRichText from '@/components/KERN/inputs/InputRichText.vue';
import Button from '@/components/KERN/Button.vue';
import Alert from '@/components/KERN/Alert.vue';
import InputCheckbox from '@/components/KERN/inputs/InputCheckbox.vue';
import Divider from '@/components/KERN/cosmetics/Divider.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';

interface RegisterWithProfileAndOrganisationResponse {
    access_token: string;
    user: any;
    person: any;
}

const { t } = useI18n();
const router = useRouter();
const showErrorMessage = ref(false);
const errorMessageContent = ref('');

const validationSchema = toTypedSchema(
    zod
        .object({
            // Account
            email: zod
                .string({
                    required_error: 'auth.validation.emailRequired',
                })
                .nonempty('auth.validation.emailRequired')
                .email('auth.validation.emailInvalid'),
            password: zod
                .string({
                    required_error: 'auth.validation.passwordRequired',
                })
                .nonempty('auth.validation.passwordRequired')
                .min(8, 'auth.validation.passwordMin8')
                .trim(),
            password_confirmation: zod
                .string({
                    required_error: 'auth.validation.passwordConfirmationRequired',
                })
                .nonempty('auth.validation.passwordRequired')
                .min(8, 'auth.validation.passwordMin8')
                .trim(),
            accept_terms: zod.boolean().refine((val) => val, {
                message: 'auth.validation.acceptTerms',
            }),
            // Profile
            name: zod.string().nonempty('auth.validation.nameRequired'),
            preferredUsername: preferredUsernameSchema.nonempty('auth.validation.usernameRequired'),
            // Organisation
            organisation_name: zod
                .string({
                    required_error: 'app.organisations.validation.nameRequired',
                })
                .nonempty('app.organisations.validation.nameNotEmpty'),
            organisation_preferredUsername: preferredUsernameSchema.nonempty('auth.validation.organisationUsernameNotEmpty'),
            organisation_summary: zod
                .string({
                    required_error: 'app.organisations.validation.summaryRequired',
                })
                .refine((val) => stripHtml(val).length > 0, { message: 'app.organisations.validation.summaryNotEmpty' }),
            })
        .superRefine(({ password, password_confirmation }, ctx) => {
            if (password !== password_confirmation) {
                ctx.addIssue({
                    path: ['password_confirmation'],
                    code: zod.ZodIssueCode.custom,
                    message: 'auth.validation.passwordsDoNotMatch',
                });
            }
        })
);

const { handleSubmit, errors, isSubmitting, submitCount } = useForm({
    validationSchema,
    initialValues: {
        email: '',
        password: '',
        password_confirmation: '',
        accept_terms: false,
        name: '',
        preferredUsername: '',
        organisation_name: '',
        organisation_preferredUsername: '',
        organisation_summary: '',
    },
});

const { value: email } = useField<string>('email');
const { value: password } = useField<string>('password');
const { value: password_confirmation } = useField<string>('password_confirmation');
const { value: accept_terms } = useField<boolean>('accept_terms');
const { value: name } = useField<string>('name');
const { value: preferredUsername } = useField<string>('preferredUsername');
const { value: organisation_name } = useField<string>('organisation_name');
const { value: organisation_preferredUsername } = useField<string>('organisation_preferredUsername');
const { value: organisation_summary } = useField<string>('organisation_summary');

watch(name, (newName) => {
    preferredUsername.value = convertToUsername(newName);
});

watch(organisation_name, (newName) => {
    organisation_preferredUsername.value = convertToUsername(newName);
});

const onSubmit = handleSubmit(async (values) => {
    try {
        showErrorMessage.value = false;

        const { data }: { data: RegisterWithProfileAndOrganisationResponse } = await dsgApi.post(
            '/auth/register-with-profile-and-organisation',
            {
                email: values.email,
                password: values.password,
                password_confirmation: values.password_confirmation,
                name: values.name,
                preferredUsername: values.preferredUsername,
                organisation_name: values.organisation_name,
                organisation_preferredUsername: values.organisation_preferredUsername,
                organisation_summary: values.organisation_summary,
            }
        );

        setUserData(data.user, data.access_token, data.person);
        checkAuthDsgApi();
        await setOrganisationData(); // Not needed cause orga list is empty, but just in case

        await router.push({
            name: 'app.myOrganisations',
        });
    } catch (error: any) {
        console.error(error);
        showErrorMessage.value = true;
        errorMessageContent.value =
            error?.response?.data?.message ||
            error?.response?.data?.error ||
            t('auth.registerWithOrganisation.error');
    }
});

if (checkLogin()) {
    router.push('/app');
}
</script>

<template>
    <div class="flex align-self-center align-items-center justify-content-center">
        <div class="register-card">
            <Card
                :title="t('auth.registerWithOrganisation.title')"
                class="p-2"
            >
                <Alert
                    v-if="showErrorMessage"
                    :title="t('common.error')"
                    :content="errorMessageContent"
                    severity="danger"
                    class="mb-4 w-full"
                />

                <Fieldset class="px-1">
                    <form
                        novalidate
                        class="flex flex-column gap-4"
                        @submit.prevent="onSubmit"
                    >
                        <!-- Account Section -->
                        <div>
                            <h2 class="kern-heading text-theme-primary mb-3">{{ t('auth.registerWithOrganisation.account') }}</h2>
                            <div class="flex flex-column gap-3">
                                <InputEmail
                                    v-model="email"
                                    :label="t('auth.email')"
                                    name="email"
                                    required
                                    :errors="submitCount === 0 ? undefined : errors.email"
                                />
                                <InputPassword
                                    v-model="password"
                                    :label="t('auth.password')"
                                    name="password"
                                    :errors="submitCount === 0 ? undefined : errors.password"
                                />
                                <InputPassword
                                    v-model="password_confirmation"
                                    :label="t('auth.passwordConfirm')"
                                    name="password_confirmation"
                                    :errors="submitCount === 0 ? undefined : errors.password_confirmation"
                                />
                                <InputCheckbox
                                    v-model="accept_terms"
                                    name="accept_terms"
                                    :label="t('auth.terms.label')"
                                    :errors="submitCount === 0 ? undefined : errors.accept_terms"
                                >
                                    <template #label>
                                        <I18nT scope="global" keypath="auth.terms.text">
                                            <template #terms>
                                                <RouterLink
                                                    :to="{ name: 'public.terms' }"
                                                    class="terms-link"
                                                >
                                                    {{ t('auth.terms.terms') }}
                                                </RouterLink>
                                            </template>
                                            <template #privacy>
                                                <RouterLink
                                                    :to="{ name: 'public.privacy' }"
                                                    class="terms-link"
                                                >
                                                    {{ t('auth.terms.privacy') }}
                                                </RouterLink>
                                            </template>
                                        </I18nT>
                                    </template>
                                </InputCheckbox>
                            </div>
                        </div>

                        <Divider />

                        <!-- Profile Section -->
                        <div>
                            <h2 class="kern-heading text-theme-primary mb-3">{{ t('auth.registerWithOrganisation.profile') }}</h2>
                            <p class="mb-4">
                                <b>{{ t('auth.hint') }}</b> <I18nT scope="global" keypath="auth.registerWithOrganisation.profileHint">
                                    <template #docs>
                                        <LinkToDocs
                                            path="Terminverwaltung/Organisation/"
                                            fragment="registrierung-fur-einzelpersonen-bzw-organisationsmitglieder"
                                        />
                                    </template>
                                </I18nT>
                            </p>
                            <div class="flex flex-column gap-4">
                                <div class="flex flex-column md:flex-row gap-4">
                                    <div class="flex-1">
                                        <InputText
                                            v-model="name"
                                            :label="t('auth.registerWithOrganisation.fullName')"
                                            name="name"
                                            :errors="submitCount === 0 ? undefined : errors.name"
                                        />
                                    </div>
                                    <div class="flex-1">
                                        <Alert
                                            :title="t('auth.information')"
                                            severity="info"
                                        >
                                            <p>{{ t('auth.registerWithOrganisation.fullNameInfo') }}</p>
                                        </Alert>
                                    </div>
                                </div>

                                <div class="flex flex-column md:flex-row gap-4">
                                    <div class="flex-1">
                                        <InputText
                                            v-model="preferredUsername"
                                            :label="t('auth.registerWithOrganisation.username')"
                                            name="preferredUsername"
                                            :errors="submitCount === 0 ? undefined : errors.preferredUsername"
                                        />
                                    </div>
                                    <div class="flex-1">
                                        <Alert
                                            :title="t('auth.information')"
                                            severity="info"
                                        >
                                            <I18nT scope="global" keypath="auth.registerWithOrganisation.usernameInfo">
                                                <template #notChangeable>
                                                    <b>{{ t('auth.registerWithOrganisation.usernameInfoNotChangeable') }}</b>
                                                </template>
                                            </I18nT>
                                        </Alert>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <Divider />

                        <!-- Organisation Section -->
                        <div>
                            <h2 class="kern-heading text-theme-primary mb-3">{{ t('auth.registerWithOrganisation.organisation') }}</h2>
                            <p class="mb-4">
                                <b>{{ t('auth.hint') }}</b> <I18nT scope="global" keypath="auth.registerWithOrganisation.organisationHint">
                                    <template #docs>
                                        <LinkToDocs
                                            path="Terminverwaltung/Organisation/"
                                            fragment="organisation-erstellen-zugang-fur-veranstalter-innen"
                                        />
                                    </template>
                                </I18nT>
                            </p>
                            <div class="flex flex-column gap-4">
                                <div class="flex flex-column md:flex-row gap-4">
                                    <div class="flex-1">
                                        <InputText
                                            v-model="organisation_name"
                                            :label="t('auth.registerWithOrganisation.organisationName')"
                                            name="organisation_name"
                                            :errors="submitCount === 0 ? undefined : errors.organisation_name"
                                        />
                                    </div>
                                    <div class="flex-1">
                                        <Alert
                                            :title="t('auth.information')"
                                            severity="info"
                                        >
                                            <p>{{ t('auth.registerWithOrganisation.organisationNameInfo') }}</p>
                                        </Alert>
                                    </div>
                                </div>

                                <div class="flex flex-column md:flex-row gap-4">
                                    <div class="flex-1">
                                        <InputText
                                            v-model="organisation_preferredUsername"
                                            :label="t('auth.registerWithOrganisation.organisationUsername')"
                                            name="organisation_preferredUsername"
                                            :errors="
                                                submitCount === 0 ? undefined : errors.organisation_preferredUsername
                                            "
                                        />
                                    </div>
                                    <div class="flex-1">
                                        <Alert
                                            :title="t('auth.information')"
                                            severity="info"
                                        >
                                            <p>{{ t('auth.registerWithOrganisation.organisationUsernameInfo') }}</p>
                                            <LinkToDocs
                                                path="Terminverwaltung/Organisation/"
                                                fragment="schritt-2-formular-zur-organisationserstellung"
                                            />
                                        </Alert>
                                    </div>
                                </div>

                                <InputRichText
                                    v-model="organisation_summary"
                                    :label="t('auth.registerWithOrganisation.organisationSummary')"
                                    name="organisation_summary"
                                    :errors="submitCount === 0 ? undefined : errors.organisation_summary"
                                />
                            </div>
                        </div>

                        <Button
                            type="submit"
                            class="mt-3"
                            :disabled="isSubmitting"
                            icon-left="add"
                        >
                            {{ t('auth.registerWithOrganisation.submit') }}
                        </Button>
                    </form>
                </Fieldset>

                <div class="text-sm mt-4 text-center">
                    <RouterLink :to="{ name: 'login' }">{{ t('auth.backToLogin') }}</RouterLink>
                </div>
            </Card>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.register-card {
    max-width: 900px !important;
    width: 100%;
}

.terms-link {
    color: #111;
    text-decoration: underline;
}
</style>
