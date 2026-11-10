<script setup lang="ts">
import { ref } from 'vue';
import { dsgApi } from '@/lib/dsgApi';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { formatDateTime } from '@/lib/helper';
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
const descriptionListData = ref<{ name: string; value: unknown }[]>([]);

const weekDayKeys: Record<number, string> = {
    0: 'sunday',
    1: 'monday',
    2: 'tuesday',
    3: 'wednesday',
    4: 'thursday',
    5: 'friday',
    6: 'saturday',
};

const monthWeekKeys: Record<number, string> = {
    1: 'first',
    2: 'second',
    3: 'third',
    4: 'fourth',
    [-1]: 'last',
};

const intervallLabels: Record<string, string> = {
    weekly: t('common.weekly'),
    monthly: t('common.monthly'),
};

const getWeekDayLabel = (day?: number): string => {
    if (day === undefined || day === null) return t('app.shared.notSet');
    return weekDayKeys[day] ? t(`app.shared.weekDays.long.${weekDayKeys[day]}`) : String(day);
};

const getMonthWeeksLabel = (weeks?: number[]): string => {
    if (!weeks?.length) return t('app.shared.notSet');

    return weeks
        .map((week) => (monthWeekKeys[week] ? t(`app.shared.monthOccurrence.weekOptions.${monthWeekKeys[week]}`) : String(week)))
        .join(', ');
};

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

        const recurrenceDetails: Array<{ name: string; value: unknown }> = [];

        if (seriesEvent.value!.intervall === Intervall.WEEKLY && seriesEventsDaysControlsEnabled) {
            recurrenceDetails.push({
                name: t('app.events.columns.weekDay'),
                value: getWeekDayLabel(seriesEvent.value!.weekly_day),
            });
        }
        else if (seriesEvent.value!.intervall === Intervall.MONTHLY && seriesEventsDaysControlsEnabled) {
            if (seriesEvent.value!.monthly_use_start_date_as_default) {
                recurrenceDetails.push({
                    name: t('app.events.columns.useStartDate'),
                    value: t('common.yes'),
                });
            }
            else {
                if (seriesEvent.value!.monthly_weeks?.length) {
                    recurrenceDetails.push({
                        name: t('app.events.columns.weeks'),
                        value: getMonthWeeksLabel(seriesEvent.value!.monthly_weeks),
                    });
                }

                if (seriesEvent.value!.monthly_week_day !== undefined) {
                    recurrenceDetails.push({
                        name: t('app.events.columns.weekDay'),
                        value: getWeekDayLabel(seriesEvent.value!.monthly_week_day),
                    });
                }
            }
        }

        descriptionListData.value = [
            {
                name: 'ID',
                value: seriesEvent.value!.id,
            },
            {
                name: t('app.events.columns.title'),
                value: seriesEvent.value!.name,
            },
            {
                name: t('app.events.columns.eventCount'),
                value: createdEvents.value.length,
            },
            {
                name: t('app.events.columns.startDate'),
                value: formatDateTime(seriesEvent.value!.start),
            },
            {
                name: t('app.events.columns.endDate'),
                value: formatDateTime(seriesEvent.value!.end),
            },
            {
                name: t('app.events.columns.intervall'),
                value: intervallLabels[seriesEvent.value!.intervall.toLowerCase()] || seriesEvent.value!.intervall,
            },
            ...recurrenceDetails,
            {
                name: t('app.events.columns.addedBy'),
                value: `${seriesEvent.value!.user.mobilizon_name}`,
            },
        ];

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
