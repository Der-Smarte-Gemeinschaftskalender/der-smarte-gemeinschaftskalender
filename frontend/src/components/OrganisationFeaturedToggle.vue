<script lang="ts" setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { dsgApi } from '@/lib/dsgApi';

import Button from '@/components/KERN/Button.vue';

import { IOrganisation } from '@/types/General';

const { t } = useI18n();

const featuredError = defineModel<string | null>('error');
const organisation = defineModel<IOrganisation>('organisation');

const featuredButtonLabel = computed(() =>
    organisation.value?.is_featured
        ? t('app.organisations.featured.markNotFeatured')
        : t('app.organisations.featured.markFeatured')
);

const toggleFeatured = async (organisation?: IOrganisation) => {
    try {
        if (!organisation?.id) {
            featuredError.value = t('app.organisations.featured.invalidOrganisation');
            return;
        }

        await dsgApi.put(`/organisations/${organisation.id}/featured`, {
            is_featured: !organisation.is_featured,
        });

        organisation.is_featured = !organisation.is_featured;
    } catch (error: any) {
        featuredError.value = t('app.organisations.featured.updateError');
        console.error('Error toggling featured status:', error);
    }
};

defineExpose({
    toggleFeatured,
    error: featuredError,
});
</script>
<template>
    <Button
        :label="featuredButtonLabel"
        :title="featuredButtonLabel"
        :aria-label="featuredButtonLabel"
        :variant="organisation?.is_featured ? 'primary' : 'secondary'"
        :icon-left="organisation?.is_featured ? 'star' : 'star_outline'"
        @click.prevent="toggleFeatured(organisation)"
    />
</template>
