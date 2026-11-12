<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import Button from '@/components/KERN/Button.vue';

const { t } = useI18n();
</script>
<template>
    <div>
        <h1 class="kern-heading">{{ t('app.notFound.title') }}</h1>
        <RouterLink :to="{ name: 'landingpage' }">
            <Button>{{ t('app.notFound.overview') }}</Button>
        </RouterLink>
    </div>
</template>
