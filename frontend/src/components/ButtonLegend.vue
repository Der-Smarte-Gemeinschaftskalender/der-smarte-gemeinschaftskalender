<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import Legend from '@/components/Legend.vue';

const { t } = useI18n();

const listLegendItems = { 
    'content-copy': t('app.shared.buttonLegend.duplicate'),
    'edit': t('app.shared.buttonLegend.edit'),
    'delete': t('app.shared.buttonLegend.delete'),
    'open-in-new': t('app.shared.buttonLegend.openPublic'),
    'visibility': t('app.shared.buttonLegend.details'),
    'wall_art': t('app.shared.buttonLegend.promoMaterials'),
};
</script>
<template>
    <Legend
        :title="t('app.shared.buttonLegend.title')"
        :icons="listLegendItems"
        
    />
</template>
