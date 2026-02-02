<script lang="ts" setup>
import { ref, watch, reactive } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

import { truncate, getMainCategoryFromSubCategory, formatOnTime } from '@/lib/helper';
import { searchEvents } from '@/lib/mobilizonClient';
import { mobilizon_main_category_options, mobilizon_event_status, mobilizon_event_language_options } from '@/lib/const';
import deLocale from '@fullcalendar/core/locales/de';

import KernAccordion from '@/components/KERN/Accordion.vue';
import InputRadio from '@/components/KERN/inputs/InputRadio.vue';
import InputCheckbox from '@/components/KERN/inputs/InputCheckbox.vue';
import InputText from '@/components/KERN/inputs/InputText.vue';
import Card from '@/components/KERN/Card.vue';
import Icon from '@/components/KERN/cosmetics/Icon.vue';

import Loader from '@/components/KERN/cosmetics/Loader.vue';
import Button from '@/components/KERN/Button.vue';
import Overlayable from '@/components/Overlayable.vue';

import { type Accordion } from '@/types/KERN';
import { type IEvent, MobilizonCategory } from '@/types/General';
import type { CalendarOptions, EventInput, EventSourceFuncArg } from '@fullcalendar/core';
import { searchDefaults } from '@/lib/instanceConfig';

const searchCategory = ref<string[]>([]);
const searchTarget = ref(localStorage.getItem('searchTarget') || searchDefaults.target);
const searchTerm = ref('');
const searchMainCategory = ref<string[]>(JSON.parse(localStorage.getItem('searchMainCategory') || '[]'));
const searchStatus = ref<string[]>(JSON.parse(localStorage.getItem('searchStatus') || '["CONFIRMED"]'));
const searchLanguage = ref<string[]>(JSON.parse(localStorage.getItem('searchLanguage') || '[]'));
let debounceTimeout: ReturnType<typeof setTimeout> | null = null;

const loading = ref(false);
const overlayable = ref<InstanceType<typeof Overlayable> | null>(null);
const fullCalendar = ref<InstanceType<typeof FullCalendar> | null>(null);
let calApi = null;

const searchAccordions: Accordion[] = [
    {
        header: 'Suchbegriff',
        open: true,
    },
    {
        header: 'Kategorien',
        open: true,
    },
    {
        header: 'Instanz',
    },
    {
        header: 'Status der Veranstaltung',
    },
    {
        header: 'Sprachen',
    },
];

const calendarOptions = reactive<CalendarOptions>({
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridWeek',
    events: async (
        fetchInfo: EventSourceFuncArg,
        successCallback: (events: EventInput[]) => void,
        failureCallback: (error: Error) => void
    ) => {
        const result = await searchEvents(
            1,
            200,
            searchTerm.value,
            searchTarget.value,
            fetchInfo.start.toISOString(),
            fetchInfo.end.toISOString(),
            searchCategory.value,
            searchStatus.value,
            searchLanguage.value
        );
        localStorage.setItem('searchTarget', searchTarget.value);
        localStorage.setItem('searchMainCategory', JSON.stringify(searchMainCategory.value));
        localStorage.setItem('searchStatus', JSON.stringify(searchStatus.value));
        localStorage.setItem('searchLanguage', JSON.stringify(searchLanguage.value));

        if (!result) {
            failureCallback(new Error('failed to fetch calendar events'));
            return;
        }

        successCallback(
            (result.searchEvents.elements ?? []).map((event: IEvent) => {
                const mainCategory = getMainCategoryFromSubCategory(event.category);
                return {
                    id: event.uuid,
                    title: truncate(event.title, 30),
                    start: event.beginsOn,
                    end: event.endsOn,
                    startStr: event.beginsOn,
                    endStr: event.endsOn,
                    url: `/events/${event.uuid}`,
                    extendedProps: {
                        event: event,
                    },
                    mainCategoryColor: mainCategory?.color || '#AD0477',
                };
            })
        );
    },
    nextDayThreshold: '09:00:00',
    dayMaxEventRows: 10,
    moreLinkContent: (arg: { num: number; text: string }) => {
        return '+' + arg.num.toString();
    },
    eventClassNames: 'kern-link',
    headerToolbar: {
        left: 'prev,next,today',
        center: 'title',
        right: 'dayGridWeek,dayGridMonth',
    },
    firstDay: 1,
    locale: deLocale,
    buttonText: {
        today: 'Heute',
        month: 'Monat',
        week: 'Woche',
        day: 'Tag',
        list: 'Liste',
    },
});

