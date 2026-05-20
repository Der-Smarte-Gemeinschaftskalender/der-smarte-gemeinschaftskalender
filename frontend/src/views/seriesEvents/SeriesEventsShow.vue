<script setup lang="ts">
import { ref } from 'vue';
import { dsgApi } from '@/lib/dsgApi';
import { useRoute, useRouter } from 'vue-router';
import { intervall_keys, monthlyIntervalWeekOptions } from '@/lib/const';
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

const loading = ref(false);
const seriesEvent = ref<SeriesEventWithRelations>();
const createdEvents = ref();
const descriptionListData = ref<{ name: string; value: unknown }[]>([]);

const weekDayLabels: Record<number, string> = {
    0: 'Sonntag',
    1: 'Montag',
    2: 'Dienstag',
    3: 'Mittwoch',
    4: 'Donnerstag',
    5: 'Freitag',
    6: 'Samstag',
};

const getWeekDayLabel = (day?: number): string => {
    if (day === undefined || day === null) return 'Nicht gesetzt';
    return weekDayLabels[day] ?? String(day);
};

const getMonthWeeksLabel = (weeks?: number[]): string => {
    if (!weeks?.length) return 'Nicht gesetzt';

    return weeks
        .map((week) => monthlyIntervalWeekOptions.find((option) => option.value === week)?.text ?? String(week))
        .join(', ');
};

const { deleting, showDeleteDialog, alertMessage, handleDelete } = useEventShowDelete({
    getDeleteUrl: () => `/series-events/${route.params.id}`,
    redirectRouteName: 'seriesEvents.index',
    fallbackErrorText: 'Fehler beim Löschen der Serientermine. Bitte versuchen Sie es später erneut oder kontaktieren Sie den Support.',
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
                name: 'Wochentag',
                value: getWeekDayLabel(seriesEvent.value!.weekly_day),
            });
        }
        else if (seriesEvent.value!.intervall === Intervall.MONTHLY && seriesEventsDaysControlsEnabled) {
            if (seriesEvent.value!.monthly_use_start_date_as_default) {
                recurrenceDetails.push({
                    name: 'Startdatum übernehmen',
                    value: 'Ja',
                });
            }
            else {
                if (seriesEvent.value!.monthly_weeks?.length) {
                    recurrenceDetails.push({
                        name: 'Woche(n)',
                        value: getMonthWeeksLabel(seriesEvent.value!.monthly_weeks),
                    });
                }

                if (seriesEvent.value!.monthly_week_day !== undefined) {
                    recurrenceDetails.push({
                        name: 'Wochentag',
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
                name: 'Titel',
                value: seriesEvent.value!.name,
            },
            {
                name: 'Terminanzahl',
                value: createdEvents.value.length,
            },
            {
                name: 'Startdatum',
                value: formatDateTime(seriesEvent.value!.start),
            },
            {
                name: 'Enddatum',
                value: formatDateTime(seriesEvent.value!.end),
            },
            {
                name: 'Intervall',
                value:
                    intervall_keys[seriesEvent.value!.intervall.toLowerCase() as keyof typeof intervall_keys] ||
                    seriesEvent.value!.intervall,
            },
            ...recurrenceDetails,
            {
                name: 'Angelegt von',
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
        title="Gesamten Serientermin löschen"
        :description='`Sie sind dabei, folgenden Serientermin zu löschen: ${ seriesEvent?.name }.\n\n Alle darin enthaltenen Einzeltermine werden ebenfalls dauerhaft gelöscht. Eine Wiederherstellung ist nicht möglich.\n\nWenn bereits Werbung für die Veranstaltungen gemacht wurde, sollten Sie den Status der einzelnen Veranstaltung auf "Abgesagt" setzen, statt den Serientermin zu löschen.`'
        confirm-text="Löschen"
        @confirm="handleDelete"
    />

    <div class="flex align-items-center justify-content-between mb-1">
        <h1 class="kern-heading text-theme-primary">Serientermin</h1>
        <div class="flex gap-2">
            <template v-if="!!seriesEvent">
                <Button
                    variant="secondary"
                    icon-left="delete"
                    :disabled="deleting"
                    @click="showDeleteDialog = true"
                >
                    {{ deleting ? 'Wird gelöscht...' : 'Löschen' }}
                </Button>
            </template>
        </div>
    </div>
    <template v-if="!loading">
        <DescriptionList :data="descriptionListData" />
        <h3 class="kern-heading mt-8 text-theme-primary">Angelegte Termine</h3>
        <CreatedEventsTable 
            :data="createdEvents" 
            type="series_event" 
        />
    </template>
    <Loader v-else />
</template>
