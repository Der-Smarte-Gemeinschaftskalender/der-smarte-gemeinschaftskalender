<script setup lang="ts">
import { ref } from 'vue';
import { findMobilizonStatistics, findOrganisation } from '@/lib/mobilizonClient';
import { getEventsStatistics } from '@/lib/dsgClient';
import { loadMobilizionGroups } from '@/composables/EventCreateFormComposable';
import { user_organisations } from '@/composables/OrganisationComposable';
import { user } from '@/composables/UserComposoable';
import { useRouter } from 'vue-router';
import type { MobilizonStatistics, EventsStatistics, Option } from '@/types/General';
import { current_organisation } from '@/composables/OrganisationComposable';
import Button from '@/components/KERN/Button.vue';
import Card from '@/components/KERN/Card.vue';
import EventCard from '@/components/EventCard.vue';

import LinkToDocs from '@/components/LinkToDocs.vue';

const router = useRouter();

const statistic = ref<MobilizonStatistics & EventsStatistics>({
    numberOfComments: 0,
    numberOfEvents: 0,
    numberOfGroups: 0,
    numberOfUsers: 0,
    numberOfInstanceFollowers: 0,
    numberOfInstanceFollowings: 0,
    numberOfLocalComments: 0,
    numberOfLocalEvents: 0,
    numberOfLocalGroups: 0,
    singleEventsCount: 0,
    seriesEventsCount: 0,
    uploadedEventsCount: 0,
    importedEventsCount: 0,
    confirmedEventsCount: 0,
    tentativeEventsCount: 0,
    cancelledEventsCount: 0,
    incomingEventsCount: 0,
});

const loading = ref(true);
const mobilizon_group_id = ref<number | null>(null);
const mobilizionGroupOptions = ref<Option[]>([]);
const organisation = ref<any>({});
const loadingOrganisation = ref(true);

const loadOrganisations = async () => {
    if (!current_organisation.value) {
        return;
    }
    try {
        const result = await findOrganisation(current_organisation.value?.preferredUsername as string, {
            afterDateTime: new Date().toISOString(),
        });

        if (!result) return;

        organisation.value = result;
    } catch (error) {
        console.error('Error loading group:', error);
    } finally {
        loadingOrganisation.value = false;
    }
};

