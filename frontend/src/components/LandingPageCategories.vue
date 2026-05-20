<script setup lang="ts">
import { useRouter } from 'vue-router';
import { mobilizon_main_category_options } from '@/lib/const';

import Card from '@/components/KERN/Card.vue';

const router = useRouter();

const navigateToCategorySearch = (e: Event, categoryValue: string) => {
    e?.preventDefault();
    
    const subCategories = mobilizon_main_category_options.value.find((cat) => cat.value === categoryValue)?.sub_categories;
    localStorage.setItem('searchCategory', JSON.stringify(subCategories));
    router.push({ name: 'public.search' });
};
</script>

<template>
    <div>
        <h3 class="kern-heading text-theme-primary mb-4 pt-0">{{ $t('public.landing.exploreCategories') }}</h3>

        <div class="cards-template">
            <div
                v-for="category in mobilizon_main_category_options"
                :key="category.value"
            >
                <Card
                    :title="category.text"
                    :image-src="category.image"
                    :to="{
                        name: 'public.search',
                        query: { category: category.value },
                    }"
                    :to-callback="(e: Event) => navigateToCategorySearch(e, category.value)"
                    :image-alt="$t('public.landing.imageFor') + ' ' + category.text"
                >
                </Card>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss"></style>
