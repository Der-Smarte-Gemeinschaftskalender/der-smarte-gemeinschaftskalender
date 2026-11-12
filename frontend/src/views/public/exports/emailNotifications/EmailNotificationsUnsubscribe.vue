<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRoute } from "vue-router";
import { I18nT, useI18n } from "vue-i18n";
import { dsgApi } from "@/lib/dsgApi";

import Alert from "@/components/KERN/Alert.vue";
import Button from "@/components/KERN/Button.vue";

import type { AxiosError } from "axios";
import Divider from "@/components/KERN/cosmetics/Divider.vue";
import Dialog from "@/components/KERN/Dialog.vue";

const route = useRoute();
const { t } = useI18n();

const showUnsubscribeSuccess = ref<string>('');
const showUnsubscribeError = ref<string>('');
const showUnsubscribeInfoError = ref<string>('');
const showDisallowDialog = ref<boolean>(false);

const token = ref<string>(route.path.split('/').at(-1) || '');
const email = ref<string>('');


onMounted(async () => {
    try {
        const { data } = await dsgApi.get('/notifications', {
            params: { token: token.value },
        });

        email.value = data.notification.email;
    }
    catch (error: AxiosError|any) {
        showUnsubscribeInfoError.value = error.response?.data?.error || t('public.exports.email.unsubscribe.loadError');
        console.error('Error loading unsubscribe info:', error);
    }
});

const unsubscribe = async (disallow: boolean = false) => {
    showUnsubscribeError.value = '';
    showUnsubscribeSuccess.value = '';
    try {
        const { data } = await dsgApi.post('/notifications/unsubscribe', {
            token: token.value,
            disallow,
        });
        showUnsubscribeSuccess.value = data.message;

    }
    catch (error: AxiosError|any) {
        showUnsubscribeError.value = error.response?.data?.error || t('public.exports.email.unsubscribe.unsubscribeError');
    }
};

</script>

<template>
    <Teleport to="#headerslot">
      <div class="mb-3 mt-4 sm:mb-4 sm:mt-5 md:my-6">
            <h1 class="kern-heading text-theme-primary">{{ t('public.exports.email.unsubscribe.title') }}</h1>
        </div>
    </Teleport>

    <Dialog
        v-model="showDisallowDialog"
        :title="t('public.exports.email.unsubscribe.disallowTitle')"
    >
        <p class="mb-4">
            {{ t('public.exports.email.unsubscribe.disallowConfirm') }}
            {{ t('public.exports.email.unsubscribe.noMoreEmails') }}
        </p>
        <Button
            class="w-full"
            :label="t('public.exports.email.unsubscribe.disallowTitle')"
            @click="unsubscribe(true); showDisallowDialog = false;"
        />
    </Dialog>

    <div>
        <Alert
            v-if="!!showUnsubscribeSuccess"
            :title="t('common.success')"
            :content="showUnsubscribeSuccess"
            severity="success"
            class="mb-4"
        />
        <Alert
            v-if="!!showUnsubscribeError || !!showUnsubscribeInfoError"
            :title="t('common.error')"
            :content="showUnsubscribeError || showUnsubscribeInfoError"
            severity="danger"
            class="mb-4"
        />

        <template v-if="!showUnsubscribeInfoError && !showUnsubscribeSuccess">
            <div>
                <I18nT
                    scope="global"
                    keypath="public.exports.email.unsubscribe.question"
                    tag="p"
                >
                    <template #email>
                        <strong>{{ email }}</strong>
                    </template>
                </I18nT>
                <p class="mb-6">
                    {{ t('public.exports.email.unsubscribe.instruction', { button: t('public.exports.email.unsubscribe.button') }) }}
                </p>

                <div class="flex justify-content-center w-full">
                    <Button
                        class="w-full max-w-30rem"
                        variant="primary"
                        :label="t('public.exports.email.unsubscribe.button')"
                        :disabled="!!showUnsubscribeSuccess"
                        @click="unsubscribe()"
                    />
                </div>
            </div>
            <Divider class="my-6" />
            <div>
                <p class="mb-4">{{ t('public.exports.email.unsubscribe.disallowQuestion') }}</p>

                <div class="flex justify-content-center w-full">
                    <Button
                        class="mt-2 w-full max-w-30rem"
                        variant="secondary"
                        :label="t('public.exports.email.unsubscribe.disallowButton')"
                        :disabled="!!showUnsubscribeSuccess"
                        @click="showDisallowDialog = true;"
                    />
                </div>
            </div>
        </template>
        <template v-else-if="showUnsubscribeInfoError">
            <p class="mb-2">{{ t('public.exports.email.unsubscribe.loadFailed') }}</p>
            <a class="text-theme-primary" href="/">{{ t('public.exports.email.unsubscribe.toHome') }}</a>
        </template>
        <template v-else>
            <p class="mb-2">
                {{ t('public.exports.email.unsubscribe.success') }}
                {{ t('public.exports.email.unsubscribe.noMoreEmails') }}
            </p>
            <a class="text-theme-primary" href="/">{{ t('public.exports.email.unsubscribe.toHome') }}</a>
        </template>
    </div>
</template>

<style scoped lang="scss">

</style>