const fullCalendarRefetchEvents = () => {
    if (!fullCalendar?.value) return;

    calApi = fullCalendar.value.getApi()!;
    calApi.refetchEvents();
};

watch(searchMainCategory, (newSearchMainCategory: MobilizonCategory[]) => {
    searchCategory.value = newSearchMainCategory
        .map((category) => {
            return mobilizon_main_category_options.find((option) => option.value === category)?.sub_categories || [];
        })
        .flat();
    fullCalendarRefetchEvents();
});

watch(searchTarget, fullCalendarRefetchEvents);
watch(searchStatus, fullCalendarRefetchEvents);
watch(searchLanguage, fullCalendarRefetchEvents);
watch(searchTerm, async (newSearchTerm) => {
    if (debounceTimeout) clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(async () => {
        fullCalendarRefetchEvents();
    }, 1000);
});
</script>
<template>
    <Teleport to="#headerslot">
        <div class="mb-3 mt-4 sm:mb-4 sm:mt-5 md:my-6 flex justify-content-between align-items-center">
            <h1 class="kern-heading text-theme-primary">Kalender</h1>
            <Button
                variant="secondary"
                icon-left="tune"
                class="xl:hidden w-min"
                aria-label="Filter"
                title="Filter"
                @click="overlayable?.openOverlay()"
            >
                Filter
            </Button>
        </div>
    </Teleport>

    <div class="flex lg:gap-6 xl:gap-8 flex-row w-full">
        <Overlayable
            ref="overlayable"
            overlay-max-width="400px"
            panel-class="side-navigation p-0 hidden lg:block"
            toggle-breakpoint="xl"
        >
            <Card
                class="aside overflow-x-hidden white-space-nowrap text-overflow-ellipsis p-3"
                body-class="px-0"
            >
                <KernAccordion
                    :accordions="searchAccordions"
                    class="w-full"
                >
                    <template #content-1>
                        <fieldset class="kern-fieldset pr-1">
                            <div class="search kern-fieldset__content">
                                <InputText
                                    v-model="searchTerm"
                                    placeholder="Schlagwort, Veranstaltungstitel..."
                                    name="searchTerm"
                                />
                            </div>
                        </fieldset>
                    </template>
                    <template #content-2>
                        <fieldset class="kern-fieldset">
                            <div class="kern-fieldset__content">
                                <template
                                    v-for="(option, index) in mobilizon_main_category_options"
                                    :key="index"
                                >
                                    <div
                                        :style="{ backgroundColor: option.color }"
                                        class="main-category-option"
                                    >
                                        <InputCheckbox
                                            v-model="searchMainCategory"
                                            :label="option.text"
                                            :name="option.value"
                                            :value="option.value"
                                        />
                                    </div>
                                </template>
                            </div>
                        </fieldset>
                    </template>
                    <template #content-3>
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
        <div class="calendar-wrapper flex-grow-1 flex flex-column">
            <FullCalendar
                v-if="!loading"
                ref="fullCalendar"
                class="calendar"
                :options="calendarOptions"
            >
                <template #eventContent="arg">
                    <div
                        class="fc-content kern-text kern-text--small"
                        :title="arg.event.title"
                        :style="{ backgroundColor: arg.event.extendedProps?.mainCategoryColor, color: 'white' }"
                    >
                        <span class="kern-text--bold">
                            {{ arg.event.title }}
                        </span>
                        <br />

                        <Icon
                            name="person"
                            color="white"
                        />
                        {{ arg.event.extendedProps.event?.attributedTo?.name }}
                        <br />

                        <Icon
                            name="schedule"
                            color="white"
                        />
                        {{ formatOnTime(arg.event.extendedProps?.event?.beginsOn) }}
                    </div>
                </template>
            </FullCalendar>
            <Loader
                v-else
                class="flex justify-content-center"
                :size="50"
            />
        </div>
    </div>
