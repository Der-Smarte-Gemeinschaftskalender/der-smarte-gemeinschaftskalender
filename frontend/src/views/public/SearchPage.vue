<script lang="ts" setup>
import dayjs from '@/lib/dayjs';
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';

import { getBeginsEndsOn } from '@/lib/helper';
import { searchEvents } from '@/lib/mobilizonClient';
import { mobilizon_category_options, mobilizon_event_status, mobilizon_event_language_options } from '@/lib/const';

import Card from '@/components/KERN/Card.vue';
import EventCardHorizontal from '@/components/EventCardHorizontal.vue';
import KernAccordion from '@/components/KERN/Accordion.vue';
import InputRadio from '@/components/KERN/inputs/InputRadio.vue';
import InputCheckbox from '@/components/KERN/inputs/InputCheckbox.vue';
import InputText from '@/components/KERN/inputs/InputText.vue';
import InputLocation from '@/components/KERN/inputs/InputLocation.vue';
import Button from '@/components/KERN/Button.vue';
import EventCard from "@/components/EventCard.vue";
import Overlayable from "@/components/Overlayable.vue";
import ShareLinks from '@/components/ShareLinks.vue';
import Icon from '@/components/KERN/cosmetics/Icon.vue';
import Loader from "@/components/KERN/cosmetics/Loader.vue";

import { type Accordion } from '@/types/KERN';


const route = useRoute();

const searchTerm = ref((route.query.tag as string) || localStorage.getItem('searchTerm') || '');
const searchTarget = ref(localStorage.getItem('searchTarget') || import.meta.env.VITE_SEARCH_TARGET);
const searchBegin = ref(localStorage.getItem('searchBegin') || 'all');
const searchCategory = ref<string[]>((route.query.category as string) || JSON.parse(localStorage.getItem('searchCategory') || '[]') || []);
const searchStatus = ref<string[]>(JSON.parse(localStorage.getItem('searchStatus') || '["CONFIRMED"]'));
const searchLanguage = ref<string[]>(JSON.parse(localStorage.getItem('searchLanguage') || '[]'));
const searchAddress = ref((route.query.address as string) || localStorage.getItem('searchAddress') || import.meta.env.VITE_SEARCH_LOCATION_ADDRESS || '');
const searchRadius = ref((route.query.radius as string) || parseInt(localStorage.getItem('searchRadius') || '10', 10));

const locationSearchRef = ref<InstanceType<typeof InputLocation> | null>(null);
const locationSearchGeoHash = computed(() => (searchAddress.value != '' ? import.meta.env.VITE_SEARCH_LOCATION_GEO_HASH : null) || locationSearchRef.value?.geoHash);
const overlayable = ref<InstanceType<typeof Overlayable> | null>(null);
const loading = ref(false);

const searchBeginsEndsOn = computed(() => {
    let beginsOn, endsOn;
    switch (searchBegin.value) {
        case 'all':
            beginsOn = dayjs().startOf('day');
            endsOn = null;
            break;
        case 'today':
            beginsOn = dayjs().startOf('day');
            endsOn = dayjs().endOf('day');
            break;
        case 'tomorrow':
            beginsOn = dayjs().add(1, 'day').startOf('day');
            endsOn = dayjs().add(1, 'day').endOf('day');
            break;
        case 'thisWeek':
            beginsOn = dayjs().startOf('week');
            endsOn = dayjs().endOf('week');
            break;
        case 'thisWeekend':
            beginsOn = dayjs().day(5).startOf('day');
            endsOn = dayjs().endOf('week');
            break;
        case 'nextWeek':
            beginsOn = dayjs().add(1, 'week').startOf('week');
            endsOn = dayjs().add(1, 'week').endOf('week');
            break;
        case 'thisMonth':
            beginsOn = dayjs().startOf('month');
            endsOn = dayjs().endOf('month');
            break;
        case 'nextMonth':
            beginsOn = dayjs().add(1, 'month').startOf('month');
            endsOn = dayjs().add(1, 'month').endOf('month');
            break;
    }
    return { beginsOn, endsOn };
});

const events = ref([]);

const searchAccordions: Accordion[] = [
    {
        header: 'Instanz',
    },
    {
        header: 'Zeitraum',
        open: true,
    },
    {
        header: 'Kategorien',
    },
    {
        header: 'Status der Veranstaltung',
    },
    {
        header: 'Sprachen',
    },
];

