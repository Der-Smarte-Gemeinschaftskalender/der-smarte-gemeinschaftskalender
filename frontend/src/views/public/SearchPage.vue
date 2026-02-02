<script lang="ts" setup>
import dayjs from '@/lib/dayjs';
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useRouter } from 'vue-router';

import { searchEvents } from '@/lib/mobilizonClient';
import { mobilizon_category_options, mobilizon_event_status, mobilizon_event_language_options } from '@/lib/const';

import Card from '@/components/KERN/Card.vue';
import EventCardHorizontal from '@/components/EventCardHorizontal.vue';
import EventsMap from '@/components/EventsMap.vue';
import KernAccordion from '@/components/KERN/Accordion.vue';
import InputRadio from '@/components/KERN/inputs/InputRadio.vue';
import InputCheckbox from '@/components/KERN/inputs/InputCheckbox.vue';
import InputText from '@/components/KERN/inputs/InputText.vue';
import InputLocation from '@/components/KERN/inputs/InputLocation.vue';
import Button from '@/components/KERN/Button.vue';
import EventCard from '@/components/EventCard.vue';
import Overlayable from '@/components/Overlayable.vue';
import ShareLinks from '@/components/ShareLinks.vue';
import Icon from '@/components/KERN/cosmetics/Icon.vue';
import Loader from '@/components/KERN/cosmetics/Loader.vue';
import { searchPage, searchDefaults } from '@/lib/instanceConfig';

import { type Accordion } from '@/types/KERN';

const route = useRoute();
const router = useRouter();

const maxResultsPerPage = 25;

const searchTerm = ref((route.query.tag as string) || localStorage.getItem('searchTerm') || '');
const searchTarget = ref(localStorage.getItem('searchTarget') || searchDefaults.target);

const searchBegin = ref(localStorage.getItem('searchBegin') || 'all');

const searchBeginsEndsOn = computed(() => {
    const timeRanges: Record<string, { beginsOn: any; endsOn: any }> = {
        all: { beginsOn: dayjs().startOf('day'), endsOn: null },
        today: { beginsOn: dayjs().startOf('day'), endsOn: dayjs().endOf('day') },
        tomorrow: { beginsOn: dayjs().add(1, 'day').startOf('day'), endsOn: dayjs().add(1, 'day').endOf('day') },
        thisWeek: { beginsOn: dayjs().startOf('week'), endsOn: dayjs().endOf('week') },
        thisWeekend: { beginsOn: dayjs().day(5).startOf('day'), endsOn: dayjs().endOf('week') },
        nextWeek: { beginsOn: dayjs().add(1, 'week').startOf('week'), endsOn: dayjs().add(1, 'week').endOf('week') },
        thisMonth: { beginsOn: dayjs().startOf('month'), endsOn: dayjs().endOf('month') },
        nextMonth: {
            beginsOn: dayjs().add(1, 'month').startOf('month'),
            endsOn: dayjs().add(1, 'month').endOf('month'),
        },
    };
    return timeRanges[searchBegin.value] || timeRanges['all'];
});

const searchCategory = ref<string[]>(
    (route.query.category as string) || JSON.parse(localStorage.getItem('searchCategory') || '[]') || []
);
const searchStatus = ref<string[]>(JSON.parse(localStorage.getItem('searchStatus') || '["CONFIRMED"]'));
const searchLanguage = ref<string[]>(JSON.parse(localStorage.getItem('searchLanguage') || '[]'));

const locationSearchRef = ref<InstanceType<typeof InputLocation> | null>(null);
const useDefaultLocationAddress = ref(
    localStorage.getItem('searchAddress') === null ||
        localStorage.getItem('searchAddress') === searchDefaults.locationAddress
);

const searchAddress = ref(
    (route.query.address as string) ??
        (useDefaultLocationAddress.value ? searchDefaults.locationAddress : localStorage.getItem('searchAddress') || '')
);