</template>

<style lang="scss">
.search.kern-fieldset__content * {
    box-sizing: border-box;
    max-width: 100%;
}

/* ===== FullCalendar Buttons ===== */
.fc-button {
    background-color: var(--kern-theme-color) !important;
    padding: 0.25rem 0.5rem !important;
    border: none;
    box-shadow: none;
}

@media (min-width: 768px) {
    .fc-button {
        padding: 0.35rem 0.75rem !important;
    }
}

@media (min-width: 1024px) {
    .fc-button {
        padding: 0.5rem 1rem !important;
    }
}

/* ===== Header Styling ===== */
.fc-col-header-cell {
    background-color: var(--kern-theme-color) !important;
    color: white !important;
}

/* ===== Scrollable Grid ===== */
.fc-view-harness {
    min-height: 400px;
    overflow-x: auto;
    overflow-y: auto;
}

.fc-view-harness .fc-scrollgrid {
    min-width: 1000px;
    min-height: 1000px;
}

/* ===== Day & Event Styling ===== */
.fc-day .fc-content {
    width: 100%;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
    font-size: 0.9rem;
    color: var(--kern-theme-color);
    line-height: 1.2;
    padding: 0.25rem;
    border-radius: 4px;
}

.fc-day .fc-event,
.fc-day .fc-event-main,
.fc-day .fc-daygrid-event-harness {
    width: calc(100% - 0.125rem);
    margin-right: 0;
    overflow: hidden;
    font-size: 0.85rem;
    padding: 0;
}

.fc-event-main {
    width: 100%;
}

.fc-day .fc-event:hover,
.fc-day .fc-event-main:hover,
.fc-day .fc-daygrid-event-harness:hover {
    width: auto;
    min-width: calc(100% - 0.125rem);
    overflow: visible;
    z-index: 10;
}

.fc-event-main:hover {
    width: auto;
    min-width: calc(100% - 0.125rem);
}

.fc-day .fc-content:hover {
    width: auto;
    min-width: calc(100% - 0.125rem);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.5);
}

.fc-day .fc-h-event {
    background-color: transparent;
    border: none;
}

.fc-day .fc-event {
    margin-left: 2px !important;
}

.fc-day-today {
    background-color: #7dcce833 !important;
    color: black !important;
}

/* ===== Toolbar Title Font Sizes ===== */
.fc-toolbar-title {
    font-size: 18px !important;
    margin: 0 0.25rem !important;
    text-align: center;
}

@media (min-width: 640px) {
    .fc-toolbar-title {
        margin: 0 0.5rem !important;
        font-size: 22px !important;
    }
}

@media (min-width: 768px) {
    .fc-toolbar-title {
        margin: 0 1rem !important;
        font-size: 26px !important;
    }
}

@media (min-width: 1024px) {
    .fc-toolbar-title {
        margin: 0 2rem !important;
        font-size: 30px !important;
    }
}

@media (min-width: 1280px) {
    .fc-toolbar-title {
        font-size: 36px !important;
    }
}

/* ===== Responsive Adjustments ===== */
@media screen and (max-width: 1199px) {
    .fc-header-toolbar {
        width: 80vw !important;
        margin: 0 auto;
    }
}

@media screen and (max-width: 1000px) {
    .aside {
        width: 80% !important;
        border: none !important;
    }
}

@media screen and (max-width: 450px) {
    .fc-view-harness {
        min-height: 50vh !important;
    }
    .fc-header-toolbar {
        width: 100% !important;
    }
    .fc-header-toolbar .fc-button-group:nth-child(1) button:nth-child(3) {
        display: none !important;
    }
    .fc-header-toolbar .fc-button-group:nth-child(1) button:nth-child(2) {
        border-top-right-radius: 4px !important;
        border-bottom-right-radius: 4px !important;
    }
}

/* ===== Main Category Option Styling ===== */
.main-category-option {
    border-radius: 4px;
    height: 40px;
    padding: 4px 8px;
}

.main-category-option label {
    color: white !important;
}

@media screen and (max-width: 1199px) {
    .aside {
        width: 80% !important;
        border: none !important;
    }
}
</style>
