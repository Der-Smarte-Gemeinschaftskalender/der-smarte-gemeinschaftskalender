<script setup lang="ts">
import { ref } from 'vue';
import { dsgApi } from '@/lib/dsgApi';
const mobilizonUrl = import.meta.env.VITE_MOBILIZON_URL;

const mobilizonAccessData = ref<any>(null);

const loadAdminData = async () => {
    try {
        const { data } = await dsgApi.get('/admin/mobilizonAccessData');

        mobilizonAccessData.value = data;
    } catch (error) {
        console.error(error);
    }
};
loadAdminData();
</script>
<template>
    <div>
        <h1 class="kern-heading text-theme-primary">Instanz Verwalten</h1>
        <p class="kern-text">
            Die Einstellung zur verwaltung der Instanz können nur direkt in Mobilizon vorgenommen werden. Bitte besuchen
            Sie die Mobilizon Weboberfläche, um Änderungen vorzunehmen:
            <a
                :href="`${mobilizonUrl}/login`"
                target="_blank"
            >
                Zur Mobilizon Instanz
            </a>
        </p>
        <h2 class="kern-heading text-theme-primary">Zugangsdaten</h2>
        <p class="kern-text">
            Diese Zugangsdaten wurden bei der Installation automatisch generiert. Diese Daten dürfen Sie mit niemanden
            teilen. Sie dürfen diese Daten auch nicht in der mobilizon Weboberfläche ändern, da sonst die Integration
            nicht mehr funktioniert.
        </p>
        <p class="kern-text">
            <b>E-Mail-Adresse:</b>
            <br />
            <code>{{ mobilizonAccessData?.mobilizon_email }}</code>
            <br />
            <b>Passwort:</b>
            <br />
            <code>{{ mobilizonAccessData?.mobilizon_password }}</code>
        </p>
    </div>
</template>
