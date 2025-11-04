<script lang="ts" setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { findOrganisation } from '@/lib/mobilizonClient';
import {formatCoordinates, normalizeStreet, stripHtml} from '@/lib/helper';

import EventCard from '@/components/EventCard.vue';
import Loader from '@/components/KERN/cosmetics/Loader.vue';
import Button from '@/components/KERN/Button.vue';
import Icon from '@/components/KERN/cosmetics/Icon.vue';
import NavigatePhysicalAddress from '@/components/NavigatePhysicalAddress.vue';
import ShareLinks from '@/components/ShareLinks.vue';
import Map from '@/components/Map.vue';


const route = useRoute();
const router = useRouter();

const organisation = ref<any>({});
const loading = ref(true);

const loadOrganisations = async () => {
    try {
        const result = await findOrganisation(route.params.preferredUsername as string, {
            afterDateTime: new Date().toISOString(),
        });

        if (!result) return;

        organisation.value = result;
    } catch (error) {
        console.error('Error loading group:', error);
        await router.push({ name: 'public.notFound' });
    } finally {
        loading.value = false;
    }
};

const downloadIcsEvent = async (): Promise<void> => {
    const data = await (
        await fetch(`${import.meta.env.VITE_MOBILIZON_URL}/@${route.params.preferredUsername}/feed/ics`)
    ).text();
    const blob = new Blob([data], { type: 'text/calendar' });
    const link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = `${route.params.preferredUsername}.ics`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

loadOrganisations();
</script>
<template>
    <template v-if="!!organisation && !loading">
        <Teleport to="#headerslot">
            <div
                class="flex flex-row flex-wrap gap-4 mb-3 mt-4 sm:mb-4 sm:mt-5 md:my-6 align-items-center justify-content-between"
            >
                <h1 class="kern-heading text-theme-primary block w-fit">{{ organisation.name }}</h1>
                <RouterLink
                    class="w-fit"
                    :to="{
                        name: 'materialGenerator.organisation',
                        params: { preferredUsername: organisation.preferredUsername },
                    }"
                >
                    <Button
                        icon-left="wall_art"
                        label="Werbematerialien erstellen"
                        class="text-sm min-h-0 md:text-base px-2 md:px-3"
                        :body-class="'py-0-5rem md:py-1rem'"
                    />
                </RouterLink>
            </div>
        </Teleport>
        <div class="w-full">
            <div class="flex flex-column lg:flex-row gap-6 justify-content-between">
                <div class="lg:col-6 p-0">
                    <h2 class="kern-heading font-semilight text-theme-primary mb-4">Über die Organisation</h2>
                    <p
                        v-html="stripHtml(organisation.summary) ? organisation.summary : 'Es wurde keine Beschreibung angegeben.'"
                        class="kern-text prose"
                    ></p>
                    <h3 class="kern-heading font-semilight text-theme-primary mb-3 mt-6">Adresse</h3>
                    <template v-if="organisation?.physicalAddress">
                        <div class="kern-text flex gap-2 align-items-start">
                            <Icon name="location_on" />
                            <!--<div>{{ event?.physicalAddress?.description }}</div>-->
                            <div>
                                <div v-if="organisation?.physicalAddress?.street">
                                    {{ normalizeStreet(organisation?.physicalAddress?.street) }},
                                </div>
                                <div>
                                    {{ organisation?.physicalAddress?.postalCode }}
                                    {{ organisation?.physicalAddress?.locality }}
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <p class="kern-text">Keine Adresse angegeben</p>
                    </template>
                </div>
                <div class="lg:col p-0 aspect-ratio-16-9">
                    <img
                        :src="organisation.avatar?.url ?? '/default_card.png'"
                        :alt="organisation.avatar?.alt || 'Organisation Logo'"
                        class="ml-auto rounded-lg w-full object-cover mb-5"
                    />
                    <template v-if="organisation.physicalAddress?.geom">
                        <Map
                            style="height: 300px"
                            search-value=""
                            :physical-address="organisation.physicalAddress"
                            :zoom="14"
                        />
                        <NavigatePhysicalAddress
                            :physical-address="organisation.physicalAddress"
                            :coordinates="formatCoordinates(organisation?.physicalAddress?.geom)"
                        />
                    </template>
                    <Button
                        v-if="organisation?.organizedEvents?.elements?.length"
                        icon-left="download"
                        @click="downloadIcsEvent"
                        class="mt-4"
                        variant="secondary"
                    >
                        Bevorstehende Veranstaltungen zum Kalender hinzufügen
                    </Button>
                </div>
            </div>
            <div v-if="organisation?.organizedEvents?.elements?.length">
                <div class="kern-row mb-5">
                    <div class="kern-col-lg-6">
                        <h3 class="kern-heading font-medium my-4 text-theme-primary">Bevorstehende Veranstaltungen</h3>
                    </div>
                    <div class="kern-col-lg-6">
                        <div class="kern-text kern-text--bold text-theme-primary text-right mt-4">
                            Veranstaltungen teilen
                        </div>
                        <div class="flex justify-content-end">
                            <ShareLinks
                                v-if="organisation?.organizedEvents?.elements.length"
                                :type="'eventList'"
                                :eventList="organisation?.organizedEvents?.elements"
                                :link-to-url="`/organisation/${organisation.preferredUsername}`"
                            >
                                <RouterLink
                                    :to="{
                                        name: 'public.infomonitor',
                                        query: { preferredUsername: organisation.preferredUsername },
                                    }"
                                >
                                    <Icon
                                        name="info"
                                        class="w-2rem h-2rem sm:w-3rem sm:h-3rem"
                                        title="Infomonitor anzeige"
                                    />
                                </RouterLink>
                            </ShareLinks>
                        </div>
                    </div>
                </div>
                <div class="cards-template">
                    <div
                        v-for="event in organisation.organizedEvents.elements"
                        :key="event.uuid"
                    >
                        <EventCard
                            :event="event"
                            class="h-full w-full"
                        />
                    </div>
                </div>
            </div>
            <div v-else>
                <h3 class="kern-heading text-theme-primary mt-6 mb-4">Keine bevorstehenden Veranstaltungen</h3>
            </div>
        </div>
    </template>
    <template v-else>
        <Loader />
    </template>
</template>

