<script lang="ts" setup>
import { ref, watch } from 'vue';
import zod from '@/lib/zod';
import { useRouter } from 'vue-router';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { dsgApi } from '@/lib/dsgApi';

import Fieldset from '@/components/KERN/Fieldset.vue';
import InputText from '@/components/KERN/inputs/InputEmail.vue';
import InputTextarea from '@/components/KERN/inputs/InputTextarea.vue';
import Button from '@/components/KERN/Button.vue';
import Alert from '@/components/KERN/Alert.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';

import type { ZodType } from 'zod';
import type { AxiosError } from 'axios';

const router = useRouter();
const showErrorMessage = ref(false);
const errorMessageContent = ref('');

interface RequestOrganisationForm {
    preferredUsername: string;
    name: string;
    summary: string;
}

const validationSchema = toTypedSchema(
    zod.object({
        name: zod
            .string({
                required_error: 'Der Name der Organisation ist erforderlich.',
            })
            .nonempty('Der Name der Organisation darf nicht leer sein.'),
        preferredUsername: zod
            .string({
                required_error: 'Der Benutzername der Organisation ist erforderlich.',
            })
            .nonempty('Der Benutzername der Organisation darf nicht leer sein.'),
        summary: zod
            .string({
                required_error: 'Die Beschreibung der Organisation ist erforderlich.',
            })
            .nonempty('Die Beschreibung der Organisation darf nicht leer sein.'),
    }) satisfies ZodType<RequestOrganisationForm>
);
const { handleSubmit, errors, isSubmitting, submitCount } = useForm({
    validationSchema,
});
const { value: name } = useField<string>('name');
const { value: preferredUsername } = useField<string>('preferredUsername');
const { value: summary } = useField<string>('summary');

const onSubmit = handleSubmit(async (values) => {
    try {
        await dsgApi.post('/organisations/requestOrganisationCreation', <RequestOrganisationForm>{
            ...values,
        });

        await router.push({
            name: 'app.myOrganisations',
        });
    } catch (error: any | AxiosError) {
        showErrorMessage.value = true;
        errorMessageContent.value = error?.response?.data?.error || 'Es ist ein Fehler aufgetreten.';
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
watch(name, async (newName) => {
    preferredUsername.value = convertToUsername(newName);
});
</script>
<template>
    <h1 class="kern-heading text-theme-primary">Organisation erstellen</h1>
    <p class="mt-1 mb-6">
        <b>Hinweis:</b>
        Mit diesem Formular stellen Sie eine Anfrage an die Betreiber*innen dieser Kalenderinstanz. Sobald Ihre Anfrage
        genehmigt ist, können Sie für Ihre Organisation Termine erstellen und Organisationsmitglieder hinzufügen.
        Weitere Informationen finden Sie im
        <LinkToDocs
            path="Terminverwaltung/Organisation/"
            fragment="organisation-erstellen-zugang-fur-veranstalter-innen"
        />
        .
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
                <div class="flex flex-column md:flex-row gap-6">
                    <InputText
                        v-model="name"
                        class="w-full"
                        label="Organisationsname"
                        name="name"
                        :errors="submitCount === 0 ? undefined : errors.name"
                    />
                    <div class="md:col-6 px-0 mt-2">
                        <Alert
                            title="Information"
                            severity="info"
                        >
                            <p>
                                Geben Sie den offiziellen Namen Ihrer Organisation ein, unter dem Veranstaltungen
                                veröffentlicht werden sollen.
                            </p>
                        </Alert>
                    </div>
                </div>
                <div class="flex flex-column md:flex-row gap-6">
                    <InputText
                        v-model="preferredUsername"
                        class="w-full"
                        label="Benutzername der Organisation"
                        name="preferredUsername"
                        :errors="submitCount === 0 ? undefined : errors.preferredUsername"
                    />

                    <div class="md:col-6 px-0 mt-2">
                        <Alert
                            title="Information"
                            severity="info"
                        >
                            <p>
                                Der Benutzername ist erforderlich, um die Organisation eindeutig im System zu
                                identifizieren. Dieser kann später nicht geändert werden.
                            </p>
                            <p>
                                Weitere Informationen zur Wahl eines Benutzernamens finden Sie im

                                <LinkToDocs
                                    path="Terminverwaltung/Organisation/"
                                    fragment="schritt-2-formular-zur-organisationserstellung"
                                />
                                .
                            </p>
                        </Alert>
                    </div>
                </div>

                <InputTextarea
                    v-model="summary"
                    label="Beschreibung (für die öffentliche Organisationsseite)"
                    name="summary"
                    :errors="submitCount === 0 ? undefined : errors.summary"
                />
            </div>
        </Fieldset>

        <div class="flex justify-content-center mt-5">
            <Button
                :disabled="isSubmitting"
                type="submit"
                icon-left="add"
            >
                Organisationserstellung anfragen
            </Button>
        </div>
    </form>
</template>
