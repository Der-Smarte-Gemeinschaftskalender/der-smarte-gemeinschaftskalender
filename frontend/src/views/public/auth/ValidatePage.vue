<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { dsgApi } from '@/lib/dsgApi';
import { checkLogin } from '@/composables/UserComposoable';
import { useRouter, useRoute } from 'vue-router';
import Card from '@/components/KERN/Card.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import Alert from '@/components/KERN/Alert.vue';

const { t } = useI18n();
const router = useRouter();
const route = useRoute();
const showErrorMessage = ref(false);
const errorMessage = ref('');
const verificationToken = route.params.verificationToken as string;

const submitValidation = async () => {
    try {
        const { data } = await dsgApi.post('/auth/validateUser', {
            verificationToken,
        });

        if (checkLogin()) {
            router.push('/app/profile/register-person');
        } else {
            router.push({
                name: 'login',
                query: { message: 'auth.validate.success' },
            });
        }
    } catch (error) {
        console.error(error);
        showErrorMessage.value = true;
        errorMessage.value = error?.response?.data?.message;
    }
};

if (!verificationToken) {
    showErrorMessage.value = true;
    errorMessage.value = t('auth.validate.invalidToken');
} else {
    submitValidation();
}
</script>
<template>
    <div class="flex align-self-center align-items-center justify-content-center">
        <div class="validation-card">
            <Card :title="t('auth.validate.title')">
                <Fieldset>
                    <Alert
                        v-if="showErrorMessage"
                        :title="t('common.error')"
                        :content="errorMessage ?? t('auth.errorOccurred')"
                        severity="danger"
                    />
                </Fieldset>
            </Card>
        </div>
    </div>
</template>
<style lang="scss">
.validation-card {
    max-width: 685px !important;
}
</style>
