<script lang="ts" setup>
import zod from '@/lib/zod';
import { ref } from 'vue';
import { useRoute } from 'vue-router';
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
import { type MobilizonGroup } from '@/types/Mobilizon';
import Icon from '@/components/KERN/cosmetics/Icon.vue';
import Button from '@/components/KERN/Button.vue';

const route = useRoute();

const loading = ref(false);
const errorMessageContent = ref<string | null>(null);
const confirmSuccess = ref(false);
const disallowSuccess = ref(false);

const confirmEmail = async () => {
    loading.value = true;
    try {
        if (!route.params.verificationToken) {
            errorMessageContent.value = 'Kein Bestätigungstoken gefunden.';
            return;
        }
        await dsgApi.post(`notifications/confirm`, {
            verificationToken: route.params.verificationToken,
        });
        confirmSuccess.value = true;
    } catch (error) {
        errorMessageContent.value = 'E-Mail-Adresse konnte nicht bestätigt werden.';
        console.error(error);
    } finally {
        loading.value = false;
    }
};
const disallowEmail = async () => {
    loading.value = true;
    try {
        if (!route.params.verificationToken) {
            errorMessageContent.value = 'Kein Bestätigungstoken gefunden.';
            return;
        }
        await dsgApi.post(`notifications/disallow`, {
            verificationToken: route.params.verificationToken,
        });
        disallowSuccess.value = true;
    } catch (error) {
        errorMessageContent.value = 'E-Mail-Adresse konnte nicht blockiert werden.';
        console.error(error);
    } finally {
        loading.value = false;
    }
};
</script>
<template>
    <div>
        <h2 class="kern-heading font-medium p-0 mb-2 text-theme-primary">E-Mail-Adresse bestätigen</h2>
        <p class="kern-text my-2">
            Um Ihre Anmeldung für Veranstaltungsbenachrichtigungen abzuschließen,
            <b>bestätigen Sie bitte Ihre E-Mail-Adresse.</b>
        </p>
        <p class="kern-text my-2">
            Falls Sie diese Nachricht nicht angefordert haben, können Sie den Empfang weiterer
            <b>E-Mails hier ablehnen.</b>
        </p>
        <Alert
            v-if="errorMessageContent"
            class="my-3"
            title="Fehler"
            :content="errorMessageContent"
            severity="danger"
        />
        <template v-if="confirmSuccess">
            <Alert
                title="E-Mail-Adresse bestätigt"
                content="Deine E-Mail-Adresse wurde erfolgreich bestätigt. Du kannst nun Benachrichtigungen erhalten."
                severity="success"
            />
            <div class="kern-row mt-3">
                <div class="kern-col">
                    <RouterLink to="/">
                        <Button label="Zurück zur Startseite" />
                    </RouterLink>
                </div>
            </div>
        </template>
        <template v-else-if="disallowSuccess">
            <Alert
                title="E-Mail-Adresse wurde blockiert"
                content="Deine E-Mail-Adresse wurde erfolgreich blockiert. Du wirst keine weiteren E-Mails erhalten."
                severity="success"
            />
            <div class="kern-row mt-3">
                <div class="kern-col">
                    <RouterLink to="/">
                        <Button label="Zurück zur Startseite" />
                    </RouterLink>
                </div>
            </div>
        </template>
        <template v-else>
            <div class="kern-row">
                <div class="kern-col">
                    <Button
                        label="Jetzt E-Mail-Adresse bestätigen"
                        icon-left="check"
                        :loading="loading"
                        @click="confirmEmail()"
                    />
                </div>
                <div class="kern-col">
                    <Button
                        label="Keine E-Mails mehr erhalten"
                        variant="secondary"
                        icon-left="close"
                        :loading="loading"
                        @click="disallowEmail()"
                    />
                </div>
            </div>
        </template>
    </div>
</template>
<style lang="scss">
.fieldset {
    max-width: 1000px !important;
}

.kern-form-input__select-wrapper:after {
    content: ' ' !important;
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M7 10l5 5 5-5H7z'/%3E%3C/svg%3E");
    background-color: white !important;
}

input[type='postal_code'] {
    border-radius: 0 !important;
}
.alert-email-info {
    min-width: 350px;
}
</style>
