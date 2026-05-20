<script setup lang="ts">
import { getAbout } from '@/lib/mobilizonClient';
import { ref } from 'vue';
import Loader from '@/components/KERN/cosmetics/Loader.vue';

const loading = ref<boolean>(false);
const about = ref<any>(null);

const loadAbout = async () => {
    loading.value = true;
    try {
        about.value = await getAbout();
    } catch (error) {
        console.error('Failed to load about info', error);
    } finally {
        loading.value = false;
    }
};
loadAbout();
</script>
<template>
    <div>
        <div class="mb-4">
            <h1 class="kern-heading text-theme-primary">{{ $t('public.imprint.title') }}</h1>
        </div>
        <div v-if="loading">
            <Loader />
        </div>
        <div
            v-else
            class="kern-text"
        >
            <div v-html="about.longDescription"></div>
            <div class="mt-4">
                <b>{{ $t('public.imprint.contact') }}:</b>
                {{ about.contact }}
            </div>
            <div class="mt-4">
                <b>{{ $t('public.imprint.registrations') }}:</b>
                <span v-if="about.registrationsOpen">{{ $t('public.imprint.registrationsOpen') }}</span>
                <span v-else>{{ $t('public.imprint.registrationsClosed') }}</span>
            </div>
        </div>
    </div>
</template>
