<script setup lang="ts">
import { ref, watch } from 'vue';
import { useRoute } from 'vue-router';

import Icon from './KERN/cosmetics/Icon.vue';
import Button from './KERN/Button.vue';
import Overlayable from '@/components/Overlayable.vue';

interface Props {
  showNavigation?: boolean;
}

const logoUrl = import.meta.env.VITE_APP_LOGO_PATH || '/logo.png';
const overlayable = ref<InstanceType<typeof Overlayable> | null>(null);
const instanceName = ref<string>(import.meta.env.VITE_INSTANCE_NAME);

const route = useRoute();

const { showNavigation = true } = defineProps<Props>();

const navigationItems = [
    {
        title: 'Veranstaltungen',
        icon: 'list_alt',
        name: 'public.search',
        button: false,
    },
    {
        title: 'Kalender',
        icon: 'calendar_month',
        name: 'public.calendar',
        button: false,
    },
    {
        title: 'Organisationen',
        icon: 'account_balance',
        name: 'public.organisations',
        button: false,
    },
    {
        title: 'Über den Kalender',
        icon: 'contact',
        link: 'https://der-smarte-gemeinschaftskalender.de',
        button: false,
    },
    {
        title: 'Intern',
        icon: 'login',
        name: 'login',
        button: true,
    },
];

const showNotificationImageInHeader = ref<boolean>(false);

watch(
    () => route.meta.showNotificationImageInHeader,
    (newVal) => {
        showNotificationImageInHeader.value = newVal as boolean;
    }
);
</script>

<template>
    <header class="py-3">
        <div class="header-content">
            <div class="flex flex-column lg:flex-row gap-3 lg:gap-0">
                <div class="lg:w-fit flex align-items-center justify-content-between">
                    <RouterLink :to="{ name: 'landingpage' }">
                        <img
                            class="logo-image"
                            :src="logoUrl"
                            :alt="`Logo ${instanceName}`"
                        />
                    </RouterLink>
                    <slot name="after-logo" />
                    <div
                        v-if="showNavigation"
                        class="flex lg:hidden gap-2"
                    >
                        <RouterLink
                            v-if="!$route.path?.toString().includes('app')"
                            :to="{ name: navigationItems[4].name }"
                            class="py-0"
                        >
                            <Button
                                :icon-size="'md'"
                                class="text-sm min-h-0 p-2"
                                :body-class="'py-0'"
                                :icon-left="navigationItems[4].icon"
                                variant="secondary"
                                :title="navigationItems[4].title"
                                :aria-label="navigationItems[4].title"
                            />
                        </RouterLink>
                        <Button
                            :icon-size="'md'"
                            class="text-sm min-h-0 p-2 sm:hidden"
                            :body-class="'py-0'"
                            :icon-left="'list'"
                            aria-label="Menü"
                            title="Menü"
                            variant="secondary"
                            @click="overlayable?.openOverlay()"
                        />
                    </div>
                </div>
                <Overlayable
                    ref="overlayable"
                    panel-class="main-navigation kern-col hidden sm:flex flex-wrap w-full align-content-center justify-content-end"
                    overlay-class="flex flex-column gap-2 p-4"
                    v-if="showNavigation"
                >
                    <template
                        v-for="(navigationItem, index) in navigationItems"
                        :key="navigationItem.title"
                    >
                        <RouterLink
                            v-if="navigationItem.name"
                            :to="{ name: navigationItem.name }"
                            class="my-2 py-1 w-min align-items-center align-content-center underline text-theme-primary font-medium"
                            @click="overlayable?.closeOverlay()"
                            :active-class="
                                navigationItem.name != 'login' ? 'router-link-active' : 'router-link-active-button'
                            "
                            :class="{
                                hidden: navigationItem.name === 'login' && $route.path?.toString().includes('app'),
                                'flex sm:hidden lg:flex sm:pl-3':
                                    navigationItem.name === 'login' && !$route.path?.toString().includes('app'),
                                'sm:px-3': !navigationItem.button && index !== 0,
                                'pr-3': index === 0,
                            }"
                        >
                            <span
                                v-if="!navigationItem.button"
                                class="flex align-items-center align-content-center w-max"
                            >
                                <Icon
                                    :name="navigationItem.icon"
                                    class="mr-2"
                                />
                                {{ navigationItem.title }}
                            </span>
                            <Button
                                v-else
                                :icon-left="navigationItem.icon"
                                variant="secondary"
                            >
                                {{ navigationItem.title }}
                            </Button>
                        </RouterLink>

                        <!-- External links -->
                        <a
                            v-else-if="navigationItem.link"
                            :href="navigationItem.link"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="my-2 py-1 w-min align-items-center align-content-center underline text-theme-primary font-medium sm:px-3"
                            @click="overlayable?.closeOverlay()"
                        >
                            <span class="flex align-items-center align-content-center w-max">
                                <Icon
                                    :name="navigationItem.icon"
                                    class="mr-2"
                                />
                                {{ navigationItem.title }}
                            </span>
                        </a>
                    </template>
                </Overlayable>
            </div>
            <div class="flex gap-8 w-full align-items-center">
                <section
                    :class="{
                        'lg:col-7': showNotificationImageInHeader,
                        'w-full': !showNotificationImageInHeader,
                    }"
                >
                    <div
                        id="headerslot"
                        class="mt-4"
                    ></div>
                    <slot name="main-header-slot"></slot>
                </section>
                <div
                    v-if="showNotificationImageInHeader"
                    class="lg:block hidden lg:m-6 xl:m-8"
                >
                    <img
                        src="/notifications.png"
                        alt="Bild"
                        class="w-full h-auto"
                    />
                </div>
            </div>
            <div class="bottom-border"></div>
        </div>
    </header>
</template>

<style scoped lang="scss">
header {
    position: relative;
    height: 100%;
    background-color: #f4fdfb;
    background-size: 100% 300%;

    .logo-image {
        width: 180px;
        height: auto;
        display: block;
    }

    .bottom-border {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 0.5rem;
        background-image: linear-gradient(to right, #a9d3a9, #7dcce8);
    }



    @media (min-width: 1024px) {
        .logo-image {
            width: 200px;
        }
    }
    @media (min-width: 1280px) {
        .logo-image {
            width: 230px;
        }
    }

    @media (min-width: 1536px) {
        .logo-image {
            width: 250px;
        }
    }
}

.main-navigation {
    .router-link-active {
        text-decoration: none !important;
    }

    @media (max-width: 1280px) {
        * {
            font-size: 0.9rem !important;
        }
    }

    @media (max-width: 640px) {
        * {
            font-size: 0.8rem !important;
        }
    }
}
</style>
