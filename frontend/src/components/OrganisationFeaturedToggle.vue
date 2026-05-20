<script lang="ts" setup>
import { computed } from 'vue';
import { dsgApi } from '@/lib/dsgApi';

import Button from '@/components/KERN/Button.vue';

import { IOrganisation } from '@/types/General';

const featuredError = defineModel<string | null>('error');
const organisation = defineModel<IOrganisation>('organisation');

const featuredButtonLabel = computed(() =>
    organisation.value?.is_featured ? 'Als nicht empfohlen markieren' : 'Als empfohlen markieren'
);

const toggleFeatured = async (organisation?: IOrganisation) => {
    try {
        if (!organisation?.id) {
            featuredError.value = 'Ungültige Organisation.';
            return;
        }

        await dsgApi.put(`/organisations/${organisation.id}/featured`, {
            is_featured: !organisation.is_featured,
        });

        organisation.is_featured = !organisation.is_featured;
    } catch (error: any) {
        featuredError.value = 'Fehler beim Aktualisieren des empfohlenen Status.';
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
