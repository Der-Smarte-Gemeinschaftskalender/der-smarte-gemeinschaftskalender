<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { instanceInformation, landingPage, searchDefaults } from '@/lib/instanceConfig';
import { isMobile } from '@/lib/helper';

import Button from '@/components/KERN/Button.vue';
import UpcomingEvents from '@/components/UpcomingEvents.vue';
import InputLocation from '@/components/KERN/inputs/InputLocation.vue';
import InputText from '@/components/KERN/inputs/InputText.vue';
import Divider from '@/components/KERN/cosmetics/Divider.vue';
import LandingPageCategories from '@/components/LandingPageCategories.vue';
import UpcomingEventsMap from '@/components/UpcomingEventsMap.vue';
import Icon from '@/components/KERN/cosmetics/Icon.vue';

const router = useRouter();
const locationSearchRef = ref<InstanceType<typeof InputLocation> | null>(null);
const searchTerm = ref<string>(searchDefaults.searchTerm || '');
const searchAddress = ref<string>(searchDefaults.locationAddress || '');
const searchRadius = ref<number>(+searchDefaults.searchRadius || 10);

const isMapExpanded = ref<boolean>(!isMobile);

const openSearch = () => {
    localStorage.setItem('searchTerm', searchTerm.value);
    localStorage.setItem('searchAddress', searchAddress.value);
    localStorage.setItem('searchRadius', searchRadius.value.toString());
    localStorage.setItem('locationSearchGeoHash', locationSearchRef.value?.geoHash || '');

    router.push({ name: 'public.search' });
};
</script>
<template>
    <div class="w-full">
        <Teleport to="#headerslot">
            <div class="mb-3 mt-4 sm:mb-4 sm:mt-5 md:my-6">
                <h1 class="kern-heading text-theme-primary">{{ landingPage.heading }}</h1>
                <h2
                    v-if="landingPage.description && landingPage.description !== ''"
                    class="kern-heading font-semilight text-theme-primary mb-6"
                >
                    {{ landingPage.description }}
                </h2>
                <p
                    v-if="landingPage.descriptionHtml && landingPage.descriptionHtml !== ''"
                    class="kern-text"
                    v-html="landingPage.descriptionHtml"
                ></p>
                <template v-if="landingPage.showNotification">
                    <RouterLink
                        class="text-lg flex align-items-center gap-2 w-fit"
                        :to="{ name: 'public.exports.index' }"
                    >
                        <Icon
                            name="notifications"
                            color="black"
                        />
                        {{ $t('public.landing.subscribeNotifications') }}
                    </RouterLink>

                    <p class="mt-4">
                        <span>{{ $t('public.landing.stayInformed') }}</span>
                    </p>
                </template>
            </div>
        </Teleport>

        <div class="mb-6 mt-2">
            <h3 class="kern-heading text-theme-primary">{{ $t('public.landing.searchEvents') }}</h3>

            <div class="flex flex-column md:flex-row gap-4 align-content-start md:align-items-center">
                <InputText
                    v-model="searchTerm"
                    :aria-label="$t('public.landing.searchTermLabel')"
                    :placeholder="$t('public.landing.searchPlaceholder')"
                    name="searchTerm"
                    class="col"
                />

                <InputLocation
                    ref="locationSearchRef"
                    v-model:address="searchAddress"
                    v-model:radius="searchRadius"
                    :search-initially="true"
                    class="md:max-w-20rem lg:max-w-max"
                />

                <Button
                    variant="secondary"
                    icon-left="search"
                    class="w-fit"
                    :disabled="locationSearchRef?.triggered"
                    @click="openSearch()"
                >
                    {{ $t('public.landing.searchButton') }}
                </Button>
            </div>
        </div>
        <LandingPageCategories v-if="landingPage.showCategories" />
        <details
            class="kern-accordion mt-4"
            :open="isMapExpanded"
            @toggle="isMapExpanded = ($event.target as HTMLDetailsElement).open"
        >
            <summary class="kern-accordion__header">
                <span class="flex gap-2 align-items-center">{{ landingPage.upcomingEventsMapTitle }}</span>
            </summary>
            <section class="kern-accordion__content">
                <UpcomingEventsMap v-if="isMapExpanded" />
            </section>
        </details>

        <UpcomingEvents />

        <Divider
            v-if="landingPage.showNotification"
            class="my-7"
        />
        <div v-if="landingPage.showNotification">
            <h3 class="kern-heading text-theme-primary">{{ $t('public.landing.neverMissEvent') }}</h3>
            <p class="mb-6">
                <span class="block mb-1">
                    {{ $t('public.landing.stayInformedAboutNew') }}
                    <strong>{{ $t('public.landing.offersAndEvents') }}</strong>
                    {{ $t('public.landing.in') }} {{ instanceInformation.name }}.
                </span>

                <span>{{ $t('public.landing.easySignup') }}</span>
            </p>

            <RouterLink
                class="text-lg flex align-items-center gap-2 w-fit"
                :to="{ name: 'public.exports.index' }"
            >
                <Icon name="notifications" />
                {{ $t('public.landing.subscribeNotifications') }}
            </RouterLink>
        </div>
    </div>
</template>
<style scoped>
h3 {
    padding-top: 0 !important;
}
</style>
