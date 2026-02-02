<script setup lang="ts">
import { ref, watch, computed, onMounted, onUnmounted } from 'vue'
import Button from "@/components/KERN/Button.vue";

interface Props {
    overlayClass?: string
    panelClass?: string
    closeButtonClass?: string
    toggleBreakpoint?: 'sm' | 'md' | 'lg' | 'xl'
    pixel?: number
    overlayMaxWidth?: string
    modelValue?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    overlayClass: '',
    panelClass: '',
    closeButtonClass: '',
    toggleBreakpoint: 'sm',
    pixel: 300,
    overlayMaxWidth: '100%',
    modelValue: false
});


const breakpoints: Record<'sm' | 'md' | 'lg' | 'xl', number> = {
    sm: 576,
    md: 768,
    lg: 992,
    xl: 1200
}

const open = ref(props.modelValue);
const viewportWidth = ref(window.innerWidth);
const toggleView = computed(() => breakpoints[props.toggleBreakpoint]);


const handleResize = () => {
    viewportWidth.value = window.innerWidth;
    open.value = !!(viewportWidth.value >= toggleView.value && open.value);

    if (open.value) document.body.classList.add('overflow-hidden');
    else document.body.classList.remove('overflow-hidden');
}

onMounted(() => {
    window.addEventListener('resize', handleResize);
    handleResize();
})

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
})


const toggleOverlay = () => {
    open.value = !open.value;
    if (open.value) document.body.classList.add('overflow-hidden');
    else document.body.classList.remove('overflow-hidden');
}
const closeOverlay = () => {
    open.value = false;
    document.body.classList.remove('overflow-hidden');
}
const openOverlay = () => {
    open.value = true;
    document.body.classList.add('overflow-hidden');
}

defineExpose({
    toggleOverlay,
    openOverlay,
    closeOverlay
});

</script>
<template>

    <div
        v-if="viewportWidth < toggleView"
        class="overlay fixed top-0 left-0 h-full w-full z-5 transition-all transition-duration-200 ease-in-out overflow-y-hidden"
        :class="{
            [`${toggleBreakpoint}:hidden`]: viewportWidth.value >= toggleView,
            'opacity-0 pointer-events-none': !open,
            'opacity-100 pointer-events-auto': open,
        }"
    >
        <div
            class="h-full p-1 relative bg-white overflow-y-scroll"
            :style="{ maxWidth: overlayMaxWidth }"
            @click="closeOverlay"
        >
            <Button
                class="absolute close-button top-0 right-0 mt-2 mr-2 border-none bg-transparent outline-none"
                :class="closeButtonClass"
                icon-left="close"
                icon-size="lg"
                variant="secondary"
                @click.stop="closeOverlay"
            ></Button>
            <div
                class="overlayable-panel"
                :class="['overlay-view', overlayClass]"
                @click.stop
            >
                <slot />
            </div>
        </div>
    </div>
    <div
        v-else
        :class="['panel-view', panelClass]"
    >
        <slot />
    </div>
</template>
<style scoped lang="scss">
.overlay {
    background-color: rgba(170, 175, 172, 0.5);
    backdrop-filter: blur(5px);
}

.close-button, .close-button:focus {
    outline: none !important;
    box-shadow: none !important;

}
</style>