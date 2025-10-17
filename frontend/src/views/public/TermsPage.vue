<script setup lang="ts">
import { getTerms } from '@/lib/mobilizonClient';
import { ref } from 'vue';
import Loader from '@/components/KERN/cosmetics/Loader.vue';

const loading = ref<boolean>(false);
const terms = ref<any>(null);

const loadTerms = async () => {
    loading.value = true;
    try {
        terms.value = await getTerms();
    } catch (error) {
        console.error('Failed to load terms info', error);
    } finally {
        loading.value = false;
    }
};
loadTerms();
</script>
<template>
    <div>
        <h1 class="kern-heading text-theme-primary">Nutzungsbedingungen</h1>
        <div v-if="loading">
            <Loader />
        </div>
        <div
            v-else
            class="kern-text"
        >
            <div v-html="terms.bodyHtml"></div>
        </div>
    </div>
</template>
