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
        <h1 class="kern-heading text-theme-primary">{{ $t('app.admin.manageInstance.title') }}</h1>
        <p class="kern-text">
            {{ $t('app.admin.manageInstance.hint') }}
            <a
                :href="`${mobilizonUrl}/login`"
                target="_blank"
            >
                {{ $t('app.admin.manageInstance.toMobilizon') }}
            </a>
        </p>
        <h2 class="kern-heading text-theme-primary">{{ $t('app.admin.instance.credentials') }}</h2>
        <p class="kern-text">
            {{ $t('app.admin.manageInstance.credentialsInfo') }}
        </p>
        <p class="kern-text">
            <b>{{ $t('app.admin.manageInstance.emailAddress') }}</b>
            <br />
            <code>{{ mobilizonAccessData?.mobilizon_email }}</code>
            <br />
            <b>{{ $t('app.admin.manageInstance.password') }}</b>
            <br />
            <code>{{ mobilizonAccessData?.mobilizon_password }}</code>
        </p>
    </div>
</template>
