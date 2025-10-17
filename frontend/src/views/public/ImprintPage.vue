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
        <h1 class="kern-heading text-theme-primary">Impressum</h1>
        <div v-if="loading">
            <Loader />
        </div>
        <div
            v-else
            class="kern-text"
        >
            <div v-html="about.longDescription"></div>
            <div class="mt-4">
                <b>Kontaktmöglichkeit:</b>
                {{ about.contact }}
            </div>
            <div class="mt-4">
                <b>Registrierungen ist:</b>
                <span v-if="about.registrationsOpen">offen</span>
                <span v-else>geschlossen</span>
            </div>
        </div>
    </div>
</template>
