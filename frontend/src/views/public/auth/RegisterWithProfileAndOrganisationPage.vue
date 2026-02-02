<script setup lang="ts">
import zod from '@/lib/zod';
import { ref, watch } from 'vue';
import { checkAuthDsgApi, dsgApi } from '@/lib/dsgApi';
import { checkLogin, setUserData } from '@/composables/UserComposoable';
import { useRouter } from 'vue-router';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { stripHtml } from '@/lib/helper';
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

const router = useRouter();
const showErrorMessage = ref(false);
const errorMessageContent = ref('');

const validationSchema = toTypedSchema(
    zod
        .object({
            // Account
            email: zod
                .string({
                    required_error: 'Die E-Mail-Adresse ist erforderlich.',
                })
                .nonempty('Die E-Mail-Adresse ist erforderlich.')
                .email('Bitte geben Sie eine gültige E-Mail-Adresse ein.'),
            password: zod
                .string({
                    required_error: 'Das Passwort ist erforderlich.',
                })
                .nonempty('Das Passwort ist erforderlich.')
                .min(8, 'Das Passwort muss mindestens 8 Zeichen lang sein.')
                .trim(),
            password_confirmation: zod
                .string({
                    required_error: 'Die Passwortbestätigung ist erforderlich.',
                })
                .nonempty('Das Passwort ist erforderlich.')
                .min(8, 'Das Passwort muss mindestens 8 Zeichen lang sein.')
                .trim(),
            accept_terms: zod.boolean().refine((val) => val, {
                message: 'Die Nutzungsbedingungen und die Datenschutzerklärung müssen akzeptiert werden.',
            }),
            // Profile
            name: zod.string().nonempty('Der Name ist erforderlich.'),
            preferredUsername: zod
                .string()
                .nonempty('Der Benutzername ist erforderlich.')
                .regex(/^[a-z][a-z0-9_]*$/, {
                    message:
                        'Bitte einen gültigen Benutzernamen eingeben z.B.: Kleinbuchstaben ohne Umlaute, Zahlen oder Unterstrich.',
                }),
            // Organisation
            organisation_name: zod
                .string({
                    required_error: 'Der Name der Organisation ist erforderlich.',
                })
                .nonempty('Der Name der Organisation darf nicht leer sein.'),
            organisation_preferredUsername: zod
                .string({
                    required_error: 'Der Benutzername der Organisation ist erforderlich.',
                })
                .regex(/^[a-z][a-z0-9_]*$/, {
                    message:
                        'Bitte einen gültigen Benutzernamen eingeben z.B.: Kleinbuchstaben ohne Umlaute, Zahlen oder Unterstrich.',
                })
                .nonempty('Der Benutzername der Organisation darf nicht leer sein.'),
            organisation_summary: zod
                .string({
                    required_error: 'Die Beschreibung der Organisation ist erforderlich.',
                })
                .refine((val) => stripHtml(val).length > 0, { message: 'Die Beschreibung darf nicht leer sein.' }),
            })
        .superRefine(({ password, password_confirmation }, ctx) => {
            if (password !== password_confirmation) {
                ctx.addIssue({
                    path: ['password_confirmation'],
                    code: zod.ZodIssueCode.custom,
                    message: 'Die Passwörter stimmen nicht überein.',
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

const convertToUsername = (inputName: string): string => {
    let username = inputName
        .toLowerCase()
        .replace(/[^\w ]+/g, '')
        .replace(/ +/g, '_')
        .replace(/_+/g, '_');

    const lastChar = username.slice(-1);

    if (lastChar === '_') {
        username = username.substring(0, username.length - 1);
    }
    return username;
};

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
            'Registrierung konnte nicht durchgeführt werden.';
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
                title="Registrierung mit Profil und Organisation"
                class="p-2"
            >
                <Alert
                    v-if="showErrorMessage"
                    title="Fehler"
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
                            <h2 class="kern-heading text-theme-primary mb-3">Account</h2>
                            <div class="flex flex-column gap-3">
                                <InputEmail
                                    v-model="email"
                                    label="E-Mail-Adresse"
                                    name="email"
                                    required
                                    :errors="submitCount === 0 ? undefined : errors.email"
                                />
                                <InputPassword
                                    v-model="password"
                                    label="Passwort"
                                    name="password"
                                    :errors="submitCount === 0 ? undefined : errors.password"
                                />
                                <InputPassword
                                    v-model="password_confirmation"
                                    label="Passwort bestätigen"
                                    name="password_confirmation"
                                    :errors="submitCount === 0 ? undefined : errors.password_confirmation"
                                />
                                <InputCheckbox
                                    v-model="accept_terms"
                                    name="accept_terms"
                                    label="Nutzungsbedingungen"
                                    :errors="submitCount === 0 ? undefined : errors.accept_terms"
                                >
                                    <template #label>
                                        Ich habe die
                                        <RouterLink
                                            :to="{ name: 'public.terms' }"
                                            class="terms-link"
                                        >
                                            Nutzungsbedingungen
                                        </RouterLink>
                                        und
                                        <RouterLink
                                            :to="{ name: 'public.privacy' }"
                                            class="terms-link"
                                        >
                                            Datenschutzerklärung
                                        </RouterLink>
                                        gelesen und akzeptiere sie.
                                    </template>
                                </InputCheckbox>
                            </div>
                        </div>

                        <Divider />

                        <!-- Profile Section -->
                        <div>
                            <h2 class="kern-heading text-theme-primary mb-3">Profil</h2>
                            <p class="mb-4">
                                <b>Hinweis:</b>
                                Weitere Informationen finden Sie im
                                <LinkToDocs
                                    path="Terminverwaltung/Organisation"
                                    fragment="registrierung-fur-einzelpersonen-bzw-organisationsmitglieder"
                                />.
                            </p>
                            <div class="flex flex-column gap-4">
                                <div class="flex flex-column md:flex-row gap-4">
                                    <div class="flex-1">
                                        <InputText
                                            v-model="name"
                                            label="Ihr vollständiger Name"
                                            name="name"
                                            :errors="submitCount === 0 ? undefined : errors.name"
                                        />
                                    </div>
                                    <div class="flex-1">
                                        <Alert
                                            title="Information"
                                            severity="info"
                                        >
                                            <p>Ihr vollständiger Name kann später geändert werden.</p>
                                        </Alert>
                                    </div>
                                </div>

                                <div class="flex flex-column md:flex-row gap-4">
                                    <div class="flex-1">
                                        <InputText
                                            v-model="preferredUsername"
                                            label="Ihr Benutzername"
                                            name="preferredUsername"
                                            :errors="submitCount === 0 ? undefined : errors.preferredUsername"
                                        />
                                    </div>
                                    <div class="flex-1">
                                        <Alert
                                            title="Information"
                                            severity="info"
                                        >
                                            Ihr eindeutiger Benutzername wird benötigt, um Sie zu Organisationen
                                            einzuladen. Dieser kann später
                                            <b>nicht mehr geändert</b>
                                            werden.
                                        </Alert>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <Divider />

                        <!-- Organisation Section -->
                        <div>
                            <h2 class="kern-heading text-theme-primary mb-3">Organisation</h2>
                            <p class="mb-4">
                                <b>Hinweis:</b>
                                Mit diesem Formular stellen Sie eine Anfrage an die Betreiber*innen dieser
                                Kalenderinstanz. Weitere Informationen finden Sie im
                                <LinkToDocs
                                    path="Terminverwaltung/Organisation/"
                                    fragment="organisation-erstellen-zugang-fur-veranstalter-innen"
                                />.
                            </p>
                            <div class="flex flex-column gap-4">
                                <div class="flex flex-column md:flex-row gap-4">
                                    <div class="flex-1">
                                        <InputText
                                            v-model="organisation_name"
                                            label="Organisationsname"
                                            name="organisation_name"
                                            :errors="submitCount === 0 ? undefined : errors.organisation_name"
                                        />
                                    </div>
                                    <div class="flex-1">
                                        <Alert
                                            title="Information"
                                            severity="info"
                                        >
                                            <p>Geben Sie den offiziellen Namen Ihrer Organisation ein.</p>
                                        </Alert>
                                    </div>
                                </div>

                                <div class="flex flex-column md:flex-row gap-4">
                                    <div class="flex-1">
                                        <InputText
                                            v-model="organisation_preferredUsername"
                                            label="Benutzername der Organisation"
                                            name="organisation_preferredUsername"
                                            :errors="
                                                submitCount === 0 ? undefined : errors.organisation_preferredUsername
                                            "
                                        />
                                    </div>
                                    <div class="flex-1">
                                        <Alert
                                            title="Information"
                                            severity="info"
                                        >
                                            <p>Der Benutzername kann später nicht geändert werden.</p>
                                            <LinkToDocs
                                                path="Terminverwaltung/Organisation/"
                                                fragment="schritt-2-formular-zur-organisationserstellung"
                                            />
                                        </Alert>
                                    </div>
                                </div>

                                <InputRichText
                                    v-model="organisation_summary"
                                    label="Beschreibung (für die öffentliche Organisationsseite)"
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
                            Registrierung abschließen
                        </Button>
                    </form>
                </Fieldset>

                <div class="text-sm mt-4 text-center">
                    <RouterLink :to="{ name: 'login' }">Zurück zum Login</RouterLink>
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