const loadStatistics = async () => {
    try {
        await loadMobilizionGroups(mobilizon_group_id, mobilizionGroupOptions);
        if (!mobilizon_group_id.value) {
            console.warn('No Mobilizon group selected');
            return;
        }

        const mobilizonStatistics = await findMobilizonStatistics();
        const eventsStatistics = await getEventsStatistics(mobilizon_group_id.value);

        if (mobilizonStatistics) {
            statistic.value = {
                ...statistic.value,
                numberOfComments: mobilizonStatistics.numberOfComments,
                numberOfEvents: mobilizonStatistics.numberOfEvents,
                numberOfGroups: mobilizonStatistics.numberOfGroups,
                numberOfUsers: mobilizonStatistics.numberOfUsers,
                numberOfInstanceFollowers: mobilizonStatistics.numberOfInstanceFollowers,
                numberOfInstanceFollowings: mobilizonStatistics.numberOfInstanceFollowings,
                numberOfLocalComments: mobilizonStatistics.numberOfLocalComments,
                numberOfLocalEvents: mobilizonStatistics.numberOfLocalEvents,
                numberOfLocalGroups: mobilizonStatistics.numberOfLocalGroups,
            };
        }

        if (eventsStatistics) {
            statistic.value = {
                ...statistic.value,
                singleEventsCount: eventsStatistics.singleEventsCount,
                seriesEventsCount: eventsStatistics.seriesEventsCount,
                uploadedEventsCount: eventsStatistics.uploadedEventsCount,
                importedEventsCount: eventsStatistics.importedEventsCount,
                confirmedEventsCount: eventsStatistics.confirmedEventsCount,
                tentativeEventsCount: eventsStatistics.tentativeEventsCount,
                cancelledEventsCount: eventsStatistics.cancelledEventsCount,
                incomingEventsCount: eventsStatistics.incomingEventsCount,
            };
        }
    } catch (error) {
        console.error('Error loading statistics:', error);
    } finally {
        loading.value = false;
    }
};
loadStatistics();
loadOrganisations();
if (!user.value.person) {
    router.push('/app/profile/register-person');
}
</script>
<template>
    <h1 class="kern-heading text-theme-primary">
        Dashboard
        <template v-if="current_organisation">{{ current_organisation.name }}</template>
    </h1>
    <template v-if="!user_organisations?.length">
        <p class="kern-text">
            Sie sind noch kein Mitglied einer Organisation. Um Termine anzulegen, können Sie entweder selbst eine
            Organisation
            <RouterLink :to="{ name: 'app.myOrganisations.request' }">erstellen</RouterLink>
            oder sich in eine bestehende Organisation einladen lassen – dafür geben Sie einfach Ihren Profilnamen an die
            Organisation weiter.
        </p>
    </template>
    <template v-else>
        <p class="kern-text">
            <b>Willkommen im Verwaltungsbereich des Smarten Gemeinschaftskalenders!</b>
            Hier können Sie ganz einfach Ihre Organisation verwalten und Termine pflegen.
        </p>
    </template>
    <p class="kern-text mt-2">
        <b>Hinweis:</b>
        Eine Schritt-für-Schritt-Anleitung zur Terminverwaltung finden Sie im
        <LinkToDocs path="" />
        .
    </p>

    <div v-if="!loading">
        <h2 class="kern-heading text-theme-primary mt-6">Gesamtübersicht der Kalenderinstanz</h2>
        <div class="flex flex-wrap gap-4 mt-5">
            <Card
                title="Nutzer*innen"
                class="w-fit"
            >
                <span class="text-theme-primary kern-text kern-text--large kern-text--bold">
                    {{ statistic.numberOfUsers }}
                </span>
            </Card>
            <Card
                title="Veranstaltungen"
                class="w-fit"
            >
                <span class="text-theme-primary kern-text kern-text--large kern-text--bold">
                    {{ statistic.numberOfLocalEvents }}
                </span>
            </Card>
            <Card
                title="Organisationen"
                class="w-fit"
            >
                <span class="text-theme-primary kern-text kern-text--large kern-text--bold">
                    {{ statistic.numberOfLocalGroups }}
                </span>
            </Card>
        </div>
    </div>
    <div v-if="current_organisation">
        <h2 class="kern-heading text-theme-primary mt-5">
            Neue Termine anlegen
            <template v-if="current_organisation">- {{ current_organisation.name }}</template>
        </h2>
        <div class="flex flex-wrap mt-4 gap-4 pr-3">
            <div class="">
                <RouterLink :to="{ name: 'singleEvents.create' }">
                    <Button
                        title="Einzeltermin anlegen"
                        aria-label="Einzeltermin anlegen"
                        type="primary"
                        icon-left="add"
                        icon-size="lg"
                    >
                        Einzeltermin
                    </Button>
                </RouterLink>
            </div>
            <div class="">
                <RouterLink :to="{ name: 'seriesEvents.create' }">
                    <Button
                        title="Serientermin anlegen"
                        aria-label="Serientermin anlegen"
                        type="primary"
                        icon-left="add"
                        icon-size="lg"
                    >
                        Serientermin
                    </Button>
                </RouterLink>
            </div>
            <div class="">
                <RouterLink :to="{ name: 'uploadedEvents.create' }">
                    <Button
                        title="Kalenderdatei hochladen"
                        aria-label="Kalenderdatei hochladen"
                        type="primary"
                        icon-left="add"
                        icon-size="lg"
                    >
                        Kalenderdatei
                    </Button>
                </RouterLink>
            </div>
            <div class="">
                <RouterLink :to="{ name: 'importedEvents.create' }">
                    <Button
                        title="Kalenderintegration anlegen"
                        aria-label="Kalenderintegration anlegen"
                        type="primary"
                        icon-left="add"
                        icon-size="lg"
                    >
                        Kalenderintegration
                    </Button>
                </RouterLink>
            </div>
        </div>
    </div>
    <div
        class="mt-6"
        v-if="!loadingOrganisation && current_organisation"
    >
        <h2 class="kern-heading text-theme-primary mt-6">
            Bevorstehende Veranstaltungen
            <template v-if="current_organisation">- {{ current_organisation.name }}</template>
        </h2>
        <div
            class="cards-template"
            v-if="organisation?.organizedEvents?.elements?.length"
        >
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
        <div v-else>
            <h3 class="kern-heading text-theme-primary mt-6 mb-4">Keine bevorstehenden Veranstaltungen</h3>
        </div>
    </div>
</template>
