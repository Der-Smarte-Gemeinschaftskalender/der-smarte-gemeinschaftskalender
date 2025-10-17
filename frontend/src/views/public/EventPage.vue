<script lang="ts" setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { findEvent, findNextEvents } from '@/lib/mobilizonClient';
import { checkLogin } from '@/composables/UserComposoable';
import { user_organisations } from '@/composables/OrganisationComposable';
import { formatOnDate, formatOnDateTime, formatOnTime, formatCoordinates, formattedLanguage } from '@/lib/helper';
import { mobilizon_category_options } from '@/lib/const';

import Loader from '@/components/KERN/cosmetics/Loader.vue';
import Icon from '@/components/KERN/cosmetics/Icon.vue';
import EventStatusBadge from '@/components/EventStatusBadge.vue';
import Button from '@/components/KERN/Button.vue';
import EventCard from '@/components/EventCard.vue';
import Map from "@/components/Map.vue";
import EventShare from '@/components/ShareLinks.vue';
import Divider from '@/components/KERN/cosmetics/Divider.vue';
import NavigatePhysicalAddress from '@/components/NavigatePhysicalAddress.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';

import { type IEvent, type IEventDetailed, MobilizonCategory, MobilizonEventJoinOptions } from '@/types/General';

const route = useRoute();
const router = useRouter();

const event = ref<IEventDetailed>();
const isUserEvent = ref(false);
const nextEvents = ref<IEvent[]>([]);

const loading = ref(true);

const loadEvent = async () => {
    try {
        if (!route.params.uuid) {
            console.error('No UUID provided');
            return;
        }

        const result = await findEvent(route.params.uuid as string);

        if (!result) {
            await router.push({ name: 'public.notFound' });
            return;
        }

        event.value = result;

        if (checkLogin()) {
            // check if user_organisations is in event.attributedTo.id
            if (user_organisations.value) {
                isUserEvent.value = user_organisations.value.some((org) => org.id === +event.value!.attributedTo?.id);
            }
        }

        loading.value = false;
        await loadNextEvent();
    } catch (e) {
        console.error(e);
    }
};

const loadNextEvent = async () => {
    const { elements } = await findNextEvents(3, new Date());
    nextEvents.value = elements;
};

