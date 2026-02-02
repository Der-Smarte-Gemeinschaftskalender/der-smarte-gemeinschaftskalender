<script lang="ts" setup>
import { ref, watch } from 'vue';
import { dsgApi } from '@/lib/dsgApi';
import { useRouter, useRoute } from 'vue-router';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { ZodType } from 'zod';
import zod from '@/lib/zod';
import Fieldset from '@/components/KERN/Fieldset.vue';
import InputText from '@/components/KERN/inputs/InputEmail.vue';
import Button from '@/components/KERN/Button.vue';
import Alert from '@/components/KERN/Alert.vue';
import Divider from '@/components/KERN/cosmetics/Divider.vue';
import { setUserPersonData, user } from '@/composables/UserComposoable';
import LinkToDocs from '@/components/LinkToDocs.vue';

const router = useRouter();
const showErrorMessage = ref(false);
const errorMessageContent = ref('');

interface RegisterPersonForm {
    preferredUsername: string;
    name: string;
}

const validationSchema = toTypedSchema(
    zod.object({
        name: zod.string().nonempty(),
        preferredUsername: zod
            .string()
            .nonempty()
            .regex(/^[a-z][a-z0-9_]*$/, {
                message:
                    'Bitte einen gültigen Benutzernamen eingeben z.B.: Kleinbuchstaben ohne Umlaute, Zahlen oder Unterstrich.',
            }),
    }) satisfies ZodType<RegisterPersonForm>
);
const { handleSubmit, errors, isSubmitting, submitCount } = useForm({
    validationSchema,
});
const { value: name } = useField<string>('name');
const { value: preferredUsername } = useField<string>('preferredUsername');

const onSubmit = handleSubmit(async (values) => {
    try {
        const { data } = await dsgApi.post('/user/registerPerson', <RegisterPersonForm>{
            ...values,
        });
        setUserPersonData(data.data);
        await new Promise((r) => setTimeout(r, 1000));
        router.push('/app');
    } catch (error) {
        showErrorMessage.value = true;
        errorMessageContent.value =
            error?.response?.data?.error ||
            'Es ist ein Fehler aufgetreten. Bevor du deinen Benutzer weiter einrichten kannst, musst du erst deine E-Mail-Adresse bestätigen.';
    }
});
const convertToUsername = (name: string): string => {
    let username = name
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
watch(name, async (newName, oldName) => {
    preferredUsername.value = convertToUsername(newName);
});
</script>
<template>
    <h1 class="kern-heading text-theme-primary">Ihr persönliches Profil einrichten</h1>
    <p class="mt-1 mb-6">
        <b>Hinweis:</b>
        Nutzen Sie zur Profileinrichtung Ihre persönlichen Daten. Verwenden Sie nicht den Organisationsnamen. Ihr
        persönliches Profil wird benötigt, um die Erstellung einer Organisation anzufragen. Eine Organisation kann von
        mehreren Einzelnutzer*innen verwaltet werden.
        <br />
        <br />
        Weitere Informationen zur Profileinrichtung finden Sie im
        <LinkToDocs
            path="Terminverwaltung/Organisation"
            fragment="registrierung-fur-einzelpersonen-bzw-organisationsmitglieder"
        />.
    </p>
    <Divider class="mt-4 mb-4" />
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
                <div class="flex flex-column md:flex-row gap-6flex flex-column md:flex-row gap-6">
                    <div class="w-full">
                        <InputText
                            v-model="name"
                            label="Name"
                            name="name"
                            placeholder="Vorname Nachname"
                            :errors="submitCount === 0 ? undefined : errors.name"
                        />
                    </div>
                    <div class="md:col-7 px-0 mt-2">
                        <Alert
                            title="Information"
                            severity="info"
                        >
                            <p>
                                Geben Sie möglichst Ihren Name an (z. B.: Vorname Nachname). Ihr Name wird im Profil
                                angezeigt. Dieser kann später geändert werden.
                            </p>
                        </Alert>
                    </div>
                </div>
                <Divider />
                <div class="flex flex-column md:flex-row gap-6flex flex-column md:flex-row gap-6">
                    <div class="w-full">
                        <InputText
                            v-model="preferredUsername"
                            label="Persönlicher Benutzername"
                            name="preferredUsername"
                            placeholder="vorname_nachname"
                            :errors="submitCount === 0 ? undefined : errors.preferredUsername"
                        />
                    </div>

                    <div class="md:col-7 px-0 mt-2">
                        <Alert
                            title="Information"
                            severity="info"
                        >
                            <p class="mb-5">
                                Bitte geben Sie eine Kombination aus Ihrem Vor- und Nachnamen
                                <b>Vor- und Nachnamen</b>
                                an (z. B. beate_beispiel) oder Ihrem Rufnamen an.
                            </p>

                            <p class="pt-2">
                                Verwenden Sie
                                <b>keinen Organisationsnamen</b>
                                , da dieser Benutzername immer Ihrem persönlichen Profil zugeordnet ist. Der
                                Benutzername wird benötigt, um Sie zu Organisationen einzuladen, und kann später
                                <b>nicht mehr geändert werden</b>
                                . Bitte wählen Sie ihn sorgfältig.
                            </p>
                        </Alert>
                    </div>
                </div>
            </div>
        </Fieldset>

        <div class="flex justify-content-center">
            <Button
                :disabled="isSubmitting"
                class="mt-5"
                type="submit"
                icon-left="add"
            >
                Profil erstellen
            </Button>
        </div>
    </form>
</template>
