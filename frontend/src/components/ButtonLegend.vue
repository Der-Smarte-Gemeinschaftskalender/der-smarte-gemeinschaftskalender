<script setup lang="ts">
import Legend from '@/components/Legend.vue';

const listLegendItems = { 
    'content-copy': 'Duplizieren (als Vorlage verwenden)',
    'edit': 'Bearbeiten',
    'delete': 'Löschen',
    'open-in-new': 'Öffentliche Seite ansehen',
    'visibility': 'Details ansehen',
    'wall_art': 'Werbemittelgenerator (Ankündigung einer einzelnen Veranstaltung)',
};
</script>
<template>
    <Legend
        title="Button-Legende (je nach Tabelle sind nicht alle Icons vorhanden)"
        :icons="listLegendItems"
        
    />
</template>