const loadSearchEvents = async () => {
    try {
        loading.value = true;
        let beginsOn, endsOn;
        const { beginsOn: newBeginsOn, endsOn: newEndsOn } = getBeginsEndsOn(searchBegin.value);
        beginsOn = newBeginsOn;
        endsOn = newEndsOn;

        const result = await searchEvents(
            1,
            50,
            searchTerm.value,
            searchTarget.value,
            beginsOn?.toISOString(),
            endsOn?.toISOString(),
            searchCategory.value,
            searchStatus.value,
            searchLanguage.value,
            locationSearchGeoHash.value,
            searchRadius.value
        );

        overlayable.value?.closeOverlay();
        events.value = result.elements;
        localStorage.setItem('searchTerm', searchTerm.value);
        localStorage.setItem('searchTarget', searchTarget.value);
        localStorage.setItem('searchBegin', searchBegin.value);
        localStorage.setItem('searchCategory', JSON.stringify(searchCategory.value));
        localStorage.setItem('searchStatus', JSON.stringify(searchStatus.value));
        localStorage.setItem('searchLanguage', JSON.stringify(searchLanguage.value));

    } catch (error) {
        console.error('Error loading events:', error);
    }

    loading.value = false;
};

const resetSearch = () => {
    searchAddress.value = import.meta.env.VITE_SEARCH_LOCATION_ADDRESS;
    searchRadius.value = 10;
    searchTarget.value = import.meta.env.VITE_SEARCH_TARGET;
    searchTerm.value = '';
    searchBegin.value = 'all';
    searchCategory.value = [];
    searchStatus.value = ['CONFIRMED'];
    searchLanguage.value = [];
    loadSearchEvents();
};

loadSearchEvents();

</script>
<template>
    <Teleport to="#headerslot">
        <div class="mb-3 mt-4 sm:mb-4 sm:mt-5 md:my-6">
            <h1 class="kern-heading text-theme-primary">Veranstaltungen finden</h1>
            <h2 class="kern-heading text-theme-primary font-semilight">
                Öffentliche Termine von Vereinen, Organisationen & der Gemeinde
            </h2>
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
                class="aside overflow-x-hidden whitespace-normal break-words p-3 "
                body-class="px-0 w-full"
            >
                <Button
                    @click="loadSearchEvents()"
                    class="lg:hidden ml-auto"
                    variant="secondary"
                    icon-left="search"
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
            <div>
                <!-- Zweite Suche -->
                <div class="flex flex-column w-full m-0 justify-content-between align-content-center gap-3">
                    <div class="flex flex-column md:flex-row align-items-center gap-4 pt-1">
                        <InputText
                            v-model="searchTerm"
                            placeholder="Schlagwort, Veranstaltungstitel, Organisation,..."
                            name="searchTerm"
                            class="col"
                        />

                        <InputLocation
                            ref="locationSearchRef"
                            v-model:address="searchAddress"
                            v-model:radius="searchRadius"
                            class="mb-5 w-full md:w-auto"
                        />
                    </div>
                    <div class="flex gap-3">
                        <Button
                            @click="overlayable?.openOverlay()"
                            variant="secondary"
                            icon-left="tune"
                            class="lg:hidden"
                        >
                            Filter
                        </Button>
                        <Button
                            @click="loadSearchEvents()"
                            variant="secondary"
                            icon-left="search"
                        >
                            Suchen
                        </Button>
                    </div>
                </div>
            </div>

            <div>
                <template v-if="!events.length">
                    <div class="kern-row">
                        <div class="kern-col">
                            <h4 class="kern-heading font-medium my-4 text-theme-primary">
                                Keine Veranstaltungen gefunden
                            </h4>
                            <p>
                                Versuchen Sie es mit einem anderen Suchbegriff oder passen Sie die Filter an.
                            </p>
                            <Button
                                @click="resetSearch()"
                                variant="secondary"
                                icon-left="autorenew"
                                class="mt-4"
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
                                {{ events.length }} Veranstaltungen gefunden
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
                                    :eventList="events"
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
                                                          locationSearchGeoHash,
                                                          searchRadius,
                                                          beginsOn: searchBeginsEndsOn.beginsOn?.toISOString(),
                                                          endsOn: searchBeginsEndsOn.endsOn?.toISOString(),
                                                      },
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
                    <div class="w-full">
                        <template
                            v-for="event in events"
                        >
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