const searchRadius = ref(
    parseInt(route.query.radius as string) ||
        (localStorage.getItem('searchRadius')
            ? parseInt(localStorage.getItem('searchRadius') as string)
            : searchDefaults.searchRadius || 10)
);

const searchGeoHash = ref('');

const getLocationGeoHash = (): string => {
    // 1. If user has entered a new location via the input field
    if (locationSearchRef.value?.geoHash !== undefined) {
        useDefaultLocationAddress.value = false;
        return locationSearchRef.value!.geoHash;
    }

    // 2. If using a saved non-default address
    if (!useDefaultLocationAddress.value) {
        return localStorage.getItem('locationSearchGeoHash') ?? searchDefaults.locationGeoHash ?? '';
    }

    // 3. Default location geohash
    return searchDefaults.locationGeoHash || '';
};

const events = ref([]);
const totalEvents = ref(0);
const currentPage = ref(route.query.page ? parseInt(route.query.page as string, 10) : 1);

const totalPages = computed(() => Math.ceil(totalEvents.value / maxResultsPerPage));

const visiblePages = computed(() => {
    const total = totalPages.value;
    const current = currentPage.value;
    const delta = 1;
    const pages: number[] = [];
    const start = Math.max(1, current - delta);
    const end = Math.min(total, current + delta);
    for (let i = start; i <= end; i++) pages.push(i);
    return pages;
});

const overlayable = ref<InstanceType<typeof Overlayable> | null>(null);
const loading = ref(false);
const isMapExpanded = ref(false);

const searchAccordions: Accordion[] = [
    { header: 'Instanz' },
    { header: 'Zeitraum', open: true },
    { header: 'Kategorien' },
    { header: 'Status der Veranstaltung' },
    { header: 'Sprachen' },
];

const saveSearchState = (locationGeoHash: string) => {
    localStorage.setItem('searchTerm', searchTerm.value);
    localStorage.setItem('searchAddress', searchAddress.value);
    localStorage.setItem('locationSearchGeoHash', locationGeoHash);
    localStorage.setItem('searchTarget', searchTarget.value);
    localStorage.setItem('searchBegin', searchBegin.value);
    localStorage.setItem('searchCategory', JSON.stringify(searchCategory.value));
    localStorage.setItem('searchStatus', JSON.stringify(searchStatus.value));
    localStorage.setItem('searchLanguage', JSON.stringify(searchLanguage.value));
};

const clearLocationSearch = () => {
    locationSearchRef.value = null;
    localStorage.removeItem('locationSearchGeoHash');
};

const loadSearchEvents = async (resetPageNumber: boolean = true) => {
    try {
        if (resetPageNumber) {
            changePage(1);
        }

        loading.value = true;

        const { beginsOn, endsOn } = searchBeginsEndsOn.value;
        searchGeoHash.value = getLocationGeoHash();

        // Clear location data ONLY if address is explicitly the default
        if (searchAddress.value === searchDefaults.locationAddress || !searchAddress.value) {
            clearLocationSearch();
        }

        const result = await searchEvents(
            currentPage.value,
            maxResultsPerPage,
            searchTerm.value,
            searchTarget.value,
            beginsOn?.toISOString(),
            endsOn?.toISOString(),
            searchCategory.value,
            searchStatus.value,
            searchLanguage.value,
            searchGeoHash.value,
            searchRadius.value
        );

        overlayable.value?.closeOverlay();
        events.value = result.searchEvents.elements;
        totalEvents.value = result.searchEvents.total;

        saveSearchState(searchGeoHash.value);
    } catch (error) {
        console.error('Error loading events:', error);
    } finally {
        loading.value = false;
    }
};

const changePage = (newPage: number) => {
    currentPage.value = newPage;
    router.push({
        query: {
            ...route.query,
            page: newPage.toString(),
        },
    });
};

watch(searchRadius, (newRadius) => {
    localStorage.setItem('searchRadius', newRadius.toString());
});

