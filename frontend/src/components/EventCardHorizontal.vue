<script lang="ts" setup>
import Card from './KERN/Card.vue';
import Icon from './KERN/cosmetics/Icon.vue';
import EventStatusBadge from './EventStatusBadge.vue';
import { formatOnDate, formatOnTime, buildAddress, formatDateTime, getCardImageUrl } from '@/lib/helper';

import type { IEventDetailed } from '@/types/General';

interface Props {
    event: IEventDetailed;
}

defineProps<Props>();
</script>
<template>
    <Card
        body-class="p-1 m-1 flex-direction-unset gap-unset kern-text w-full px-1 py-0"
        class="p-1"
        :image-src="getCardImageUrl(event)"
        :image-alt="''"
        :image-class="'max-w-18rem w-18rem p-2 my-auto flex-shrink-0'"
        :horizontal="true"
    >
        <div class="kern-row w-full kern-text kern-text--bold pt-0 pl-3 flex-grow-1">
            <div class="w-full p-0 ml-2 content">
                <div class="kern-row">
                    <div class="kern-col-10 mt-3">
                        <RouterLink
                            :to="{
                                name: 'public.event',
                                params: { uuid: event.uuid },
                            }"
                            class="kern-heading text-2xl font-medium text-theme-primary"
                        >
                            {{ event.title }}
                        </RouterLink>
                    </div>
                    <div
                        v-if="event.status && event.status !== 'CONFIRMED'"
                        class="kern-col-2"
                    >
                        <EventStatusBadge :status="event.status" />
                    </div>
                </div>
                <div class="kern-row mt-2 mb-4">
                    <div class="kern-col-4 align-items-start p-2 flex gap-2">
                        <Icon name="calendar_month" />

                        <div class="hidden xl:inline-block">
                            <div>{{ formatOnDate(event.beginsOn) }}</div>
                            <template v-if="formatOnDate(event.beginsOn) != formatOnDate(event.endsOn)">
                                <div>{{ formatOnDate(event.endsOn) }}</div>
                            </template>
                        </div>
                        <div class="xl:hidden">
                            <div>{{ formatDateTime(event.beginsOn) }}</div>
                            <template v-if="formatDateTime(event.beginsOn) != formatDateTime(event.endsOn)">
                                <div>{{ formatDateTime(event.endsOn) }}</div>
                            </template>
                        </div>
                    </div>
                    <div class="kern-col-8 p-2 flex gap-2 align-items-start">
                        <Icon name="location_on" />
                        <div class="line-height-2 white-space-pre-line">
                            {{ buildAddress(event.physicalAddress) || $t('public.event.noAddress') }}
                        </div>
                    </div>
                    <div class="kern-col-4 flex gap-2 align-items-start p-2">
                        <template v-if="formatOnDate(event.beginsOn) === formatOnDate(event.endsOn)">
                            <Icon name="schedule" />
                            {{ formatOnTime(event.beginsOn) }}
                        </template>
                    </div>
                    <div
                        v-if="event?.attributedTo?.name"
                        class="kern-col-8 flex gap-2 align-items-start p-2"
                    >
                        <Icon name="person" />
                        {{ event?.attributedTo?.name }}
                    </div>
                </div>
            </div>
        </div>
    </Card>
</template>
<style scoped lang="scss">
.content {
    max-width: 550px !important;
}
</style>
