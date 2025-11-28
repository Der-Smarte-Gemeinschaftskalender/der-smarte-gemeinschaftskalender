<script setup lang="ts">
import { findOrganisation, searchEvents } from '@/lib/mobilizonClient';
import { useRoute } from 'vue-router';
import dayjs from '@/lib/dayjs';
import { computed, ref } from 'vue';
import Progress from '@/components/KERN/cosmetics/Progress.vue';
import Loader from '@/components/KERN/cosmetics/Loader.vue';
import Icon from '@/components/KERN/cosmetics/Icon.vue';
import EventStatusBadge from '@/components/EventStatusBadge.vue';
import {
    formatOnDate,
    formatOnDateTime,
    formatOnTime,
    formatCoordinates,
    formattedLanguage,
    getBeginsEndsOn,
} from '@/lib/helper';
import { type IEvent, type IEventDetailed, MobilizonCategory, MobilizonEventJoinOptions } from '@/types/General';
import { mobilizon_event_language_options, mobilizon_category_options } from '@/lib/const';
import Button from '@/components/KERN/Button.vue';
import Header from '@/components/Header.vue';
import QRCodeVue3 from 'qrcode-vue3';
import { createFullEventUrl, createFullUrl } from '@/lib/shareInformation';

const instanceName = import.meta.env.VITE_INSTANCE_NAME;

const route = useRoute();

const events = ref([]);
const loading = ref<boolean>(false);
const showCurrentEvent = ref<number>(0);
const showEventInSeconds = ref<number>(parseInt(route.query.showEventInSeconds as string) || 30);
const progressUpdatesPerSecond = ref<number>(20);
const currentEventShowtime = ref<number>(0);
const preferredUsername = ref<string | null>(route.query.preferredUsername as string | null);
const organisationName = ref<string | null>(null);

const event = computed(() => {
    return events.value[showCurrentEvent.value] || null;
});

