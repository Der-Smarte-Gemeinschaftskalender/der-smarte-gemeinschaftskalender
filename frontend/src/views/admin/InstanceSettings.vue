<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { dsgApi } from '@/lib/dsgApi';
import { useRoute } from 'vue-router';
import { I18nT, useI18n } from 'vue-i18n';

import Button from '@/components/KERN/Button.vue';
import Alert from '@/components/KERN/Alert.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';
import Table from '@/components/KERN/Table.vue';
import Divider from '@/components/KERN/cosmetics/Divider.vue';

import { emailEventTypeLabels, type EmailEventType } from '@/types/EmailTemplate';
import { LOCALE_LABELS, type SupportedLocale } from '@/i18n';
import type { Column } from '@/types/General';
import type { UserView } from '@/types/User';
import Badge from '@/components/KERN/cosmetics/Badge.vue';


enum CopyItem {
    Email = 'email',
    Password = 'password'
}

const { t } = useI18n();

const errorMessage = ref<string>('');
const successMessage = ref<string>('');
const mobilizonEmail = ref<string>('');
const mobilizonPassword = ref<string>('');
const mobilizonPasswordVisible = ref<boolean>(false);
const passwordCopied = ref<boolean>(false);    
const emailCopied = ref<boolean>(false);

const instanceUrl = import.meta.env.VITE_MOBILIZON_URL || null;
const route = useRoute();

// computed so the labels follow a language switch
const emailColumns = computed<Array<Column>>(() => [
    {
        key: 'subject',
        name: t('app.admin.emailTemplates.subject'),
        align: 'left',
    },
    {
        key: 'on_event',
        name: t('app.admin.emailTemplates.onEvent'),
        format: (value: EmailEventType) => emailEventTypeLabels.value[value] || value,
        align: 'left',
    },
    {
        key: 'locale',
        name: t('app.admin.emailTemplates.locale'),
        align: 'left',
    },
    {
        key: 'aktionen',
        name: '',
        align: 'right',
    },
]);

const loadInstanceCredentials = async () => {
    try {
        const { data } = await dsgApi.get<UserView>('user/instance');
        mobilizonEmail.value = data.email;
        mobilizonPassword.value = data.password;
    } catch (error: any) {
        console.error('Error loading Mobilizon credentials:', error);
        errorMessage.value =
            error.response?.data?.error || t('app.admin.instance.credentialsLoadError');
    }
};

const showAlert = () => {
    const query = route.query;
    if (query.templateUpdated) {
        successMessage.value = t('app.admin.instance.templateUpdated');
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
        :title="t('common.error')"
        :content="errorMessage"
        severity="danger"
        class="mb-5"
    />
    <Alert
        v-if="successMessage.length"
        :title="t('common.success')"
        :content="successMessage"
        severity="success"
        class="mb-5"
    />

    <h1 class="kern-heading text-theme-primary">{{ t('app.admin.instance.title') }}</h1>
    <p class="mt-1 mb-6">
        <b>{{ t('app.admin.shared.note') }}</b> <I18nT
            keypath="app.admin.instance.hint"
            scope="global"
        >
            <template #link>
                <LinkToDocs path="Terminverwaltung/Instanz/" fragment="instanz-verwalten" />
            </template>
        </I18nT>
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
                {{ t('app.admin.instance.toMobilizon') }}
            </Button>
        </a>
    </section>

    <section>
        <h2 class="kern-heading text-theme-primary font-medium mb-3">{{ t('app.admin.instance.credentials') }}</h2>
        <div class="mb-6 flex flex-column md:flex-row gap-6">
            <div class="w-full mt-2">
                <p class="mb-3">
                    <span>{{ t('app.admin.instance.credentialsCreated') }}</span>
                </p>
                <p>
                    <I18nT
                        keypath="app.admin.instance.credentialsUsage"
                        tag="span"
                        scope="global"
                    >
                        <template #login>
                            <strong>{{ t('app.admin.instance.credentialsUsageLogin') }}</strong>
                        </template>
                        <template #restriction>
                            <strong>{{ t('app.admin.instance.credentialsUsageRestriction') }}</strong>
                        </template>
                    </I18nT>
                </p>
            </div>
            <div class="md:col-6 p-0">
                <Alert
                    :title="t('app.admin.instance.warning')"
                    severity="warning"
                    :content="t('app.admin.instance.credentialsWarning')"
                />
            </div>
        </div>
        <div class="flex flex-column sm:flex-row gap-6">
            <Fieldset
                v-if="mobilizonEmail"
                :legend="t('app.admin.instance.emailAddress')"
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
                        :title="t('app.admin.instance.copyEmail')"
                        :aria-label="t('app.admin.instance.copyEmail')"
                        :icon-left="emailCopied ? 'check' : 'content-copy'"
                        :disabled="emailCopied"
                        @click="saveToClipboard(mobilizonEmail, CopyItem.Email)"
                    />
                </div>
            </Fieldset>

            <Fieldset
                v-if="mobilizonPassword"
                :legend="t('app.admin.users.password')"
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
                            :title="t('app.admin.instance.togglePassword')"
                            :aria-label="t('app.admin.instance.togglePassword')"
                            variant="secondary"
                            text
                            :icon-left="mobilizonPasswordVisible ? 'visibility-off' : 'visibility'"
                            @click="mobilizonPasswordVisible = !mobilizonPasswordVisible"
                        />
                        <Button
                            :title="t('app.admin.instance.copyPassword')"
                            :aria-label="t('app.admin.instance.copyPassword')"
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
        <h2 class="kern-heading text-theme-primary font-medium mb-3">{{ t('app.admin.instance.emailTemplates') }}</h2>
        <p class="mb-6">
            <b>{{ t('app.admin.shared.note') }}</b> <I18nT
                keypath="app.admin.instance.emailTemplatesHint"
                scope="global"
            >
                <template #link>
                    <LinkToDocs path="Terminverwaltung/E-Mail-Benachrichtigungen/#e-mail-vorlagen" />
                </template>
            </I18nT>
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
                    :title="emailEventTypeLabels[row.on_event as EmailEventType] || row.on_event"
                />
            </template>
            <template #locale="{ row }">
                {{ LOCALE_LABELS[row.locale as SupportedLocale] || row.locale }}
            </template>
            <template #aktionen="{ row }">
                <div class="flex justify-content-end flex-wrap gap-2 min-w-10rem">
                    <RouterLink :to="{ name: 'admin.emailTemplates.edit', params: { id: row?.id } }">
                        <Button
                            :title="t('common.edit')"
                            :aria-label="t('common.edit')"
                            icon-left="edit"
                        />
                    </RouterLink>
                </div>
            </template>
        </Table>
    </section>
</template>
