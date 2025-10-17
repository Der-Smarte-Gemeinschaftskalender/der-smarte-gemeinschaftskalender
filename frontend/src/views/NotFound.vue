<script setup lang="ts">
import Button from '@/components/KERN/Button.vue';
</script>
<template>
    <div>
        <h1 class="kern-heading">Seite konnte nicht gefunden werden</h1>
        <RouterLink :to="{ name: 'landingpage' }">
            <Button>Übersicht</Button>
        </RouterLink>
    </div>
</template>
