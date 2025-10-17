<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRoute } from "vue-router";
import { dsgApi } from "@/lib/dsgApi";

import Alert from "@/components/KERN/Alert.vue";
import Button from "@/components/KERN/Button.vue";

import type { AxiosError } from "axios";
import Divider from "@/components/KERN/cosmetics/Divider.vue";
import Dialog from "@/components/KERN/Dialog.vue";

const route = useRoute();

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
        showUnsubscribeInfoError.value = error.response?.data?.error || 'Fehler beim Laden der Abonnement-Informationen. Bitte versuchen Sie es später erneut.';
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
        showUnsubscribeError.value = error.response?.data?.error || 'Fehler beim Abmelden. Bitte versuchen Sie es später erneut.';
    }
};

</script>

<template>
    <Teleport to="#headerslot">
      <div class="mb-3 mt-4 sm:mb-4 sm:mt-5 md:my-6">
            <h1 class="kern-heading text-theme-primary">E-Mail-Benachrichtigungen abbestellen</h1>
        </div>
    </Teleport>

    <Dialog
        title="Alle E-Mail-Benachrichtigungen abbestellen"
        v-model="showDisallowDialog"
    >
        <p class="mb-4">
            Sind Sie sicher, dass Sie alle E-Mail-Benachrichtigungen für immer abbestellen möchten? Sie erhalten keine E-Mail-Benachrichtigungen mehr von dieser Plattform.
        </p>
        <Button
            class="w-full"
            label="Alle E-Mail-Benachrichtigungen abbestellen"
            @click="unsubscribe(true); showDisallowDialog = false;"
        />
    </Dialog>

    <div>
        <Alert
            v-if="!!showUnsubscribeSuccess"
            title="Erfolg"
            :content="showUnsubscribeSuccess"
            severity="success"
            class="mb-4"
        />
        <Alert
            v-if="!!showUnsubscribeError || !!showUnsubscribeInfoError"
            title="Fehler"
            :content="showUnsubscribeError || showUnsubscribeInfoError"
            severity="danger"
            class="mb-4"
        />

        <template v-if="!showUnsubscribeInfoError && !showUnsubscribeSuccess">
            <div>
                <p>Möchten Sie keine E-Mail-Benachrichtigungen auf die Adresse <strong>{{ email }}</strong> mehr erhalten?</p>
                <p class="mb-6">Klicken Sie auf "Abbestellen", um den Vorgang abzuschließen.</p>

                <div class="flex justify-content-center w-full">
                    <Button
                        class="w-full max-w-30rem"
                        variant="primary"
                        @click="unsubscribe()"
                        label="Abbestellen"
                        :disabled="!!showUnsubscribeSuccess"
                    />
                </div>
            </div>
            <Divider class="my-6" />
            <div>
                <p class="mb-4">Möchten Sie für immer keine E-Mail-Benachrichtigungen mehr erhalten? Klicken Sie auf die Schaltfläche unten, um alle E-Mail-Benachrichtigungen abzubestellen.</p>

                <div class="flex justify-content-center w-full">
                    <Button
                        class="mt-2 w-full max-w-30rem"
                        variant="secondary"
                        @click="showDisallowDialog = true;"
                        label="Für immer abbestellen"
                        :disabled="!!showUnsubscribeSuccess"
                    />
                </div>
            </div>
        </template>
        <template v-else-if="showUnsubscribeInfoError">
            <p class="mb-2">Die Abonnement-Informationen konnten nicht geladen werden. Bitte versuchen Sie es später erneut.</p>
            <a class="text-theme-primary" href="/">Zur Startseite</a>
        </template>
        <template v-else>
            <p class="mb-2">Sie haben sich erfolgreich abgemeldet. Sie erhalten keine E-Mail-Benachrichtigungen mehr von dieser Plattform.</p>
            <a class="text-theme-primary" href="/">Zur Startseite</a>
        </template>
    </div>
</template>

<style scoped lang="scss">

</style>