<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { dsgApi } from '@/lib/dsgApi';

import Button from '@/components/KERN/Button.vue';
import Alert from '@/components/KERN/Alert.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';

const errorMessage = ref<string>('');
const mobilizonEmail = ref<string>('');
const mobilizonPassword = ref<string>('');
const mobilizonPasswordVisible = ref<boolean>(false);

const instanceUrl = import.meta.env.VITE_MOBILIZON_URL || null;

const loadInstanceCredentials = async () => {
    try {
        const { data } = await dsgApi.get('user/instance');
        mobilizonEmail.value = data.email;
        mobilizonPassword.value = data.password;
    } catch (error: any) {
        console.error('Error loading Mobilizon credentials:', error);
        errorMessage.value =
            error.response?.data?.error || 'Fehler beim Laden der Zugangsdaten. Bitte versuchen Sie es später erneut.';
    }
};

onMounted(loadInstanceCredentials);

const saveToClipboard = (text: string) => {
    navigator.clipboard.writeText(text).then(
        () => {
            console.log('Text copied to clipboard');
        },
        (err) => {
            console.error('Could not copy text: ', err);
        }
    );
};
</script>

<template>
    <Alert
        v-if="errorMessage.length"
        title="Fehler"
        :content="errorMessage"
        severity="danger"
        class="mb-5"
    />

    <h1 class="kern-heading text-theme-primary">Instanz verwalten</h1>
    <p class="mt-1 mb-6">
        Die zentrale Verwaltung Ihrer Instanz erfolgt über Mobilizon. Mobilizon ist die zugrunde liegende
        Open-Source-Plattform, auf der der Smarte Gemeinschaftskalender basiert. Dort können Sie alle instanzweiten
        Einstellungen und Funktionen administrieren. Weitere Informationen finden Sie im
        <LinkToDocs path="Terminverwaltung/Instanz/#instanz-verwalten" />
        .
    </p>

    <section class="mb-6 flex justify-content-center w-full">
        <a
            :href="instanceUrl"
            target="_blank"
        >
            <Button
                variant="primary"
                icon-right="open-in-new"
                class=""
            >
                Zur Instanzverwaltung auf Mobilizon
            </Button>
        </a>
    </section>

    <section>
        <h2 class="kern-heading text-theme-primary font-medium mb-3">Zugangsdaten</h2>
        <div class="mb-6 flex flex-column md:flex-row gap-6">
            <p class="w-full mt-2">
                <span>Diese Zugangsdaten wurden automatisch bei der Installation erstellt.</span>
                <br />
                <br />
                <span>
                    Sie sind ausschließlich für die
                    <strong>Anmeldung bei Mobilizon</strong>
                    gedacht und dürfen
                    <strong>nicht verändert oder weitergegeben</strong>
                    werden.
                </span>
            </p>
            <div class="md:col-6 p-0">
                <Alert
                    title="Warnung"
                    severity="warning"
                    :content="`Änderungen an diesen Daten in Mobilizon führen dazu, dass die Integration mit dem Smarten Gemeinschaftskalender nicht mehr funktioniert.`"
                />
            </div>
        </div>
        <div class="flex flex-column sm:flex-row gap-6">
            <Fieldset
                v-if="mobilizonEmail"
                legend="E-Mail-Adresse"
                class="w-full"
                style="min-width: 0"
            >
                <div class="flex align-items-center gap-5">
                    <span
                        class="font-mono overflow-x-hidden text-overflow-ellipsis"
                        style="min-width: 0"
                    >
                        {{ mobilizonEmail }}
                    </span>
                    <Button
                        icon-left="content-copy"
                        variant="secondary"
                        text
                        @click="saveToClipboard(mobilizonEmail)"
                    />
                </div>
            </Fieldset>

            <Fieldset
                v-if="mobilizonPassword"
                legend="Passwort"
                class="w-full"
                style="min-width: 0"
            >
                <div class="flex align-items-center gap-5">
                    <span
                        class="font-mono w-max overflow-x-hidden text-overflow-ellipsis white-space-nowrap"
                        style="min-width: 0"
                    >
                        <template v-if="mobilizonPasswordVisible">
                            {{ mobilizonPassword }}
                        </template>
                        <template v-else>
                            {{ '*'.repeat(mobilizonPassword?.length || 8) }}
                        </template>
                    </span>
                    <div class="flex gap-2">
                        <Button
                            :icon-left="mobilizonPasswordVisible ? 'visibility-off' : 'visibility'"
                            variant="secondary"
                            text
                            @click="mobilizonPasswordVisible = !mobilizonPasswordVisible"
                        />
                        <Button
                            icon-left="content-copy"
                            variant="secondary"
                            text
                            @click="saveToClipboard(mobilizonPassword)"
                        />
                    </div>
                </div>
            </Fieldset>
        </div>
    </section>
</template>
