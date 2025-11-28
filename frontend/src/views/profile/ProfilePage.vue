<script lang="ts" setup>
import ProfileForm from '@/views/profile/ProfileForm.vue';
import PasswordForm from '@/views/profile/PasswordForm.vue';
import Divider from '@/components/KERN/cosmetics/Divider.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';
</script>
<template>
    <h1 class="kern-heading text-theme-primary">Profil bearbeiten</h1>

    <p class="mt-1 mb-6">
        <b>Hinweis:</b>
        Weitere Informationen finden Sie im
        <LinkToDocs
            path="Terminverwaltung/Organisation/"
            fragment="profil-bearbeiten-einzelperson"
        />
        .
    </p>

    <ProfileForm />
    <Divider class="my-6" />
    <PasswordForm />
</template>