const resetSearch = () => {
    searchTerm.value = '';
    searchTarget.value = searchDefaults.target;
    searchBegin.value = 'all';
    searchCategory.value = [];
    searchStatus.value = ['CONFIRMED'];
    searchLanguage.value = [];
    searchAddress.value = searchDefaults.locationAddress;
    searchRadius.value = searchDefaults.searchRadius;

    searchGeoHash.value = searchDefaults.locationGeoHash || '';
    useDefaultLocationAddress.value = true;
    clearLocationSearch();
    loadSearchEvents();
};

if (route.query.page) loadSearchEvents(false);
else changePage(1);
</script>
<template>
    <Teleport to="#headerslot">
        <div class="mb-3 mt-4 sm:mb-4 sm:mt-5 md:my-6">
            <h1 class="kern-heading text-theme-primary">Veranstaltungen finden</h1>
            <h2
                v-if="searchPage.description && searchPage.description !== ''"
                class="kern-heading font-semilight text-theme-primary"
            >
                {{ searchPage.description }}
            </h2>
            <p
                v-if="searchPage.descriptionHtml && searchPage.descriptionHtml !== ''"
                class="kern-text"
                v-html="searchPage.descriptionHtml"
            ></p>
        </div>
    </Teleport>
    <div class="block md:flex justify-content-between lg:gap-6 w-full">
        <Overlayable
            ref="overlayable"
            overlay-max-width="350px"
            panel-class="side-navigation p-0 hidden lg:block"
            toggle-breakpoint="lg"
        >
            <Card
                class="aside overflow-x-hidden whitespace-normal break-words p-3"
                body-class="px-0 w-full"
            >
                <Button
                    class="lg:hidden ml-auto"
                    variant="secondary"
                    icon-left="search"
                    :disabled="locationSearchRef?.triggered"
                    @click="loadSearchEvents()"
                >
                    Suchen
                </Button>
                <KernAccordion
                    :accordions="searchAccordions"
                    class="w-full"
                >
                    <template #content-1>
                        <fieldset class="kern-fieldset">
                            <div class="kern-fieldset__content">
                                <InputRadio
                                    v-model="searchTarget"
                                    label="Dieser Kalender"
                                    name="INTERNAL"
                                    value="INTERNAL"
                                />
                                <InputRadio
                                    v-model="searchTarget"
                                    label="Gesamtes Kalendernetzwerk"
                                    name="GLOBAL"
                                    value="GLOBAL"
                                />
                            </div>
                        </fieldset>
                    </template>
                    <template #content-2>
                        <fieldset class="kern-fieldset">
                            <div class="kern-fieldset__content">
                                <InputRadio
                                    v-model="searchBegin"
                                    label="Alle Veranstaltungen"
                                    name="all"
                                    value="all"
                                />
                                <InputRadio
                                    v-model="searchBegin"
                                    label="Heute"
                                    name="today"
                                    value="today"
                                />
                                <InputRadio
                                    v-model="searchBegin"
                                    label="Morgen"
                                    name="tomorrow"
                                    value="tomorrow"
                                />
                                <InputRadio
                                    v-model="searchBegin"
                                    label="Diese Woche"
                                    name="thisWeek"
                                    value="thisWeek"
                                />
                                <InputRadio
                                    v-model="searchBegin"
                                    label="Dieses Wochenende"
                                    name="thisWeekend"
                                    value="thisWeekend"
                                />
                                <InputRadio
                                    v-model="searchBegin"
                                    label="Nächste Woche"
                                    name="nextWeek"
                                    value="nextWeek"
                                />
                                <InputRadio
                                    v-model="searchBegin"
                                    label="Diesen Monat"
                                    name="thisMonth"
                                    value="thisMonth"
                                />
                                <InputRadio
                                    v-model="searchBegin"
                                    label="Nächster Monat"
                                    name="nextMonth"
                                    value="nextMonth"
                                />
                            </div>
                        </fieldset>
                    </template>
                    <template #content-3>
                        <fieldset class="kern-fieldset">
                            <div class="kern-fieldset__content">
                                <InputCheckbox
                                    v-for="(option, index) in mobilizon_category_options"
                                    :key="index"
                                    v-model="searchCategory"
                                    :label="option.text"
                                    :name="option.value"
                                    :value="option.value"
                                />
                            </div>
                        </fieldset>
                    </template>
                    <template #content-4>
                        <fieldset class="kern-fieldset">
                            <div class="kern-fieldset__content">
                                <InputCheckbox
                                    v-for="(option, index) in mobilizon_event_status"
                                    :key="index"
                                    v-model="searchStatus"
                                    :label="option.text"
                                    :name="option.value"
                                    :value="option.value"
                                />
                            </div>
                        </fieldset>
                    </template>
                    <template #content-5>
                        <fieldset class="kern-fieldset">
                            <div class="kern-fieldset__content">
                                <InputCheckbox
                                    v-for="(option, index) in mobilizon_event_language_options"
                                    :key="index"
                                    v-model="searchLanguage"
                                    :label="option.text"
                                    :name="option.value"
                                    :value="option.value"
                                />
                            </div>
                        </fieldset>
                    </template>
                </KernAccordion>
            </Card>
        </Overlayable>
        <div
            v-if="!loading"
            class="w-full"
        >
            <div class="second-search-bar">
                <!-- Zweite Suche -->
                <div class="flex flex-column w-full m-0 justify-content-between align-content-center gap-3">
                    <div class="flex flex-column md:flex-row align-items-center gap-4 pt-4 md:pt-1">
                        <InputText
                            v-model="searchTerm"
                            placeholder="Schlagwort, Veranstaltungstitel..."
                            name="searchTerm"
                            class="col"
                            aria-label="Suchbegriff: Schlagwort, Veranstaltungstitel"
                        />

                        <InputLocation
                            ref="locationSearchRef"
                            v-model:address="searchAddress"
                            v-model:radius="searchRadius"
                            :search-initially="true"
                            class="mb-5 w-full md:w-auto"
                        />
                    </div>
                    <div class="flex gap-3">
                        <Button
                            variant="secondary"
                            icon-left="tune"
                            class="lg:hidden"
                            @click="overlayable?.openOverlay()"
                        >
                            Filter
                        </Button>
                        <Button
                            variant="secondary"
                            icon-left="search"
                            :disabled="locationSearchRef?.triggered"
                            @click="loadSearchEvents()"
                        >
                            Suchen
                        </Button>
                    </div>
                </div>
            </div>

            <div>
                <template v-if="!totalEvents">
                    <div class="kern-row">
                        <div class="kern-col">
                            <h4 class="kern-heading font-medium my-4 text-theme-primary">
                                Keine Veranstaltungen gefunden
                            </h4>
                            <p>Versuchen Sie es mit einem anderen Suchbegriff oder passen Sie die Filter an.</p>
                            <Button
                                variant="secondary"
                                icon-left="autorenew"
                                class="mt-4"
                                @click="resetSearch()"
                            >
                                Suchfilter zurücksetzen
                            </Button>
                        </div>
                    </div>
                </template>
                <template v-else>
                    <div class="kern-row mb-5">
                        <div class="kern-col-lg-6">
                            <h4 class="kern-heading font-medium my-4 text-theme-primary">
                                {{ totalEvents }} Veranstaltungen gefunden
                            </h4>
                        </div>
                        <div class="kern-col-lg-6">
                            <div class="kern-text kern-text--bold text-theme-primary text-right mt-4">
                                Suchergebnis teilen
                            </div>
                            <div class="flex justify-content-end">
                                <ShareLinks
                                    v-if="events.length"
                                    :type="'eventList'"
                                    :event-list="events"
                                    link-to-url="/search"
                                >
                                    <RouterLink
                                        :to="{
                                            name: 'public.infomonitor',
                                            query: {
                                                searchTerm,
                                                searchTarget,
                                                searchBegin,
                                                searchCategory,
                                                searchStatus,
                                                searchLanguage,
                                                locationSearchGeoHash: '', //TODOlocationSearchGeoHash: getLocationGeoHash(),
                                                searchRadius,
                                                beginsOn: searchBeginsEndsOn.beginsOn?.toISOString(),
                                                endsOn: searchBeginsEndsOn.endsOn?.toISOString(),
                                            },
                                        }"
                                        aria-label="Zur Infomonitor Anzeige der Organisation"
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

                    <!-- Map Accordion -->
                    <details
                        class="kern-accordion mb-5"
                        :open="isMapExpanded"
                        @toggle="isMapExpanded = ($event.target as HTMLDetailsElement).open"
                    >
                        <summary class="kern-accordion__header">
                            <span class="flex gap-2 align-items-center">Karte</span>
                        </summary>
                        <section class="kern-accordion__content">
                            <EventsMap
                                v-if="isMapExpanded"
                                :events="events"
                            />
                        </section>
                    </details>

                    <div class="w-full">
                        <template v-for="event in events">
                            <EventCardHorizontal
                                :event="event"
                                class="mb-5 hidden md:flex lg:hidden xl:flex"
                            />
                            <EventCard
                                :event="event"
                                class="mb-5 flex md:hidden lg:flex xl:hidden"
                            />
                        </template>
                    </div>

                    <template v-if="totalPages > 1">
                        <div class="flex justify-content-center align-items-center gap-2 my-6">
                            <Button
                                variant="secondary"
                                :hide-text-on-mobile="true"
                                :disabled="currentPage === 1"
                                icon-left="keyboard-double-arrow-left"
                                class="px-2 sm:px-3 mx-1 hidden sm:flex"
                                @click="changePage(currentPage - 1)"
                            >
                                Zurück
                            </Button>
                            <Button
                                v-if="visiblePages[0]! > 1"
                                variant="secondary"
                                class="px-2 sm:px-3"
                                @click="changePage(1)"
                            >
                                1
                            </Button>
                            <span
                                v-if="visiblePages[0]! > 2"
                                class="text-theme-primary mb-2"
                                style="line-height: 1px"
                            >
                                …
                            </span>
                            <Button
                                v-for="page in visiblePages"
                                :key="page"
                                :variant="page === currentPage ? 'primary' : 'secondary'"
                                class="px-2 sm:px-3"
                                @click="changePage(page)"
                            >
                                {{ page }}
                            </Button>
                            <span
                                v-if="visiblePages[visiblePages.length - 1]! < totalPages - 1"
                                class="text-theme-primary mb-2"
                                style="line-height: 1px"
                            >
                                …
                            </span>
                            <Button
                                v-if="visiblePages[visiblePages.length - 1]! < totalPages"
                                class="px-2 sm:px-3"
                                variant="secondary"
                                @click="changePage(totalPages)"
                            >
                                {{ totalPages }}
                            </Button>
                            <Button
                                variant="secondary"
                                :hide-text-on-mobile="true"
                                :disabled="currentPage === totalPages"
                                icon-right="keyboard-double-arrow-right"
                                class="px-2 sm:px-3 mx-1 hidden sm:flex"
                                @click="changePage(currentPage + 1)"
                            >
                                Weiter
                            </Button>
                        </div>
                    </template>
                </template>
            </div>
        </div>
        <div
            v-else
            class="flex justify-content-center align-items-center w-full"
        >
            <Loader size="large" />
        </div>
    </div>
</template>
<style lang="scss" scoped>
.aside {
    min-width: 250px;
    max-width: 279px !important;
}
@media screen and (max-width: 991px) {
    .aside {
        width: 80% !important;
        border: none !important;
    }
}
</style>
