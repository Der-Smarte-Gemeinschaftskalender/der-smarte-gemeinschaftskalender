<script setup lang="ts">
import { ref } from 'vue';
import { dsgApi } from '@/lib/dsgApi';
import { checkLogin } from '@/composables/UserComposoable';
import { useRouter, useRoute } from 'vue-router';
import Card from '@/components/KERN/Card.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import Alert from '@/components/KERN/Alert.vue';

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
                query: { message: 'Deine E-Mail-Adresse wurde erfolgreich verifiziert. Bitte melde dich an.' },
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
    errorMessage.value = 'Der Verifizierungstoken ist ungültig';
} else {
    submitValidation();
}
</script>
<template>
    <div class="flex align-self-center align-items-center justify-content-center">
        <div class="validation-card">
            <Card title="Validierung">
                <Fieldset>
                    <Alert
                        v-if="showErrorMessage"
                        title="Fehler"
                        :content="errorMessage ?? 'Es ist ein Fehler aufgetreten.'"
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
