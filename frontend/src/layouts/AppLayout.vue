<script lang="ts" setup>
import { ref } from 'vue';
import SideNavigation from '@/components/SideNavigation.vue';
import Overlayable from "@/components/Overlayable.vue";
import Button from "@/components/KERN/Button.vue";


const overlayable = ref<InstanceType<typeof Overlayable> | null>(null);


</script>
<template>
    <div class="w-full flex justify-content-between gap-6 xl:gap-8">
        <Overlayable
            ref="overlayable"
            toggle-breakpoint="lg"
            overlay-max-width="350px"
            panel-class="side-navigation p-0 hidden lg:block overflow-y-hidden min-w-18rem"
        >
            <SideNavigation
              class="w-full overflow-y-hidden"
            />
        </Overlayable>
        <main class="w-full overflow-x-scroll">
            <Button
                class="lg:hidden mb-5 mt-1"
                icon-left="menu"
                variant="secondary"
                @click="overlayable?.openOverlay()"
            />
            <RouterView />
        </main>
    </div>
</template>
<style scoped lang="scss">

</style>
