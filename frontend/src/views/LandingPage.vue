<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';

import Button from '@/components/KERN/Button.vue';
import UpcomingEvents from '@/components/UpcomingEvents.vue';
import InputLocation from '@/components/KERN/inputs/InputLocation.vue';
import InputText from '@/components/KERN/inputs/InputText.vue';
import Divider from '@/components/KERN/cosmetics/Divider.vue';
import LandingPageCategories from '@/components/LandingPageCategories.vue';

const router = useRouter();
const locationSearchRef = ref<InstanceType<typeof InputLocation> | null>(null);
const searchTerm = ref<string>('');
const searchAddress = ref<string>(import.meta.env.VITE_SEARCH_LOCATION_ADDRESS || '');
const searchRadius = ref<number>(5);
const instanceName = ref<string>(import.meta.env.VITE_INSTANCE_NAME);

const openSearch = () => {
    localStorage.setItem('searchTerm', searchTerm.value);
    localStorage.setItem('searchAddress', searchAddress.value);
    localStorage.setItem('searchRadius', searchRadius.value.toString());

    router.push({ name: 'public.search' });
};
</script>
<template>
    <div class="w-full">
        <Teleport to="#headerslot">
            <div class="mb-3 mt-4 sm:mb-4 sm:mt-5 md:my-6">
                <h1 class="kern-heading text-theme-primary">Veranstaltungen im Amt Süderbrarup</h1>
                <h2 class="kern-heading font-semilight text-theme-primary mb-6">{{ instanceName }}</h2>

                <RouterLink :to="{ name: 'public.exports.index' }">
                    <Button
                        icon-left="notifications"
                        label="Benachrichtigungen abonnieren"
                    />
                </RouterLink>

                <p class="mt-4">
                    <span>Bleiben Sie über Angebote in Ihrer Region informiert.</span>
                </p>
            </div>
        </Teleport>

        <div class="mb-6 mt-2">
            <h3 class="kern-heading text-theme-primary">Veranstaltungen suchen</h3>

            <div class="flex flex-column md:flex-row gap-4 align-content-start md:align-items-center">
                <InputText
                    aria-label="Suchbegriff"
                    v-model="searchTerm"
                    placeholder="Schlagwort, Veranstaltungstitel, Organisation,..."
                    name="searchTerm"
                    class="col"
                />

                <InputLocation
                    ref="locationSearchRef"
                    v-model:address="searchAddress"
                    v-model:radius="searchRadius"
                    class="md:max-w-20rem lg:max-w-max"
                />

                <Button
                    @click="openSearch()"
                    variant="secondary"
                    icon-left="search"
                    class="w-fit"
                >
                    Suchen
                </Button>
            </div>
        </div>
        <UpcomingEvents />
        <Divider class="my-7" />
        <LandingPageCategories />
        <Divider class="my-7" />
        <div>
            <h3 class="kern-heading text-theme-primary">Keine Veranstaltung mehr verpassen</h3>
            <p class="mb-6">
                <span class="inline-block mb-1">
                    Bleibe informiert über neue
                    <strong>Angebote und Veranstaltungen</strong>
                    im Amt Süderbrarup.
                </span>
                <br />
                <span>Ganz einfach anmelden – jederzeit abbestellbar.</span>
            </p>

            <RouterLink :to="{ name: 'public.exports.index' }">
                <Button
                    icon-left="notifications"
                    label="Benachrichtigungen abonnieren"
                />
            </RouterLink>
        </div>
    </div>
</template>
<style scoped>
h3 {
    padding-top: 0 !important;
}
</style>
