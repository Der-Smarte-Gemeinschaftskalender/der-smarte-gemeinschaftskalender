<script setup lang="ts">
import Icon from '@/components/KERN/cosmetics/Icon.vue';

interface Props {
    variables?: { [key: string]: string };
    icons?: { [key: string]: string };
    title?: string;
}
defineProps<Props>();
</script>
<template>
    <div
        class="bg-gray-100 p-4 border-round-xs"
        style="border-left: 4px solid #2b2c6a"
    >
        <h3
            v-if="title"
            class="text-lg font-semibold text-theme-primary mb-2"
        >
            {{ title || 'Legende' }}
        </h3>
        <slot
            v-else
            name="title"
        />

        <ul
            class="mt-2 ml-4 text-900"
            v-if="(variables && Object.keys(variables).length > 0) || (icons && Object.keys(icons).length > 0)"
        >
            <template v-if="icons">
                <li
                    v-for="(description, icon) in icons"
                    :key="icon"
                    class="my-3 flex align-items-center gap-2"
                >
                    <Icon
                        :name="icon"
                        style="opacity: .7;"
                    />
                    -
                    <span class="text-700">{{ description }}</span>
                </li>
            </template>
            <template v-else-if="variables">
                <li
                    v-for="(description, variable) in variables"
                    :key="variable"
                    class="my-3"
                >
                    <code class="bg-gray-300 px-1 py-1 font-mono border-round-md">:{{ variable }}</code> –
                    {{ description }}
                </li>
            </template>
        </ul>
        <template v-else>
            <p class="text-900 mt-2">Keine Legenden vorhanden.</p>
        </template>
    </div>
</template>
