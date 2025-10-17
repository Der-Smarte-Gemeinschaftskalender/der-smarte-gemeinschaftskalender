<script setup lang="ts">
import { getPrivacy } from '@/lib/mobilizonClient';
import { ref } from 'vue';
import Loader from '@/components/KERN/cosmetics/Loader.vue';

const loading = ref<boolean>(false);
const privacy = ref<any>(null);

const loadPrivacy = async () => {
    loading.value = true;
    try {
        privacy.value = await getPrivacy();
    } catch (error) {
        console.error('Failed to load privacy info', error);
    } finally {
        loading.value = false;
    }
};
loadPrivacy();
</script>
<template>
    <div>
        <h1 class="kern-heading text-theme-primary">Datenschutz</h1>
        <div v-if="loading">
            <Loader />
        </div>
        <div
            v-else
            class="kern-text"
        >
            <div v-html="privacy.bodyHtml"></div>
        </div>
    </div>
</template>
