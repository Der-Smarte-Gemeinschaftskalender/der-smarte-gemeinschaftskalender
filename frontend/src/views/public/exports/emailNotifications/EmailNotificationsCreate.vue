<script lang="ts" setup>
import zod from '@/lib/zod';
import { ref } from 'vue';
import { toTypedSchema } from '@vee-validate/zod';
import { useField, useForm } from 'vee-validate';
import { axiosErrorHandler, dsgApi } from '@/lib/dsgApi';
import { intervall_notification_options, mobilizon_category_options_all } from '@/lib/const';
import { findOrganisationOptions } from '@/lib/mobilizonClient';

import Fieldset from '@/components/KERN/Fieldset.vue';
import InputSelect from '@/components/KERN/inputs/InputSelect.vue';
import InputText from '@/components/KERN/inputs/InputText.vue';
import InputRadios from '@/components/KERN/inputs/InputRadios.vue';
import Divider from '@/components/KERN/cosmetics/Divider.vue';
import Alert from '@/components/KERN/Alert.vue';

import { Intervall, MobilizonCategoryAndAll, type Option } from '@/types/General';
import Icon from '@/components/KERN/cosmetics/Icon.vue';
import Button from '@/components/KERN/Button.vue';
import InputLocation from '@/components/KERN/inputs/InputLocation.vue';
import InputCheckbox from '@/components/KERN/inputs/InputCheckbox.vue';

enum EventType {
    INTERNAL = 'INTERNAL',
    GLOBAL = 'GLOBAL',
}

enum Radius {
    '5 km' = 5,
    '10 km' = 10,
    '25 km' = 25,
    '50 km' = 50,
    '100 km' = 100,
    '250 km' = 250,
}

const events = [
    { label: 'Nur Veranstaltungen aus diesem Kalender', value: 'INTERNAL' },
    { label: 'Alle Veranstaltungen aus dem Netzwerk anzeigen', value: 'GLOBAL' },
];

const defaultField = {
    intervall: Intervall.WEEKLY,
    category: MobilizonCategoryAndAll.ALL,
    eventType: EventType.INTERNAL,
    email: '',
    organisation: undefined,
    postal_code: '',
    radius: Radius['25 km'],
};

const showConfirmEmail = ref(false);
const organisationOptions = ref<Option[]>([]);

const validationSchema = toTypedSchema(
    zod
        .object({
            intervall: zod.nativeEnum(Intervall),
            category: zod.nativeEnum(MobilizonCategoryAndAll),
            eventType: zod.nativeEnum(EventType),
            email: zod.string().email().nonempty(),
            organisation: zod.string().optional(),
            postal_code: zod.string().optional(),
            radius: zod.nativeEnum(Radius).optional(),
            accept_terms: zod
                .boolean()
                .default(false)
                .refine((val) => val === true, {
                    message: 'Die Nutzungsbedingungen müssen akzeptiert werden.',
                }),
        })
        // This is a custom refinement to validate the form based on the event type
        .superRefine((data, ctx) => {
            if (data.eventType === EventType.GLOBAL) {
                if (!data.postal_code || !/^\d{5}$/.test(data.postal_code)) {
                    ctx.addIssue({
                        path: ['postal_code'],
                        code: zod.ZodIssueCode.custom,
                        message: 'Bitte geben Sie eine gültige Postleitzahl ein',
                    });
                }
                if (data.radius === undefined || !Object.values(Radius).includes(data.radius)) {
                    ctx.addIssue({
                        path: ['radius'],
                        code: zod.ZodIssueCode.custom,
                        message: 'Bitte wählen Sie einen gültigen Radius aus',
                    });
                }
            }
        })
);

const { handleSubmit, errors, submitCount } = useForm({
    validationSchema,
    initialValues: defaultField,
});

const { value: intervall } = useField<Intervall>('intervall');
const { value: category } = useField<typeof MobilizonCategoryAndAll>('category');
const { value: eventType } = useField<string>('eventType');
const { value: email } = useField<string>('email');
const { value: organisation } = useField<string>('organisation');
const { value: radius } = useField<number>('radius');
const { value: postal_code } = useField<string>('postal_code');
const { value: accept_terms } = useField<boolean>('accept_terms');

const isSubmitting = ref(false);
const errorMessageContent = ref('');
const locationSearchRef = ref<InstanceType<typeof InputLocation> | null>(null);

const submitForm = handleSubmit(async (values) => {
    isSubmitting.value = true;

    try {
        const data = await dsgApi.post('notifications', {
            intervall: values.intervall,
            category: values.category,
            eventType: values.eventType,
            email: values.email,
            organisation: values.eventType === EventType.INTERNAL ? values.organisation : undefined,
            location_hash: values.eventType === EventType.GLOBAL ? locationSearchRef.value?.geoHash : undefined,
            address: values.eventType === EventType.GLOBAL ? values.postal_code : undefined,
            radius: values.eventType === EventType.GLOBAL ? values.radius : undefined,
        });

        showConfirmEmail.value = true;
    } catch (error: any) {
        console.error('Error submitting form:', error);
        errorMessageContent.value =
            axiosErrorHandler(error) && error?.response?.data?.error
                ? error.response.data.error
                : 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
    } finally {
        isSubmitting.value = false;
    }
});

