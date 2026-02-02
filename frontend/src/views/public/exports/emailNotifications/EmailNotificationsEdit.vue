<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { toTypedSchema } from '@vee-validate/zod';
import { useField, useForm } from 'vee-validate';
import type { AxiosError } from 'axios';

import zod from '@/lib/zod';
import { axiosErrorHandler, dsgApi } from '@/lib/dsgApi';
import { intervall_notification_options, mobilizon_category_options_all } from '@/lib/const';
import { findOrganisationOptions } from '@/lib/mobilizonClient';

import Alert from '@/components/KERN/Alert.vue';
import Button from '@/components/KERN/Button.vue';
import Divider from '@/components/KERN/cosmetics/Divider.vue';
import Dialog from '@/components/KERN/Dialog.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import InputSelect from '@/components/KERN/inputs/InputSelect.vue';
import InputRadios from '@/components/KERN/inputs/InputRadios.vue';
import InputLocation from '@/components/KERN/inputs/InputLocation.vue';
import Icon from '@/components/KERN/cosmetics/Icon.vue';

import { Intervall, MobilizonCategoryAndAll, type Option } from '@/types/General';

type Message = {
    title: string;
    content: string;
    severity: 'success' | 'danger' | 'info';
    source?: 'fetch' | 'unsubscribe' | 'disallow' | 'update';
};

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

const route = useRoute();
const token = ref<string>(route.path.split('/').at(-1) || '');
const emailFromToken = ref<string>('');

const organisationOptions = ref<Option[]>([]);
const locationSearchRef = ref<InstanceType<typeof InputLocation> | null>(null);
const showDisallowDialog = ref<boolean>(false);
const message = ref<Message | null>(null);
const unsubscribed = ref<boolean>(false);

onMounted(async () => {
    try {
        const { data } = await dsgApi.get('/notifications', {
            params: { token: token.value },
        });

        emailFromToken.value = data.notification.email;
        intervall.value = data.notification.intervall;
        category.value = data.notification.category;

        if (data.notification.organisation_id) {
            eventType.value = EventType.INTERNAL;
            organisation.value = data.notification.organisation_id;
        } else {
            eventType.value = EventType.GLOBAL;
            organisation.value = data.notification.organisation_id || null;
            postal_code.value = data.notification.address || '';
            radius.value = data.notification.radius || Radius['25 km'];
        }
    } catch (error: AxiosError | any) {
        message.value = {
            title: 'Fehler',
            content:
                error.response?.data?.error ||
                'Fehler beim Laden der Abonnement-Informationen. Bitte versuchen Sie es später erneut.',
            severity: 'danger',
            source: 'fetch',
        };
        console.error('Error loading unsubscribe info:', error);
    }

    findOrganisationOptions().then((options) => {
        organisationOptions.value = options;
        if (!organisation.value) organisation.value = options[0]?.value || null;
    });
});

