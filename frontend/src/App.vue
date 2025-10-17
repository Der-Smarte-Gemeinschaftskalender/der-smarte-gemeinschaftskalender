<script lang="ts" setup>
import Footer from '@/components/Footer.vue';
import Header from '@/components/Header.vue';
</script>
<template>
    <template v-if="!!$route.meta.showWithoutLayout"><RouterView :key="$route.fullPath" /></template>
    <template v-else>
        <Header class="mb-3 md:mb-5 xl:mb-7" />
        <div class="main">
            <div class="flex justify-content-center">
                <RouterView :key="$route.fullPath" />
            </div>
        </div>
        <Footer class="mt-7" />
    </template>
</template>

<style scoped lang="scss"></style>
