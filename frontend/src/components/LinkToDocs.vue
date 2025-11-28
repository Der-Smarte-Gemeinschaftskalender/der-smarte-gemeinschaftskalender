<script setup lang="ts">
const docsUrl = import.meta.env.VITE_DSG_DOCS_URL;
interface Props {
    path: string;
    fragment?: string;
}

const { path, fragment } = defineProps<Props>();
const fullDocsUrl = `${docsUrl}/${path}${fragment ? `#${fragment}` : ''}`;
</script>
<template>
    <a
        :href="fullDocsUrl"
        target="_blank"
    >
        <slot>Handbuch</slot>
    </a>
</template>