findOrganisationOptions().then((options) => {
    organisationOptions.value = options;
    organisation.value = options[0]?.value || null;
});
</script>
<template>
    <Teleport to="#headerslot">
        <div class="mb-3 mt-4 sm:mb-4 sm:mt-5 md:my-6">
            <h1 class="kern-heading text-theme-primary">E-Mail-Benachrichtigungen abonnieren</h1>
            <h2 class="kern-heading font-semilight text-theme-primary">Stellen Sie ihr persönliches Abo zusammen</h2>
        </div>
    </Teleport>

    <template v-if="!showConfirmEmail">
        <form
            class="fieldset col-12 md:col-11 lg:col-10 xl:col-8 mt-7"
            @submit="submitForm"
        >
            <Fieldset class="mb-8">
                <section class="flex flex-column md:flex-row justify-content-between gap-6">
                    <InputSelect
                        class="w-full"
                        name="intervall"
                        label="Intervall"
                        :options="intervall_notification_options"
                        v-model="intervall"
                        :errors="submitCount === 0 ? undefined : errors.intervall"
                    />
                    <InputSelect
                        class="w-full"
                        name="category"
                        label="Kategorien"
                        :options="mobilizon_category_options_all"
                        v-model="category"
                        :errors="submitCount === 0 ? undefined : errors.category"
                    />
                </section>

                <Divider class="w-full my-6" />

                <section class="">
                    <div class="flex flex-column-reverse md:flex-row justify-content-between gap-6 mb-4 md:mb-0">
                        <InputRadios
                            class="w-full mb-5"
                            name="event-types"
                            label="Welche Veranstaltungen sollen angezeigt werden?"
                            :radios="events"
                            v-model="eventType"
                            :errors="submitCount === 0 ? undefined : errors.eventType"
                        ></InputRadios>

                        <div class="w-full">
                            <Alert
                                title="Hinweis"
                                type="info"
                            >
                                <p v-if="eventType === EventType.GLOBAL">
                                    <span>"Alle Veranstaltungen aus dem Netzwerk"</span>
                                    <span>
                                        bedeutet, dass Sie zusätzlich Termine aus anderen Gemeinden undOrganisationen
                                        sehen, die einige Kalender betreiben.
                                    </span>
                                </p>
                                <p v-else>
                                    <span>"Nur Veranstaltungen aus diesem Kalender"</span>
                                    <span>bedeutet, dass Sie nur Termine aus dieser Kalender Instanz sehen.</span>
                                </p>
                            </Alert>
                        </div>
                    </div>

                    <div>
                        <InputSelect
                            class="md:col-5"
                            :class="{
                                flex: eventType === EventType.INTERNAL,
                                hidden: eventType !== EventType.INTERNAL,
                            }"
                            name="organisation"
                            label="Organisationen"
                            :options="organisationOptions"
                            v-model="organisation"
                            :errors="submitCount === 0 ? undefined : errors.organisation"
                            :disabled="!organisationOptions.length"
                        />

                        <div
                            class="md:col-5 flex-column"
                            :class="{ flex: eventType === EventType.GLOBAL, hidden: eventType !== EventType.GLOBAL }"
                        >
                            <InputLocation
                                name="location"
                                label="Postleitzahl"
                                ref="locationSearchRef"
                                v-model:address="postal_code"
                                v-model:radius="radius"
                                :postal-code-only="true"
                                :errors="submitCount === 0 ? undefined : errors.postal_code || errors.radius"
                            />
                            <span
                                v-if="submitCount > 0 && (errors.postal_code || errors.radius)"
                                class="kern-error flex align-items-center py-2 gap-2 text-lg"
                                role="alert"
                            >
                                <Icon name="danger" />
                                {{ errors.postal_code || errors.radius }}
                            </span>
                        </div>
                    </div>
                </section>

                <Divider class="w-full my-6" />

                <InputText
                    class="md:col-5"
                    name="email"
                    label="E-Mail-Adresse"
                    placeholder="Ihre E-Mail-Adresse"
                    v-model="email"
                    :errors="submitCount === 0 ? undefined : errors.email"
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
                        gelesen und akzeptiere sie.
                    </template>
                </InputCheckbox>
            </Fieldset>
            <Alert
                v-if="errorMessageContent"
                title="Fehler"
                :content="errorMessageContent"
                severity="danger"
                class="my-2"
            />
            <Button
                type="submit"
                :disabled="isSubmitting"
                class="mx-auto flex align-items-center mb-4"
            >
                <span class="flex gap-2">
                    <Icon
                        name="mail"
                        color="white"
                    />
                    E-Mail-Benachrichtigung jetzt abonnieren
                </span>
            </Button>
        </form>
    </template>
    <template v-else>
        <div>
            <h2 class="kern-heading font-medium p-0 mb-2 text-theme-primary">
                Bitte bestätigen Sie Ihre E-Mail-Adresse
            </h2>
            <p class="kern-text mt-6">Wir haben Ihnen eine E-Mail zur Bestätigung an {{ email }} gesendet.</p>
            <p class="kern-text mb-6">
                Bitte klicken Sie auf den Link in der E-Mail, um den Benachrichtigungsservice zu bestätigen.
            </p>
            <Alert
                title="Information"
                severity="info"
                class="w-min"
            >
                <div class="kern-text alert-email-info">
                    <div class="kern-text--bold">Wenn Sie keine E-Mail erhalten haben:</div>
                    <ul class="ml-4 mt-2">
                        <li>Prüfen Sie Ihren Spam-Ordner</li>
                        <li>Warten Sie ein paar Minuten – es kann etwas dauern</li>
                    </ul>
                </div>
            </Alert>
        </div>
    </template>
</template>
<style lang="scss">
.alert-email-info {
    width: 350px;
    max-width: 100%;
}
.terms-link {
    color: #111;
    text-decoration: underline;
}
</style>
