<script setup lang="ts">
import Icon from './KERN/cosmetics/Icon.vue';

const listLegendItems = [
    { icon: 'content-copy', text: 'Duplizieren (als Vorlage verwenden)' },
    { icon: 'edit', text: 'Bearbeiten' },
    { icon: 'open-in-new', text: 'Öffentliche Seite ansehen' },
    { icon: 'visibility', text: 'Details ansehen' },
    { icon: 'wall_art', text: 'Werbemittelgenerator (Ankündigung einer einzelnen Veranstaltung)' },
];
</script>
<template>
    <div class="button-legend mt-3">
        <p class="kern-text mb-2">
            <span class="kern-text--bold">Button-Legende</span>
            (je nach Tabelle sind nicht alle Icons vorhanden)
        </p>
        <div class="flex flex-wrap flex-column sm:flex-row">
            <template
                v-for="(item, index) in listLegendItems"
                :key="item.icon"
            >
                <span class="kern-text inline-flex items-center w-auto">
                    <Icon :name="item.icon" class="" />
                    <span class="mx-2">=</span>
                    <span class="">{{ item.text }}</span>
                    <span v-if="index != listLegendItems.length - 1" class="mx-4 hidden sm:inline">|</span>
                </span>
            </template>
        </div>
    </div>
</template>
<style scoped lang="scss">
.button-legend {
    h3,
    span,
    p {
        color: #757575 !important;
    }
}
</style>
