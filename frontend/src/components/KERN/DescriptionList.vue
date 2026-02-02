<script lang="ts" setup>
import { type DescriptionListData } from '@/types/KERN';
import Button from './Button.vue';
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
                        <p 
                            v-if="!row.type && !row.formatAsHtml"
                            class="text-overflow-ellipsis"
                        >
                            {{ row.value }}
                        </p>
                        <p v-else-if="row.type === 'tags'">
                            <Button
                                v-for="(tag, index) in row.value"
                                :key="index"
                                :label="tag"
                                variant="secondary"
                                size="sm"
                                class="mr-2 mb-2 pointer-events-none"
                            />
                        </p>
                        <p 
                            v-else-if="row.type === 'prose'"
                            class="text-overflow-ellipsis prose"
                        >
                            <span v-if="row.value === null">Keine Angabe</span>
                            <span v-else>{{ row.value }}</span>
                        </p>
                        <a
                            v-else-if="row.type === 'link' && !row.local_url"
                            :href="row.value"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="hover:underline"
                        >
                            {{ row.value }}
                        </a>
                        <RouterLink
                            v-else-if="row.type === 'link' && row.local_url"
                            :to="row.local_url"
                            class="hover:underline"
                        >
                            {{ row.value }}
                        </RouterLink>
                        <div 
                            v-else-if="row.formatAsHtml"
                            class="text-overflow-ellipsis"
                            v-html="row.value"
                        />
                    </template>
                </dd>
            </div>
        </template>
    </dl>
</template>