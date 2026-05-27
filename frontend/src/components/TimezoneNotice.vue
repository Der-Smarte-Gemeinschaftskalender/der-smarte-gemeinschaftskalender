<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import Alert from '@/components/KERN/Alert.vue';
import { useTimezoneCheck } from '@/composables/TimezoneComposable';

const { t } = useI18n();
const { isMismatch, detectedTz } = useTimezoneCheck();
</script>

<template>
    <Alert
        v-if="isMismatch"
        severity="info"
        :title="t('public.timezoneNotice.title')"
        :content="t('public.timezoneNotice.content', { detectedTz })"
    />
</template>
