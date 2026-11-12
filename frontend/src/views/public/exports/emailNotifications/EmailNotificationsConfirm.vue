<script lang="ts" setup>
import { ref } from 'vue';
import { useRoute } from 'vue-router';
import { I18nT, useI18n } from 'vue-i18n';
import { dsgApi } from '@/lib/dsgApi';

import Alert from '@/components/KERN/Alert.vue';
import Button from '@/components/KERN/Button.vue';

const route = useRoute();
const { t } = useI18n();

const loading = ref(false);
const errorMessageContent = ref<string | null>(null);
const confirmSuccess = ref(false);
const disallowSuccess = ref(false);

const confirmEmail = async () => {
    loading.value = true;
    try {
        if (!route.params.verificationToken) {
            errorMessageContent.value = t('public.exports.email.confirm.noToken');
            return;
        }
        await dsgApi.post(`notifications/confirm`, {
            verificationToken: route.params.verificationToken,
        });
        confirmSuccess.value = true;
    } catch (error) {
        errorMessageContent.value = t('public.exports.email.confirm.confirmError');
        console.error(error);
    } finally {
        loading.value = false;
    }
};
const disallowEmail = async () => {
    loading.value = true;
    try {
        if (!route.params.verificationToken) {
            errorMessageContent.value = t('public.exports.email.confirm.noToken');
            return;
        }
        await dsgApi.post(`notifications/disallow`, {
            verificationToken: route.params.verificationToken,
        });
        disallowSuccess.value = true;
    } catch (error) {
        errorMessageContent.value = t('public.exports.email.confirm.disallowError');
        console.error(error);
    } finally {
        loading.value = false;
    }
};
</script>
<template>
    <div>
        <h2 class="kern-heading font-medium p-0 mb-2 text-theme-primary">
            {{ t('public.exports.email.confirm.title') }}
        </h2>
        <I18nT
            scope="global"
            keypath="public.exports.email.confirm.intro"
            tag="p"
            class="kern-text my-2"
        >
            <template #action>
                <b>{{ t('public.exports.email.confirm.introAction') }}</b>
            </template>
        </I18nT>
        <I18nT
            scope="global"
            keypath="public.exports.email.confirm.disallowIntro"
            tag="p"
            class="kern-text my-2"
        >
            <template #action>
                <b>{{ t('public.exports.email.confirm.disallowIntroAction') }}</b>
            </template>
        </I18nT>
        <Alert
            v-if="errorMessageContent"
            class="my-3"
            :title="t('common.error')"
            :content="errorMessageContent"
            severity="danger"
        />
        <template v-if="confirmSuccess">
            <Alert
                :title="t('public.exports.email.confirm.confirmedTitle')"
                :content="t('public.exports.email.confirm.confirmedText')"
                severity="success"
            />
            <div class="kern-row mt-3">
                <div class="kern-col">
                    <RouterLink to="/">
                        <Button :label="t('public.exports.email.confirm.backToHome')" />
                    </RouterLink>
                </div>
            </div>
        </template>
        <template v-else-if="disallowSuccess">
            <Alert
                :title="t('public.exports.email.confirm.blockedTitle')"
                :content="t('public.exports.email.confirm.blockedText')"
                severity="success"
            />
            <div class="kern-row mt-3">
                <div class="kern-col">
                    <RouterLink to="/">
                        <Button :label="t('public.exports.email.confirm.backToHome')" />
                    </RouterLink>
                </div>
            </div>
        </template>
        <template v-else>
            <div class="kern-row">
                <div class="kern-col">
                    <Button
                        :label="t('public.exports.email.confirm.confirmButton')"
                        icon-left="check"
                        :loading="loading"
                        @click="confirmEmail()"
                    />
                </div>
                <div class="kern-col">
                    <Button
                        :label="t('public.exports.email.confirm.disallowButton')"
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
