<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import InputSelect from './InputSelect.vue';
import { timezoneOptions } from '@/lib/timezone';

const model = defineModel<string>();

interface Props {
    name?: string;
    label?: string;
    errors?: string;
}

const { name = 'timezone', label, errors } = defineProps<Props>();
const { t } = useI18n();
</script>
<template>
    <InputSelect
        v-model="model"
        :name="name"
        :label="label ?? t('common.timezone.label')"
        :options="timezoneOptions"
        :errors="errors"
    />
</template>
