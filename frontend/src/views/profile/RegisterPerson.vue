<script lang="ts" setup>
import { ref, watch } from 'vue';
import { I18nT, useI18n } from 'vue-i18n';
import { dsgApi } from '@/lib/dsgApi';
import { useRouter, useRoute } from 'vue-router';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { ZodType } from 'zod';
import { convertToUsername, preferredUsernameSchema } from '@/lib/helper';
import zod from '@/lib/zod';
import Fieldset from '@/components/KERN/Fieldset.vue';
import InputText from '@/components/KERN/inputs/InputEmail.vue';
import Button from '@/components/KERN/Button.vue';
import Alert from '@/components/KERN/Alert.vue';
import Divider from '@/components/KERN/cosmetics/Divider.vue';
import { setUserPersonData, user } from '@/composables/UserComposoable';
import LinkToDocs from '@/components/LinkToDocs.vue';

const { t } = useI18n();
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
        preferredUsername: preferredUsernameSchema.nonempty(),
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
            t('app.profile.registerPerson.saveError');
    }
});

watch(name, async (newName, oldName) => {
    preferredUsername.value = convertToUsername(newName);
});
</script>
<template>
    <h1 class="kern-heading text-theme-primary mb-4">{{ t('app.profile.registerPerson.title') }}</h1>
    <p class="mb-3">
        <b>{{ t('app.profile.hint') }}</b>
        {{ t('app.profile.registerPerson.hint') }}
    </p>
    <p class="mb-6">
        <I18nT scope="global" keypath="app.profile.registerPerson.moreInfo">
            <template #docs>
                <LinkToDocs
                    path="Terminverwaltung/Organisation/"
                    fragment="registrierung-fur-einzelpersonen-bzw-organisationsmitglieder"
                />
            </template>
        </I18nT>
    </p>
    <Divider class="mb-6" />
    <form
        novalidate
        @submit.prevent="onSubmit"
    >
        <Alert
            v-if="showErrorMessage"
            :title="t('common.error')"
            :content="errorMessageContent || t('app.profile.errorOccurred')"
            severity="danger"
        />

        <Fieldset>
            <div class="flex flex-column gap-5">
                <div class="flex flex-column md:flex-row gap-6flex flex-column md:flex-row gap-6">
                    <div class="w-full">
                        <InputText
                            v-model="name"
                            :label="t('app.profile.registerPerson.name')"
                            name="name"
                            :placeholder="t('app.profile.registerPerson.namePlaceholder')"
                            :errors="submitCount === 0 ? undefined : errors.name"
                        />
                    </div>
                    <div class="md:col-7 px-0 mt-2">
                        <Alert
                            :title="t('app.profile.information')"
                            severity="info"
                        >
                            <p>
                                {{ t('app.profile.registerPerson.nameInfo') }}
                            </p>
                        </Alert>
                    </div>
                </div>
                <Divider />
                <div class="flex flex-column md:flex-row gap-6flex flex-column md:flex-row gap-6">
                    <div class="w-full">
                        <InputText
                            v-model="preferredUsername"
                            :label="t('app.profile.registerPerson.username')"
                            name="preferredUsername"
                            :placeholder="t('app.profile.registerPerson.usernamePlaceholder')"
                            :errors="submitCount === 0 ? undefined : errors.preferredUsername"
                        />
                    </div>

                    <div class="md:col-7 px-0 mt-2">
                        <Alert
                            :title="t('app.profile.information')"
                            severity="info"
                        >
                            <p class="mb-5">
                                <I18nT scope="global" keypath="app.profile.registerPerson.usernameInfo1">
                                    <template #firstLastName>
                                        <b>{{ t('app.profile.registerPerson.usernameInfo1Bold') }}</b>
                                    </template>
                                </I18nT>
                            </p>

                            <p class="pt-2">
                                <I18nT scope="global" keypath="app.profile.registerPerson.usernameInfo2">
                                    <template #noOrganisationName>
                                        <b>{{ t('app.profile.registerPerson.usernameInfo2NoOrganisationName') }}</b>
                                    </template>
                                    <template #notChangeable>
                                        <b>{{ t('app.profile.registerPerson.usernameInfo2NotChangeable') }}</b>
                                    </template>
                                </I18nT>
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
                {{ t('app.profile.registerPerson.submit') }}
            </Button>
        </div>
    </form>
</template>
