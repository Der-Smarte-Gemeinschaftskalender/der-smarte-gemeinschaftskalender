<script setup lang="ts">
interface Accordion {
    header: string;
    content?: string;
    open?: boolean;
}

interface Props {
    title?: string;
    ordered?: boolean;
    accordions: Array<Accordion>;
}

defineProps<Props>();
</script>

<template>
    <div class="kern-accordion-group">
        <h1
            v-if="title"
            class="kern-heading"
        >
            {{ title }}
        </h1>
        <details
            v-for="(accordion, index) in accordions"
            class="kern-accordion w-full"
            :open="accordion.open"
        >
            <summary class="kern-accordion__header">
                <span class="flex gap-3 align-items-center">
                    <span
                        v-if="ordered"
                        class="kern-number"
                    >
                        {{ index + 1 }}
                    </span>
                    {{ accordion.header }}
                </span>
            </summary>
            <section class="kern-accordion__content">
                <p
                    v-if="accordion.content"
                    class="kern-text"
                >
                    {{ accordion.content }}
                </p>
                <slot :name="`content-${index + 1}`" />
            </section>
        </details>
    </div>
</template>

<style scoped lang="scss">
.kern-number::before {
    content: '';
}
</style>
