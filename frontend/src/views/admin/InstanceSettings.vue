<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { dsgApi } from '@/lib/dsgApi';
import { useRoute } from 'vue-router';

import Button from '@/components/KERN/Button.vue';
import Alert from '@/components/KERN/Alert.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';
import Table from '@/components/KERN/Table.vue';
import Divider from '@/components/KERN/cosmetics/Divider.vue';

import { emailEventTypeLabels } from '@/types/EmailTemplate';
import type { Column } from '@/types/General';
import type { UserView } from '@/types/User';
import Badge from '@/components/KERN/cosmetics/Badge.vue';


enum CopyItem {
    Email = 'email',
    Password = 'password'
}

const errorMessage = ref<string>('');
const successMessage = ref<string>('');
const mobilizonEmail = ref<string>('');
const mobilizonPassword = ref<string>('');
const mobilizonPasswordVisible = ref<boolean>(false);
const passwordCopied = ref<boolean>(false);    
const emailCopied = ref<boolean>(false);

const instanceUrl = import.meta.env.VITE_MOBILIZON_URL || null;
const route = useRoute();

const emailColumns: Array<Column> = [
    {
        key: 'subject',
        name: 'Betreff',
        align: 'left',
    },
    {
        key: 'on_event',
        name: 'Bei welchem Ereignis',
        format: (value: keyof typeof emailEventTypeLabels) => emailEventTypeLabels[value] || value,
        align: 'left',
    },
    {
        key: 'aktionen',
        name: '',
        align: 'right',
    },
];

const loadInstanceCredentials = async () => {
    try {
        const { data } = await dsgApi.get<UserView>('user/instance');
        mobilizonEmail.value = data.email;
        mobilizonPassword.value = data.password;
    } catch (error: any) {
        console.error('Error loading Mobilizon credentials:', error);
        errorMessage.value =
            error.response?.data?.error || 'Fehler beim Laden der Zugangsdaten. Bitte versuchen Sie es später erneut.';
    }
};

const showAlert = () => {
    const query = route.query;
    if (query.templateUpdated) {
        successMessage.value = 'Vorlage erfolgreich aktualisiert.';
    }
};

onMounted(() => [loadInstanceCredentials(), showAlert()]);
const saveToClipboard = (text: string, item: CopyItem) => {
    navigator.clipboard.writeText(text).then(
        () => {
            if (item === CopyItem.Password) {
                passwordCopied.value = true;
                setTimeout(() => {
                    passwordCopied.value = false;
                }, 2000);
            } else if (item === CopyItem.Email) {
                emailCopied.value = true;
                setTimeout(() => {
                    emailCopied.value = false;
                }, 2000);
            }
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
    <Alert
        v-if="successMessage.length"
        title="Erfolg"
        :content="successMessage"
        severity="success"
        class="mb-5"
    />

    <h1 class="kern-heading text-theme-primary">Instanz verwalten</h1>
    <p class="mt-1 mb-6">
        <b>Hinweis:</b>
        Die zentrale Verwaltung Ihrer Instanz erfolgt über Mobilizon. Mobilizon ist die zugrunde liegende
        Open-Source-Plattform, auf der der Smarte Gemeinschaftskalender basiert. Dort können Sie alle instanzweiten
        Einstellungen und Funktionen administrieren. Weitere Informationen finden Sie im
        <LinkToDocs path="Terminverwaltung/Instanz/" fragment="instanz-verwalten" />.
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
            <div class="w-full mt-2">
                <p class="mb-3">
                    <span>Diese Zugangsdaten wurden automatisch bei der Installation erstellt.</span>
                </p>
                <p>
                    <span>
                        Sie sind ausschließlich für die
                        <strong>Anmeldung bei Mobilizon</strong>
                        gedacht und dürfen
                        <strong>nicht verändert oder weitergegeben</strong>
                        werden.
                    </span>
                </p>
            </div>
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
                        variant="secondary"
                        text
                        title="E-Mail-Adresse kopieren"
                        aria-label="E-Mail-Adresse kopieren"
                        :icon-left="emailCopied ? 'check' : 'content-copy'"
                        :disabled="emailCopied"
                        @click="saveToClipboard(mobilizonEmail, CopyItem.Email)"
                    />
                </div>
            </Fieldset>

            <Fieldset
                v-if="mobilizonPassword"
                legend="Passwort"
                class="w-full"
                style="min-width: 0"
            >
                <div class="flex align-items-center gap-5 mb-2">
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
                            title="Passwort anzeigen/verbergen"
                            aria-label="Passwort anzeigen/verbergen"
                            variant="secondary"
                            text
                            :icon-left="mobilizonPasswordVisible ? 'visibility-off' : 'visibility'"
                            @click="mobilizonPasswordVisible = !mobilizonPasswordVisible"
                        />
                        <Button
                            title="Passwort kopieren"
                            aria-label="Passwort kopieren"
                            variant="secondary"
                            text
                            :icon-left="passwordCopied ? 'check' : 'content-copy'"
                            :disabled="passwordCopied"
                            @click="saveToClipboard(mobilizonPassword, CopyItem.Password)"
                        />
                    </div>
                </div>
            </Fieldset>
        </div>
    </section>

    <Divider class="my-6" />

    <section>
        <h2 class="kern-heading text-theme-primary font-medium mb-3">E-Mail-Vorlagen</h2>
        <p class="mb-6">
            <b>Hinweis:</b>
            Hier können Sie die E-Mail-Vorlagen verwalten, die für Benachrichtigungen der Nutzer*innen verwendet werden.
            Weitere Informationen finden Sie im
            <LinkToDocs path="Terminverwaltung/E-Mail-Benachrichtigungen/#e-mail-vorlagen" />.
        </p>
        <Table
            :api="{
                url: 'email-templates'
            }"
            :valuesMaxLength="50"
            :columns="emailColumns"
        >
            <template #on_event="{ row }">
                <Badge
                    :title="emailEventTypeLabels[row.on_event as keyof typeof emailEventTypeLabels] || row.on_event"
                />
            </template>
            <template #aktionen="{ row }">
                <div class="flex justify-content-end flex-wrap gap-2 min-w-10rem">
                    <RouterLink :to="{ name: 'admin.emailTemplates.edit', params: { id: row?.id } }">
                        <Button
                            title="Bearbeiten"
                            aria-label="Bearbeiten"
                            icon-left="edit"
                        />
                    </RouterLink>
                </div>
            </template>
        </Table>
    </section>
</template>