const downloadIcsEvent = async (): Promise<void> => {
    const data = await (
        await fetch(`${import.meta.env.VITE_MOBILIZON_URL}/events/${route.params.uuid}/export/ics`)
    ).text();
    const blob = new Blob([data], { type: 'text/calendar' });
    const link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = `${event.value?.title}.ics`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const selectCategoryAndRedirect = (category: MobilizonCategory) => {
    localStorage.setItem('searchCategory', JSON.stringify([category]));
    router.push({ name: 'public.search' });
};

const getCategoryText = (category: MobilizonCategory) => {
    return mobilizon_category_options.find((option) => option.value === category)?.text || 'Unbekannt';
};

loadEvent();
</script>
<template>
    <template v-if="!!event && !loading">
        <Teleport to="#headerslot">
            <div class="mb-3 mt-4 sm:mb-4 sm:mt-5 md:my-6">
                <div class="kern-row mt-4">
                    <div class="kern-col">
                        <h1 class="kern-heading text-theme-primary">
                            {{ event.title }}
                        </h1>
                    </div>
                    <div
                        class="kern-col flex justify-content-end align-items-center"
                        v-if="event.status != 'CONFIRMED'"
                    >
                        <EventStatusBadge
                            v-if="event.status"
                            :status="event.status"
                        />
                    </div>
                </div>
                <div class="kern-row">
                    <div
                        class="kern-col"
                        v-if="event.attributedTo?.preferredUsername"
                    >
                        <h2 class="kern-heading text-theme-primary">
                            <RouterLink
                                :to="{
                                    name: 'public.organisations.show',
                                    params: { preferredUsername: event.attributedTo.preferredUsername },
                                }"
                                class="flex align-items-center gap-3 text-decoration-none font-semilight text-theme-primary"
                            >
                                <Icon
                                    name="person"
                                    style="transform: scale(2)"
                                    class="mr-2"
                                />
                                <span class="block">
                                    {{ event.attributedTo.name }}
                                </span>
                            </RouterLink>
                        </h2>
                    </div>
                    <div class="kern-col flex justify-content-end align-items-center">
                        <template v-if="!!isUserEvent">
                            <RouterLink
                                class="ml-2"
                                :to="{ name: 'materialGenerator.event', params: { uuid: route.params.uuid } }"
                            >
                                <Button
                                    icon-left="wall_art"
                                    class="mr-2"
                                >
                                    Werbemittel
                                </Button>
                            </RouterLink>
                            <RouterLink :to="{ name: 'createdEvent.findEvent', params: { uuid: route.params.uuid } }">
                                <Button
                                    aria-label="Bearbeiten"
                                    class="px-1 mr-2"
                                    title="Bearbeiten"
                                >
                                    <Icon
                                        name="edit"
                                        color="white"
                                    />
                                </Button>
                            </RouterLink>
                        </template>
                    </div>
                </div>
            </div>
        </Teleport>
        <div class="w-full">
            <div class="flex lg:flex-row flex-column gap-6">
                <div class="kern-col">
                    <div class="w-full flex sm:flex-row justify-content-between flex-column gap-5 flex-wrap">
                        <div>
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
                        <div>
                            <h3 class="kern-heading font-medium text-theme-primary my-3">Wo?</h3>
                            <div
                                v-if="event?.physicalAddress?.geom"
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
                                <!--<p>Du kannst online Teilnehmen!</p>-->
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
                                <!-- <Icon name="" /> -->
                                Keine Adresse angegeben
                            </div>
                        </div>
                    </div>
                    <h3 class="kern-heading font-medium text-theme-primary mb-3 mt-6">Was?</h3>
                    <div
                        class="kern-text"
                        v-html="event.description"
                    ></div>
                    <div class="mt-5">
                        <div class="flex justify-content-between gap-5">
                            <div>
                                <h3 class="kern-heading text-theme-primary my-3">Sprache</h3>
                                <div class="kern-text py-2">
                                    <Icon name="language" />
                                    {{ formattedLanguage(event.language) }}
                                </div>
                            </div>
                            <div>
                                <h3 class="kern-heading text-theme-primary my-3">Status</h3>
                                <div class="py-2">
                                    <EventStatusBadge :status="event.status" />
                                </div>
                            </div>
                            <div>
                                <h3 class="kern-heading text-theme-primary my-3">Kategorie</h3>
                                <Button variant="secondary" @click="selectCategoryAndRedirect(event.category)">
                                    {{ getCategoryText(event.category) }}
                                </Button>
                            </div>
                        </div>
                        <h3 class="kern-heading text-theme-primary mb-3 mt-6">
                            Termin zum persönlichen Kalender hinzufügen
                        </h3>
                        <p class="kern-text mb-5">
                            Sie können diesen Termin herunterladen und in Ihrem persönlichen Kalender speichern. Weitere
                            Informationen finden Sie im
                            <LinkToDocs
                                path="DSG%20Funktionen/"
                                fragment="veranstaltungsdatei-exportieren"
                            />.
                        </p>
                        <Button
                            icon-left="download"
                            @click="downloadIcsEvent"
                        >
                            Zum Kalender hinzufügen
                        </Button>
                        <div v-if="event?.tags?.length">
                            <h3 class="kern-heading text-theme-primary mb-3 mt-6">Schlagwörter</h3>
                            <div class="flex flex-wrap gap-2">
                                <template
                                    v-for="tag in event.tags"
                                    :key="tag"
                                >
                                    <RouterLink :to="{ name: 'public.search', query: { tag: tag.title } }">
                                        <Button variant="secondary">{{ tag.title }}</Button>
                                    </RouterLink>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="kern-col">
                    <div class="mb-4">
                        <template v-if="event.joinOptions === MobilizonEventJoinOptions.EXTERNAL">
                            <h3 class="kern-heading font-medium text-theme-primary my-3">Externe Anmeldung</h3>
                            <a
                                :href="event.externalParticipationUrl!"
                                target="_blank"
                            >
                                <Button icon-left="open-in-new">Jetzt anmelden</Button>
                            </a>
                        </template>
                    </div>
                    <!-- Bild anzeigen oder Default -->
                    <div>
                        <img
                            :src="event.picture ? event.picture.url : '/default_card.png'"
                            alt="Event Bild"
                            class="w-full"
                        />
                    </div>
                    <div>
                        <div
                            v-if="!!event.physicalAddress?.geom"
                            class="mb-3"
                        >
                            <h3 class="kern-heading text-theme-primary mb-3 mt-5">Karte</h3>
                            <Map
                                style="height: 300px"
                                search-value=""
                                :physical-address="event.physicalAddress"
                                :zoom="14"
                            />

                            <NavigatePhysicalAddress
                                :physical-address="event.physicalAddress"
                                :coordinates="formatCoordinates(event?.physicalAddress?.geom)"
                            />
                        </div>
                        <div
                            v-if="!!event?.onlineAddress"
                            class="kern-text"
                        >
                            <h3 class="kern-heading text-theme-primary my-3">Webseite</h3>
                            <!--<p>Du kannst online Teilnehmen!</p>-->
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
                    </div>
                    <h3 class="kern-heading text-theme-primary mb-3 mt-3">
                        <Icon
                            name="share"
                            style="transform: scale(1.5)"
                        />
                        Veranstaltung teilen
                    </h3>
                    <EventShare
                        :event="event"
                        type="singleEvent"
                    />
                </div>
            </div>
            <Divider class="my-8" />
            <h3 class="kern-heading text-theme-primary font-medium mb-2">
                Ähnliche Veranstaltungen
            </h3>
            <div class="cards-template">
                <div
                    v-for="relatedEvent in event.relatedEvents"
                    :key="relatedEvent.uuid"
                >
                    <EventCard
                        :event="relatedEvent"
                        class="h-full"
                    />
                </div>
            </div>
            <h3 class="kern-heading text-theme-primary font-medium mt-7 mb-2">
                Weitere Veranstaltungen aus dem Kalender Amt Süderbrarup
            </h3>
            <div class="cards-template">
                <div
                    v-for="nextEvent in nextEvents"
                    :key="nextEvent.uuid"
                >
                    <EventCard
                        :event="nextEvent"
                        class="h-full"
                    />
                </div>
                <div class="flex align-items-center justify-content-center">
                    <RouterLink :to="{ name: 'public.search' }">
                        <Button icon-right="arrow-forward">Alle Veranstaltungen anzeigen</Button>
                    </RouterLink>
                </div>
            </div>
        </div>
    </template>
    <template v-else>
        <Loader />
    </template>
</template>
