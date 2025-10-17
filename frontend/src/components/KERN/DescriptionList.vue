<script lang="ts" setup>
import { type DescriptionListData } from '@/types/KERN';
interface Props {
    data: DescriptionListData[];
}

defineProps<Props>();
</script>
<template>
    <dl class="kern-description-list">
        <template
            v-for="row in data"
            :key="row.key || row"
        >
            <div class="kern-description-list__row">
                <dt class="kern-description-list__key">
                    {{ row.name }}
                </dt>
                <dd class="kern-description-list__value">
                    <template v-if="row.slot">
                        <slot
                            :name="row.key"
                            :row="row"
                        />
                    </template>
                    <template v-else>
                        <p class="text-overflow-ellipsis">
                            {{ row.value }}
                        </p>
                    </template>
                </dd>
            </div>
        </template>
    </dl>
</template>
