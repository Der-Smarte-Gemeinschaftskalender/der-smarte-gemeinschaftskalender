<script setup lang="ts">
import { computed, ref } from 'vue';
import { dsgApi } from '@/lib/dsgApi';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { formatDateTime } from '@/lib/helper';
import { getMonthWeekLabel, getWeekDayLabel, intervall_keys } from '@/lib/const';
import { useEventShowDelete } from '@/composables/EventShowDeleteComposable';
import { seriesEventsDaysControlsEnabled } from '@/lib/instanceConfig';

import DescriptionList from '@/components/KERN/DescriptionList.vue';
import CreatedEventsTable from '@/components/CreatedEventsTable.vue';
import Loader from '@/components/KERN/cosmetics/Loader.vue';
import Button from '@/components/KERN/Button.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Alert from '@/components/KERN/Alert.vue';

import { Intervall } from '@/types/General';
import type { SeriesEvent } from '@/types/events/SeriesEvents.ts';
import type { User } from '@/types/User.ts';


interface SeriesEventWithRelations extends SeriesEvent {
    user: User;
}

const route = useRoute();
const router = useRouter();
const { t } = useI18n();

const loading = ref(false);
const seriesEvent = ref<SeriesEventWithRelations>();
const createdEvents = ref();

const formatWeekDay = (day?: number): string => {
    if (day === undefined || day === null) return t('app.shared.notSet');
    return getWeekDayLabel(day) ?? String(day);
};

const formatMonthWeeks = (weeks?: number[]): string => {
    if (!weeks?.length) return t('app.shared.notSet');

    return weeks.map((week) => getMonthWeekLabel(week) ?? String(week)).join(', ');
};

const getRecurrenceDetails = (event: SeriesEventWithRelations): Array<{ name: string; value: unknown }> => {
    if (!seriesEventsDaysControlsEnabled) return [];

    if (event.intervall === Intervall.WEEKLY) {
        return [{ name: t('app.events.columns.weekDay'), value: formatWeekDay(event.weekly_day) }];
    }

    if (event.intervall !== Intervall.MONTHLY) return [];

    if (event.monthly_use_start_date_as_default) {
        return [{ name: t('app.events.columns.useStartDate'), value: t('common.yes') }];
    }

    const details: Array<{ name: string; value: unknown }> = [];
    if (event.monthly_weeks?.length) {
        details.push({ name: t('app.events.columns.weeks'), value: formatMonthWeeks(event.monthly_weeks) });
    }
    if (event.monthly_week_day !== undefined) {
        details.push({ name: t('app.events.columns.weekDay'), value: formatWeekDay(event.monthly_week_day) });
    }
    return details;
};

// computed so the labels follow a language switch. Missing data gets a fallback instead of
// throwing, an error here would leave the page blank.
const descriptionListData = computed<{ name: string; value: unknown }[]>(() => {
    const event = seriesEvent.value;
    if (!event) return [];

    return [
        {
            name: 'ID',
            value: event.id,
        },
        {
            name: t('app.events.columns.title'),
            value: event.name,
        },
        {
            name: t('app.events.columns.eventCount'),
            value: createdEvents.value?.length,
        },
        {
            name: t('app.events.columns.startDate'),
            value: formatDateTime(event.start),
        },
        {
            name: t('app.events.columns.endDate'),
            value: formatDateTime(event.end),
        },
        {
            name: t('app.events.columns.intervall'),
            value: intervall_keys.value[event.intervall?.toLowerCase() as keyof typeof intervall_keys.value] || event.intervall,
        },
        ...getRecurrenceDetails(event),
        {
            name: t('app.events.columns.addedBy'),
            value: event.user?.mobilizon_name || t('common.notSpecified'),
        },
    ];
});

const { deleting, showDeleteDialog, alertMessage, handleDelete } = useEventShowDelete({
    getDeleteUrl: () => `/series-events/${route.params.id}`,
    redirectRouteName: 'seriesEvents.index',
    fallbackErrorText: t('app.events.series.show.deleteError'),
});

const loadSeriesEvent = async () => {
    try {
        loading.value = true;
        const { data } = await dsgApi.get<{seriesEvent: SeriesEventWithRelations}>(`/series-events/${route.params.id}`);

        if (!data || !data.seriesEvent) {
            await router.push({ name: 'notFound' });
            return;
        }    
        
        seriesEvent.value = data.seriesEvent;
        createdEvents.value = seriesEvent.value.created_events;
    } catch (error) {
        console.error(error);
        await router.push({ name: 'notFound' });

    } finally {
        loading.value = false;
    }
};

loadSeriesEvent();
</script>
<template>
    <Alert
        v-if="alertMessage"
        class="mb-4"
        :severity="alertMessage.severity"
        :title="alertMessage.title"
        :content="alertMessage.content"
    />
    <ConfirmDialog
        v-model="showDeleteDialog"
        :title="t('app.events.series.deleteTitle')"
        :description="t('app.events.series.deleteDescription', { name: `${seriesEvent?.name}` })"
        :confirm-text="t('common.delete')"
        @confirm="handleDelete"
    />

    <div class="flex align-items-center justify-content-between mb-1">
        <h1 class="kern-heading text-theme-primary">{{ t('app.events.series.show.title') }}</h1>
        <div class="flex gap-2">
            <template v-if="!!seriesEvent">
                <Button
                    variant="secondary"
                    icon-left="delete"
                    :disabled="deleting"
                    @click="showDeleteDialog = true"
                >
                    {{ deleting ? t('app.shared.deleting') : t('common.delete') }}
                </Button>
            </template>
        </div>
    </div>
    <template v-if="!loading">
        <DescriptionList :data="descriptionListData" />
        <h3 class="kern-heading mt-8 text-theme-primary">{{ t('app.events.createdEvents') }}</h3>
        <CreatedEventsTable 
            :data="createdEvents" 
            type="series_event" 
        />
    </template>
    <Loader v-else />
</template>
