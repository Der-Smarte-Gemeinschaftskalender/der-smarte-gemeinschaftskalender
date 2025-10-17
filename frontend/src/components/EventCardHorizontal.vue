<script lang="ts" setup>
import Card from './KERN/Card.vue';
import Button from './KERN/Button.vue';
import Icon from './KERN/cosmetics/Icon.vue';
import EventStatusBadge from './EventStatusBadge.vue';
import { formatOnDate, formatOnTime, buildAddress, formatDateTime } from '@/lib/helper';

import type { IEventDetailed } from '@/types/General';

interface Props {
    event: IEventDetailed;
}

defineProps<Props>();
</script>
<template>
    <Card
        bodyClass="p-1 m-1 flex-direction-unset gap-unset kern-text w-full"
        class="p-1"
        :image-src="event.picture?.url || '/default_card.png'"
        :image-alt="event.title"
        :image-class="'max-w-20rem p-2 my-auto'"
        :horizontal="true"
    >
        <div class="kern-row w-full kern-text kern-text--bold pt-0 pl-3">
            <div class="kern-col-8 p-0 min-w-24rem">
                <div class="kern-row">
                    <div class="kern-col-10">
                        <h4 class="kern-heading font-medium text-theme-primary">{{ event.title }}</h4>
                    </div>
                    <div
                        class="kern-col-2"
                        v-if="event.status && event.status !== 'CONFIRMED'"
                    >
                        <EventStatusBadge :status="event.status" />
                    </div>
                </div>
                <div class="kern-row mt-2 mb-4">
                    <div class="kern-col-4 align-items-start p-2 flex gap-2">
                        <Icon name="calendar_month" />

                        <span class="hidden xl:inline-block">
                            {{ formatOnDate(event.beginsOn) }}
                            <template v-if="formatOnDate(event.beginsOn) != formatOnDate(event.endsOn)">
                                <br />
                                <span>{{ formatOnDate(event.endsOn) }}</span>
                            </template>
                        </span>
                        <span class="xl:hidden">
                            {{ formatDateTime(event.beginsOn) }}
                            <template v-if="formatDateTime(event.beginsOn) != formatDateTime(event.endsOn)">
                                <br />
                                <span>{{ formatDateTime(event.endsOn) }}</span>
                            </template>
                        </span>
                    </div>
                    <div class="kern-col-8 p-2 flex gap-2 align-items-start">
                        <Icon name="location_on" />
                        <div class="line-height-2 white-space-pre-line">
                            {{ buildAddress(event.physicalAddress) || 'Keine Adresse' }}
                        </div>
                    </div>
                    <div class="kern-col-4 flex gap-2 align-items-start p-2">
                        <template v-if="formatOnDate(event.beginsOn) === formatOnDate(event.endsOn)">
                            <Icon name="schedule" />
                            {{ formatOnTime(event.beginsOn) }}
                        </template>
                    </div>
                    <div
                        class="kern-col-8 flex gap-2 align-items-start p-2"
                        v-if="event?.attributedTo?.name"
                    >
                        <Icon name="person" />
                        {{ event?.attributedTo?.name }}
                    </div>
                </div>
                <RouterLink
                    v-if="event.uuid"
                    :to="{ name: 'public.event', params: { uuid: event.uuid } }"
                    class="mt-0 mb-3"
                >
                    <Button
                        icon-left="visibility"
                        class="px-4"
                    >
                        Ansehen
                    </Button>
                </RouterLink>
            </div>
        </div>
    </Card>
</template>
