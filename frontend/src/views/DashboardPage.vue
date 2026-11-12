<script setup lang="ts">
import { ref } from 'vue';
import { I18nT } from 'vue-i18n';
import { findMobilizonStatistics, findOrganisation } from '@/lib/mobilizonClient';
import { getEventsStatistics } from '@/lib/dsgClient';
import { loadMobilizionGroups } from '@/composables/EventCreateFormComposable';
import { user_organisations } from '@/composables/OrganisationComposable';
import { useRouter } from 'vue-router';
import type { MobilizonStatistics, EventsStatistics, Option } from '@/types/General';
import { current_organisation } from '@/composables/OrganisationComposable';
import Button from '@/components/KERN/Button.vue';
import Card from '@/components/KERN/Card.vue';
import EventCard from '@/components/EventCard.vue';

import LinkToDocs from '@/components/LinkToDocs.vue';
import { allowedEventCreationsMethods } from '@/lib/instanceConfig';

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
</script>
<template>
    <h1 class="kern-heading text-theme-primary">
        {{ $t('app.dashboard.title') }}
        <template v-if="current_organisation">{{ current_organisation.name }}</template>
    </h1>
    <template v-if="!user_organisations?.length">
        <I18nT scope="global"
            keypath="app.dashboard.noMembership"
            tag="p"
            class="kern-text"
        >
            <template #link>
                <RouterLink :to="{ name: 'app.myOrganisations.request' }">{{ $t('app.dashboard.noMembershipLink') }}</RouterLink>
            </template>
        </I18nT>
    </template>
    <template v-else>
        <p class="kern-text">
            <b>{{ $t('app.dashboard.welcomeTitle') }}</b>
            {{ $t('app.dashboard.welcomeText') }}
        </p>
    </template>
    <p class="kern-text mt-2">
        <b>{{ $t('app.shared.hint') }}</b> <I18nT scope="global" keypath="app.dashboard.docsHint">
            <template #link>
                <LinkToDocs path="" />
            </template>
        </I18nT>
    </p>

    <div v-if="!loading">
        <h2 class="kern-heading text-theme-primary mt-6">{{ $t('app.dashboard.instanceOverview') }}</h2>
        <div class="flex flex-wrap gap-4 mt-5">
            <Card
                :title="$t('app.dashboard.users')"
                class="w-fit"
            >
                <span class="text-theme-primary kern-text kern-text--large kern-text--bold">
                    {{ statistic.numberOfUsers }}
                </span>
            </Card>
            <Card
                :title="$t('app.dashboard.events')"
                class="w-fit"
            >
                <span class="text-theme-primary kern-text kern-text--large kern-text--bold">
                    {{ statistic.numberOfLocalEvents }}
                </span>
            </Card>
            <Card
                :title="$t('app.dashboard.organisations')"
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
            {{ $t('app.dashboard.createNewEvents') }}
            <template v-if="current_organisation">- {{ current_organisation.name }}</template>
        </h2>
        <div class="flex flex-wrap mt-4 gap-4 pr-3">
            <div class="">
                <RouterLink
                    v-if="allowedEventCreationsMethods.singleEvent"
                    :to="{ name: 'singleEvents.create' }"
                >
                    <Button
                        :title="$t('app.dashboard.createSingleEvent')"
                        :aria-label="$t('app.dashboard.createSingleEvent')"
                        type="primary"
                        icon-left="add"
                        icon-size="lg"
                    >
                        {{ $t('app.dashboard.singleEvent') }}
                    </Button>
                </RouterLink>
            </div>
            <div class="">
                <RouterLink
                    v-if="allowedEventCreationsMethods.seriesEvent"
                    :to="{ name: 'seriesEvents.create' }"
                >
                    <Button
                        :title="$t('app.dashboard.createSeriesEvent')"
                        :aria-label="$t('app.dashboard.createSeriesEvent')"
                        type="primary"
                        icon-left="add"
                        icon-size="lg"
                    >
                        {{ $t('app.dashboard.seriesEvent') }}
                    </Button>
                </RouterLink>
            </div>
            <div class="">
                <RouterLink
                    v-if="allowedEventCreationsMethods.uploadedEvent"
                    :to="{ name: 'uploadedEvents.create' }"
                >
                    <Button
                        :title="$t('app.dashboard.uploadCalendarFile')"
                        :aria-label="$t('app.dashboard.uploadCalendarFile')"
                        type="primary"
                        icon-left="add"
                        icon-size="lg"
                    >
                        {{ $t('app.dashboard.calendarFile') }}
                    </Button>
                </RouterLink>
            </div>
            <div class="">
                <RouterLink
                    v-if="allowedEventCreationsMethods.importedEvent"
                    :to="{ name: 'importedEvents.create' }"
                >
                    <Button
                        :title="$t('app.dashboard.createCalendarIntegration')"
                        :aria-label="$t('app.dashboard.createCalendarIntegration')"
                        type="primary"
                        icon-left="add"
                        icon-size="lg"
                    >
                        {{ $t('app.dashboard.calendarIntegration') }}
                    </Button>
                </RouterLink>
            </div>
        </div>
    </div>
    <div
        v-if="!loadingOrganisation && current_organisation"
        class="mt-6"
    >
        <h2 class="kern-heading text-theme-primary mt-6">
            {{ $t('app.dashboard.upcomingEvents') }}
            <template v-if="current_organisation">- {{ current_organisation.name }}</template>
        </h2>
        <div
            v-if="organisation?.organizedEvents?.elements?.length"
            class="cards-template"
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
            <h3 class="kern-heading text-theme-primary mt-6 mb-4">{{ $t('app.dashboard.noUpcomingEvents') }}</h3>
        </div>
    </div>
</template>
