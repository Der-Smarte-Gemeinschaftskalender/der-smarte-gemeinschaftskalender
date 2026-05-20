<script setup lang="ts">

interface Props {
    variables: { [key: string]: string };
}

defineProps<Props>();
</script>

<template>
    <div v-if="variables && Object.keys(variables).length > 0" class="bg-gray-100 p-4 border-round-xs" style="border-left: 4px solid #2b2c6a;">
        <slot name="title" />
        <ul class="mt-2 ml-4 text-900">

            <li v-for="(description, variable) in variables" :key="variable" class="my-3">
                <code class="bg-gray-300 px-1 py-1 font-mono border-round-md">:{{ variable }}</code> – {{ description }}
            </li>
        </ul>
    </div>
</template>
