<script lang="ts" setup>
import { I18nT, useI18n } from 'vue-i18n';
import ProfileForm from '@/views/profile/ProfileForm.vue';
import PasswordForm from '@/views/profile/PasswordForm.vue';
import Divider from '@/components/KERN/cosmetics/Divider.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';

const { t } = useI18n();
</script>
<template>
    <h1 class="kern-heading text-theme-primary">{{ t('app.profile.page.title') }}</h1>

    <p class="mt-1 mb-6">
        <b>{{ t('app.profile.hint') }}</b> <I18nT scope="global" keypath="app.profile.page.moreInfo">
            <template #docs>
                <LinkToDocs
                    path="Terminverwaltung/Organisation/"
                    fragment="profil-bearbeiten-einzelperson"
                />
            </template>
        </I18nT>
    </p>

    <ProfileForm />
    <Divider class="my-6" />
    <PasswordForm />
</template>
