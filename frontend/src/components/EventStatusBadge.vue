<script lang="ts" setup>
import Badge from './KERN/cosmetics/Badge.vue';
import { mobilizon_event_status } from '@/lib/const';
import { EventStatus } from '@/types/General';

interface Props {
    status: EventStatus;
}

const props = defineProps<Props>();

const text = mobilizon_event_status?.find((status) => status?.value === EventStatus[props?.status])?.text;
let severity: "danger" | "info" | "success" | "warning" = 'info';

if (props.status === EventStatus.CONFIRMED) {
    severity = 'success';
} else if (props.status === EventStatus.CANCELLED) {
    severity = 'danger';
} else if (props.status === EventStatus.TENTATIVE) {
    severity = 'warning';
}
</script>
<template>
    <Badge
        :severity="severity"
        :title="text"
    />
</template>
