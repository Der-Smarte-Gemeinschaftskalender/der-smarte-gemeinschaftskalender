<script lang="ts" setup>
import zod from '@/lib/zod';
import { computed, ref } from 'vue';
import { toTypedSchema } from '@vee-validate/zod';
import { useField, useForm } from 'vee-validate';
import { useI18n } from 'vue-i18n';
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

const { t } = useI18n();

const events = computed(() => [
    { label: t('public.exports.email.create.eventsInternal'), value: 'INTERNAL' },
    { label: t('public.exports.email.create.eventsGlobal'), value: 'GLOBAL' },
]);

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
            email: zod.string().email(t('public.exports.email.create.validation.invalidEmail')),
            organisation: zod.string().optional(),
            postal_code: zod.string().optional(),
            radius: zod.nativeEnum(Radius).optional(),
            accept_terms: zod
                .boolean()
                .default(false)
                .refine((val) => val === true, {
                    message: t('public.exports.email.create.validation.termsRequired'),
                }),
        })
        // This is a custom refinement to validate the form based on the event type
        .superRefine((data, ctx) => {
            if (data.eventType === EventType.GLOBAL) {
                if (!data.postal_code || !/^\d{5}$/.test(data.postal_code)) {
                    ctx.addIssue({
                        path: ['postal_code'],
                        code: zod.ZodIssueCode.custom,
                        message: t('public.exports.email.create.validation.invalidPostalCode'),
                    });
                }
                if (data.radius === undefined || !Object.values(Radius).includes(data.radius)) {
                    ctx.addIssue({
                        path: ['radius'],
                        code: zod.ZodIssueCode.custom,
                        message: t('public.exports.email.create.validation.invalidRadius'),
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
        await dsgApi.post('notifications', {
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
    } catch (error: unknown) {
        const apiError = error as { response?: { data?: { error?: string } } };
        errorMessageContent.value =
            axiosErrorHandler(error) && apiError.response?.data?.error
                ? apiError.response.data.error
                : t('public.exports.email.create.submitError');
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
            <h1 class="kern-heading text-theme-primary">{{ t('public.exports.email.create.title') }}</h1>
            <h2 class="kern-heading font-semilight text-theme-primary">{{ t('public.exports.email.create.subtitle') }}</h2>
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
                        v-model="intervall"
                        class="w-full"
                        name="intervall"
                        :label="t('public.exports.email.create.intervalLabel')"
                        :options="intervall_notification_options"
                        :errors="submitCount === 0 ? undefined : errors.intervall"
                    />
                    <InputSelect
                        v-model="category"
                        class="w-full"
                        name="category"
                        :label="t('public.exports.email.create.categoriesLabel')"
                        :options="mobilizon_category_options_all"
                        :errors="submitCount === 0 ? undefined : errors.category"
                    />
                </section>

                <Divider class="w-full my-6" />

                <section class="">
                    <div class="flex flex-column-reverse md:flex-row justify-content-between gap-6 mb-4 md:mb-0">
                        <InputRadios
                            v-model="eventType"
                            class="w-full mb-5"
                            name="event-types"
                            :label="t('public.exports.email.create.eventTypeLabel')"
                            :radios="events"
                            :errors="submitCount === 0 ? undefined : errors.eventType"
                        ></InputRadios>

                        <div class="w-full">
                            <Alert
                                :title="t('public.exports.email.create.noticeTitle')"
                                type="info"
                            >
                                <p v-if="eventType === EventType.GLOBAL">
                                    <span>"{{ t('public.exports.email.create.alertGlobalTitle') }}" </span>
                                    <span>
                                        {{ t('public.exports.email.create.alertGlobalText') }}
                                    </span>
                                </p>
                                <p v-else>
                                    <span>"{{ t('public.exports.email.create.alertInternalTitle') }}" </span>
                                    <span>{{ t('public.exports.email.create.alertInternalText') }}</span>
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
                            :label="t('public.exports.email.create.organisationsLabel')"
                            :options="organisationOptions"
                            :errors="submitCount === 0 ? undefined : errors.organisation"
                            :disabled="!organisationOptions.length"
                        />

                        <div
                            class="md:col-5 flex-column"
                            :class="{ flex: eventType === EventType.GLOBAL, hidden: eventType !== EventType.GLOBAL }"
                        >
                            <InputLocation
                                ref="locationSearchRef"
                                v-model:address="postal_code"
                                v-model:radius="radius"
                                name="location"
                                :label="t('public.exports.email.create.postalCodeLabel')"
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
                    v-model="email"
                    class="md:col-5"
                    name="email"
                    :label="t('public.exports.email.create.emailLabel')"
                    :placeholder="t('public.exports.email.create.emailPlaceholder')"
                    :errors="submitCount === 0 ? undefined : errors.email"
                />
                <InputCheckbox
                    v-model="accept_terms"
                    name="accept_terms"
                    :label="t('public.exports.email.create.termsLabel')"
                    :errors="submitCount === 0 ? undefined : errors.accept_terms"
                >
                    <template #label>
                        {{ t('public.exports.email.create.termsIntro') }}
                        <RouterLink
                            :to="{ name: 'public.terms' }"
                            class="terms-link"
                        >{{ t('public.terms.title') }}</RouterLink>
                        {{ t('public.exports.email.create.termsOutro') }}
                    </template>
                </InputCheckbox>
            </Fieldset>
            <Alert
                v-if="errorMessageContent"
                :title="t('common.error')"
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
                    {{ t('public.exports.email.create.submitButton') }}
                </span>
            </Button>
        </form>
    </template>
    <template v-else>
        <div>
            <h2 class="kern-heading font-medium p-0 mb-2 text-theme-primary">
                {{ t('public.exports.email.create.confirmTitle') }}
            </h2>
            <p class="kern-text mt-6">{{ t('public.exports.email.create.confirmSent', { email }) }}</p>
            <p class="kern-text mb-6">
                {{ t('public.exports.email.create.confirmInstruction') }}
            </p>
            <Alert
                :title="t('public.exports.email.create.infoTitle')"
                severity="info"
                class="w-min"
            >
                <div class="kern-text alert-email-info">
                    <div class="kern-text--bold">{{ t('public.exports.email.create.noEmailTitle') }}</div>
                    <ul class="ml-4 mt-2">
                        <li>{{ t('public.exports.email.create.noEmailTipSpam') }}</li>
                        <li>{{ t('public.exports.email.create.noEmailTipWait') }}</li>
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
