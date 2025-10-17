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
            .regex(/^[a-zA-Z0-9_]+$/, 'Der Benutzername darf nur Buchstaben und Zahlen enthalten.'),
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

    let lastChar = username.slice(-1);

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
    <h2 class="kern-heading text-theme-primary">Benutzer einrichten</h2>
    <p class="mt-1 mb-6">
        <b>Hinweis:</b>
        Weitere Informationen finden Sie im
        <LinkToDocs
            path="Terminverwaltung/Organisation"
            fragment="registrierung-fur-einzelpersonen-bzw-organisationsmitglieder"
        />
        .
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
                <div class="kern-row">
                    <div class="kern-col-xl-6">
                        <InputText
                            v-model="name"
                            label="Name"
                            name="name"
                            :errors="submitCount === 0 ? undefined : errors.name"
                        />
                    </div>
                    <div class="kern-col-xl-6">
                        <Alert
                            title="Information"
                            severity="info"
                        >
                            <p>Ihr vollständiger Name kann später geändert werden.</p>
                        </Alert>
                    </div>
                </div>
                <Divider />
                <div class="kern-row">
                    <div class="kern-col-xl-6">
                        <InputText
                            v-model="preferredUsername"
                            label="Benutzername"
                            name="preferredUsername"
                            :errors="submitCount === 0 ? undefined : errors.preferredUsername"
                        />
                    </div>

                    <div class="kern-col-xl-6">
                        <Alert
                            title="Information"
                            severity="info"
                        >
                            Ihr eindeutiger Benutzername wird benötigt, um Sie zu Organisationen einzuladen. Dieser kann
                            später
                            <b>nicht mehr geändert</b>
                            werden. Bitte wählen Sie sorgfältig.
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