const loadEvents = async () => {
    loading.value = true;
    try {
        if (!!preferredUsername.value) {
            const result = await findOrganisation(
                preferredUsername.value,
                {
                    afterDateTime: new Date().toISOString(),
                },
                'language, description,joinOptions, category, tags { slug, title}, physicalAddress { country, description, locality, postalCode, street, type, geom }'
            );
            organisationName.value = result?.name || null;
            events.value = result?.organizedEvents?.elements || [];
        } else {
            const { beginsOn, endsOn } = getBeginsEndsOn((route.query.searchBegin as string) || 'all');
            const result = await searchEvents(
                1,
                50,
                (route.query.searchTerm as string) || '',
                (route.query.searchTarget as string) || 'INTERNAL',
                beginsOn?.toISOString(),
                endsOn?.toISOString(),
                (route.query.searchCategory as string[]) || [],
                (route.query.searchStatus as string[]) || ['CONFIRMED'],
                (route.query.searchLanguage as string[]) || [],
                (route.query.locationSearchGeoHash as string) || '',
                ((parseInt(route.query.searchRadius as string) || 10) as number) || 10,
                'language, description,joinOptions'
            );
            events.value = result.searchEvents.elements;
        }
    } catch (error) {
        console.error('Error loading events:', error);
    } finally {
        showCurrentEvent.value = 0;
        loading.value = false;
    }
};
setInterval((): void => {
    currentEventShowtime.value += 1;
    if (currentEventShowtime.value > showEventInSeconds.value * progressUpdatesPerSecond.value) {
        if (showCurrentEvent.value >= events.value.length - 1) {
            showCurrentEvent.value = 0;
            void (async () => {
                await loadEvents();
            })();
        } else {
            showCurrentEvent.value += 1;
        }

        currentEventShowtime.value = 0;
    }
}, 1000 / progressUpdatesPerSecond.value); // 60 * 1000 milliseconds
loadEvents();
</script>
<template>
    <Header :show-navigation="false">
        <template #after-logo>
            <div class="lg:px-5">
                <h1
                    v-if="!organisationName"
                    class="kern-heading text-theme-primary"
                >
                    {{ instanceName }}
                </h1>
                <h3
                    v-else-if="organisationName"
                    class="kern-heading text-theme-primary"
                >
                    {{ instanceName }} -
                    {{ organisationName }}
                </h3>
            </div>
        </template>
    </Header>
    <div v-if="!loading">
        <div
            v-if="!events.length"
            class="flex justify-content-center"
        >
            <p class="kern-text">Keine Veranstaltung mit diesen Suchkriterien gefunden.</p>
        </div>
        <div
            v-else
            class="py-3 px-2 md:px-3 mx-2 lg:mx-4 xl:mx-6 infomonitor"
        >
            <div
                class="main-infomonitor-content"
                v-if="!!event"
            >
                <div class="kern-row">
                    <div class="kern-col">
                        <div class="kern-col">
                            <h1 class="kern-heading text-theme-primary">
                                {{ event.title }}
                            </h1>

                            <h2 class="kern-heading text-theme-primary">
                                <Icon
                                    name="person"
                                    style="transform: scale(2)"
                                    class="mr-2"
                                />
                                {{ event?.attributedTo?.name || organisationName }}
                            </h2>
                            <div class="kern-row">
                                <div class="kern-col pl-0">
                                    <h3 class="kern-heading font-medium text-theme-primary my-3">Wann?</h3>
                                    <section v-if="formatOnDate(event?.beginsOn) === formatOnDate(event?.endsOn)">
                                        <div class="kern-text flex gap-2 align-items-start">
                                            <Icon name="calendar_month" />
                                            {{ formatOnDate(event?.beginsOn) }}
                                        </div>
                                        <div class="kern-text flex gap-2 align-items-start">
                                            <Icon name="schedule" />
                                            {{ formatOnTime(event?.beginsOn) }}
                                            bis
                                            {{ formatOnTime(event?.endsOn) }}
                                        </div>
                                    </section>
                                    <section v-else>
                                        <div class="kern-text flex gap-2 align-items-start">
                                            <Icon name="calendar_month" />
                                            Start:
                                            {{ formatOnDateTime(event?.beginsOn) }}
                                        </div>
                                        <div class="kern-text flex gap-2 align-items-start">
                                            <Icon name="none" />
                                            Ende:
                                            {{ formatOnDateTime(event?.endsOn) }}
                                        </div>
                                    </section>
                                </div>
                                <div class="kern-col">
                                    <h3 class="kern-heading font-medium text-theme-primary my-3">Wo?</h3>
                                    <div
                                        v-if="event?.physicalAddress"
                                        class="kern-text flex gap-2 align-items-start"
                                    >
                                        <Icon name="location_on" />
                                        <!--<div>{{ event?.physicalAddress?.description }}</div>-->
                                        <div>
                                            <div>{{ event?.physicalAddress?.street }}</div>
                                            <div>
                                                {{ event?.physicalAddress?.postalCode }}
                                                {{ event?.physicalAddress?.locality }}
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        v-if="!!event?.onlineAddress"
                                        class="kern-text flex gap-2 align-items-start"
                                    >
                                        <Icon name="link" />
                                        <a
                                            :href="event?.onlineAddress"
                                            target="_blank"
                                        >
                                            {{ event?.onlineAddress }}
                                        </a>
                                    </div>
                                    <div
                                        v-if="!event?.physicalAddress && !event?.onlineAddress"
                                        class="kern-text flex gap-2 align-items-start"
                                    >
                                        Keine Adresse angegeben
                                    </div>
                                </div>
                            </div>
                        </div>
                        <h3 class="kern-heading font-medium text-theme-primary my-3">Was?</h3>
                        <div class="description-container">
                            <div
                                class="kern-text pr-6"
                                v-html="event.description"
                            ></div>
                        </div>
                        <div class="kern-row">
                            <div class="kern-col">
                                <h3 class="kern-heading text-theme-primary my-2">Sprache</h3>
                                <div class="kern-text py-2">
                                    <Icon name="language" />
                                    {{ formattedLanguage(event.language) }}
                                </div>
                            </div>
                            <div class="kern-col">
                                <h3 class="kern-heading text-theme-primary my-2">Status</h3>
                                <div class="py-2">
                                    <EventStatusBadge :status="event.status" />
                                </div>
                            </div>
                            <div class="kern-col">
                                <h3 class="kern-heading text-theme-primary my-2">Kategorie</h3>
                                <RouterLink :to="{ name: 'public.search', query: { category: event.category } }">
                                    <Button variant="secondary">
                                        {{
                                            mobilizon_category_options.find(
                                                (category_option) =>
                                                    (category_option.value as MobilizonCategory) === event!.category
                                            )?.text
                                        }}
                                    </Button>
                                </RouterLink>
                            </div>
                        </div>
                        <div v-if="event?.tags?.length">
                            <h3 class="kern-heading text-theme-primary mb-3">Schlagwörter</h3>
                            <template
                                v-for="tag in event.tags"
                                :key="tag"
                            >
                                <RouterLink
                                    :to="{ name: 'public.search', query: { tag: tag.title } }"
                                    class="m-2 mb-2"
                                >
                                    <Button variant="secondary">{{ tag.title }}</Button>
                                </RouterLink>
                            </template>
                        </div>
                    </div>
                    <div class="kern-col">
                        <div class="mb-4">
                            <template v-if="event.joinOptions === MobilizonEventJoinOptions.EXTERNAL">
                                <h3 class="kern-heading font-medium text-theme-primary my-3">Externe Anmeldung</h3>
                            </template>
                        </div>
                        <div class="flex justify-content-center">
                            <img
                                v-if="!event.picture"
                                src="/default_card.svg"
                                class="main-img"
                            />
                            <img
                                v-else
                                :src="event.picture.url"
                                class="main-img"
                            />
                        </div>
                        <div
                            v-if="!!event?.onlineAddress"
                            class="kern-text"
                        >
                            <h3 class="kern-heading text-theme-primary mb-3 mt-3">Webseite</h3>
                            <Icon
                                name="link"
                                class="mr-2"
                            />
                            <a
                                :href="event?.onlineAddress"
                                target="_blank"
                            >
                                {{ event?.onlineAddress }}
                            </a>
                        </div>
                        <div class="kern-row mt-5 text-center">
                            <h3 class="kern-heading font-medium text-theme-primary my-1 mt-6 text-center">
                                Alle Infos zum Event
                            </h3>
                            <div class="flex justify-content-center mb-4">
                                <QRCodeVue3
                                    :value="createFullEventUrl(event.uuid)"
                                    :width="250"
                                    :height="250"
                                    :imageOptions="{ hideBackgroundDots: true, imageSize: 0.4, margin: 0 }"
                                    :dotsOptions="{ type: 'square' }"
                                ></QRCodeVue3>
                            </div>
                            <p>{{ createFullEventUrl(event.uuid) }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex align-items-center mt-4">
                <Progress
                    class="w-full"
                    id="event-progress"
                    :value="currentEventShowtime"
                    :max="showEventInSeconds * progressUpdatesPerSecond"
                />
            </div>
        </div>
    </div>
    <div
        class="flex justify-content-center"
        v-else
    >
        <Loader />
    </div>
</template>
<style scoped>
.infomonitor {
    height: calc(100vh - 150px - 2rem);
    max-height: calc(100vh - 150px - 2rem);
}
.main-infomonitor-content {
    height: calc(95vh - 150px - 2rem);
    max-height: calc(95vh - 150px - 2rem);
    min-height: calc(95vh - 150px - 2rem);
    overflow-y: hidden;
    overflow-x: hidden;
}
.main-img {
    max-height: 30vh;
}
.description-container {
    max-height: 15vh;
    overflow-y: auto;
}
</style>