const unsubscribe = async (disallow: boolean = false) => {
    message.value = null;
    isSubmitting.value = true;

    try {
        const { data } = await dsgApi.post('/notifications/unsubscribe', {
            token: token.value,
            disallow
        });
        message.value = {
            title: 'Erfolg',
            content: data.message,
            severity: 'success',
            source: disallow ? 'disallow' : 'unsubscribe',
        };

        unsubscribed.value = true;
    } catch (error: AxiosError | any) {
        message.value = {
            title: 'Fehler',
            content: error.response?.data?.error || 'Fehler beim Abmelden. Bitte versuchen Sie es später erneut.',
            severity: 'danger',
            source: disallow ? 'disallow' : 'unsubscribe',
        };
    } finally {
        isSubmitting.value = false;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const defaultField = {
    intervall: Intervall.WEEKLY,
    category: MobilizonCategoryAndAll.ALL,
    eventType: EventType.INTERNAL,
    organisation: undefined,
    postal_code: '',
    radius: Radius['25 km'],
};

const validationSchema = toTypedSchema(
    zod
        .object({
            intervall: zod.nativeEnum(Intervall),
            category: zod.nativeEnum(MobilizonCategoryAndAll),
            eventType: zod.nativeEnum(EventType),
            organisation: zod.string().optional(),
            postal_code: zod.string().optional(),
            radius: zod.nativeEnum(Radius).optional(),
        })
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
const { value: organisation } = useField<string>('organisation');
const { value: radius } = useField<number>('radius');
const { value: postal_code } = useField<string>('postal_code');

const isSubmitting = ref(false);

const submitForm = handleSubmit(async (values) => {
    isSubmitting.value = true;
    message.value = null;

    try {
        const data = await dsgApi.patch('notifications', {
            token: token.value,
            intervall: values.intervall,
            category: values.category,
            eventType: values.eventType,
            organisation: values.eventType === EventType.INTERNAL ? values.organisation : undefined,
            location_hash: values.eventType === EventType.GLOBAL ? locationSearchRef.value?.geoHash : undefined,
            address: values.eventType === EventType.GLOBAL ? values.postal_code : undefined,
            radius: values.eventType === EventType.GLOBAL ? values.radius : undefined,
        });

        console.log('Form submitted successfully:', data);
        message.value = {
            title: 'Erfolg',
            content: data.data.message,
            severity: 'success',
            source: 'update',
        };
    } catch (error: any) {
        console.error('Error submitting form:', error);

        message.value = {
            title: 'Fehler',
            content:
                axiosErrorHandler(error) && error?.response?.data?.error
                    ? error.response.data.error
                    : 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.',
            severity: 'danger',
            source: 'update',
        };
    } finally {
        isSubmitting.value = false;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
});
</script>

<template>
    <Teleport to="#headerslot">
        <div class="mb-3 mt-4 sm:mb-4 sm:mt-5 md:my-6">
            <h1 class="kern-heading text-theme-primary">Benachrichtigungen bearbeiten</h1>
            <h2 class="kern-heading font-semilight text-theme-primary">
                Abonnement-Einstellungen verwalten oder abbestellen
            </h2>
        </div>
    </Teleport>

    <Dialog
        v-model="showDisallowDialog"
        title="Alle E-Mail-Benachrichtigungen abbestellen"
    >
        <p class="mb-4">Sind Sie sicher, dass Sie alle E-Mail-Benachrichtigungen für immer abbestellen möchten?</p>
        <Button
            class="w-full"
            label="Alle E-Mail-Benachrichtigungen abbestellen"
            @click="
                unsubscribe(true);
                showDisallowDialog = false;
            "
        />
    </Dialog>

    <div>
        <div
            v-if="!!message"
            class="col-12 md:col-11 lg:col-10 xl:col-8 mt-1 mx-auto"
        >
            <Alert
                :title="message.title"
                :content="message.content"
                :severity="message.severity"
                class="alert-email-info mb-4 w-full"
            />
        </div>

        <div
            :class="{
                hidden: unsubscribed,
            }"
        >
            <template v-if="!message || (message && message.source !== 'fetch')">
                <div class="col-12 md:col-11 lg:col-10 xl:col-8 mt-1 mx-auto">
                    <p class="mb-2">
                        Sie bearbeiten die E-Mail-Benachrichtigung für
                        <strong>{{ emailFromToken }}</strong>
                        . Passen Sie die Einstellungen unten an und klicken Sie auf "E-Mail-Benachrichtigung
                        aktualisieren", um Ihre Änderungen zu speichern.
                    </p>
                </div>

                <form
                    class="fieldset col-12 md:col-11 lg:col-10 xl:col-8 mx-auto"
                    @submit="submitForm"
                >
                    <Fieldset class="mb-8">
                        <section class="flex flex-column md:flex-row justify-content-between gap-6">
                            <InputSelect
                                v-model="intervall"
                                class="w-full"
                                name="intervall"
                                label="Intervall"
                                :options="intervall_notification_options"
                                :errors="submitCount === 0 ? undefined : errors.intervall"
                            />
                            <InputSelect
                                v-model="category"
                                class="w-full"
                                name="category"
                                label="Kategorien"
                                :options="mobilizon_category_options_all"
                                :errors="submitCount === 0 ? undefined : errors.category"
                            />
                        </section>

                        <Divider class="w-full my-6" />

                        <section>
                            <div class="flex flex-column-reverse md:flex-row justify-content-between gap-6">
                                <InputRadios
                                    v-model="eventType"
                                    class="w-full mb-5"
                                    name="event-types"
                                    label="Welche Veranstaltungen sollen angezeigt werden?"
                                    :radios="events"
                                    :errors="submitCount === 0 ? undefined : errors.eventType"
                                />
                                <div class="w-full">
                                    <Alert
                                        title="Hinweis"
                                        type="info"
                                    >
                                        <p v-if="eventType === EventType.GLOBAL">
                                            <span>"Alle Veranstaltungen aus dem Netzwerk"</span>
                                            bedeutet, dass Sie zusätzlich Termine aus anderen Gemeinden und
                                            Organisationen sehen.
                                        </p>
                                        <p v-else>
                                            <span>"Nur Veranstaltungen aus diesem Kalender"</span>
                                            bedeutet, dass Sie nur Termine aus dieser Instanz sehen.
                                        </p>
                                    </Alert>
                                </div>
                            </div>

                            <div>
                                <InputSelect
                                    v-model="organisation"
                                    class="md:col-5"
                                    :class="{
                                        flex: eventType === EventType.INTERNAL,
                                        hidden: eventType !== EventType.INTERNAL,
                                    }"
                                    name="organisation"
                                    label="Organisationen"
                                    :options="organisationOptions"
                                    :errors="submitCount === 0 ? undefined : errors.organisation"
                                    :disabled="!organisationOptions.length"
                                />

                                <div
                                    class="md:col-5 flex-column"
                                    :class="{
                                        flex: eventType === EventType.GLOBAL,
                                        hidden: eventType !== EventType.GLOBAL,
                                    }"
                                >
                                    <InputLocation
                                        ref="locationSearchRef"
                                        v-model:address="postal_code"
                                        v-model:radius="radius"
                                        name="location"
                                        label="Postleitzahl"
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
                    </Fieldset>

                    <div class="flex justify-content-center w-full">
                        <Button
                            type="submit"
                            :disabled="isSubmitting"
                            class="mt-2 w-full max-w-30rem"
                        >
                            <span class="flex gap-2">
                                <Icon
                                    name="mail"
                                    color="white"
                                />
                                E-Mail-Benachrichtigung aktualisieren
                            </span>
                        </Button>
                    </div>
                </form>

                <Divider class="my-6" />

                <div class="col-12 md:col-11 lg:col-10 xl:col-8 mt-7 mx-auto">
                    <p class="mb-6">Klicken Sie auf "Abbestellen", um den Vorgang abzuschließen.</p>

                    <div class="flex justify-content-center w-full">
                        <Button
                            class="mt-2 w-full max-w-30rem"
                            variant="primary"
                            label="Abbestellen"
                            :disabled="isSubmitting"
                            @click="unsubscribe()"
                        />
                    </div>
                </div>

                <Divider class="my-6" />
                <div class="col-12 md:col-11 lg:col-10 xl:col-8 mt-7 mx-auto">
                    <p class="mb-4">
                        Möchten Sie für immer keine E-Mail-Benachrichtigungen mehr erhalten? Klicken Sie auf die
                        Schaltfläche unten, um alle E-Mail-Benachrichtigungen abzubestellen.
                    </p>

                    <div class="flex justify-content-center w-full">
                        <Button
                            class="mt-2 w-full max-w-30rem"
                            variant="secondary"
                            label="Für immer abbestellen"
                            :disabled="isSubmitting"
                            @click="showDisallowDialog = true"
                        />
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<style scoped lang="scss">
.alert-email-info {
    width: 350px;
    max-width: 100%;
}
</style>
