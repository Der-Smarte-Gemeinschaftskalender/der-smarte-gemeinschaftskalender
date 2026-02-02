<script setup lang="ts">
import { useRouter } from 'vue-router';
import { mobilizon_main_category_options } from '@/lib/const';

import Button from '@/components/KERN/Button.vue';
import Card from '@/components/KERN/Card.vue';

const router = useRouter();

const navigateToCategorySearch = (categoryValue: string) => {
    const subCategories = mobilizon_main_category_options.find((cat) => cat.value === categoryValue)?.sub_categories;
    localStorage.setItem('searchCategory', JSON.stringify(subCategories));

    router.push({ name: 'public.search' });
};
</script>

<template>
    <div>
        <h3 class="kern-heading text-theme-primary mb-4 pt-0">Kategorien erkunden</h3>

        <div class="cards-template">
            <div
                v-for="category in mobilizon_main_category_options"
                :key="category.value"
            >
                <Card
                    :title="category.text"
                    :image-src="category.image"
                    :callback-to="() => navigateToCategorySearch(category.value)"
                >
                    <template #footer>
                        <Button
                            icon-right="arrow-forward"
                            class="flex-grow-0"
                            label="Mehr erfahren"
                            @click="navigateToCategorySearch(category.value)"
                        />
                    </template>
                </Card>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss"></style>
