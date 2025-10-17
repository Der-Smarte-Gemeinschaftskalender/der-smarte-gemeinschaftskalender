<script lang="ts" setup>
import { ref, onMounted, watch, nextTick } from 'vue';

interface Props {
    label?: string;
    title?: string;
    subtitle?: string;
    imageSrc?: string|undefined;
    imageAlt?: string;
    bodyClass?: string;
    imageClass?: string;
    horizontal?: boolean;
    to?: { name: string; params?: Record<string, string | number>; query?: Record<string, string | number>  } | { path: string  };
    callbackTo?: () => void;
}

defineProps<Props>();

const fallbackSrc = '/default_card.png';

const onImageError = (event: Event) => {
    console.warn('Image load error, setting fallback image.');
    const target = event.target as HTMLImageElement;
    if (target.src !== fallbackSrc) target.src = fallbackSrc;
};

</script>
<template>
    <article
        class="kern-card"
        :class="{
            'flex-row': horizontal,
            'justify-between': horizontal,

        }"
    >
        <div
            v-if="imageSrc"
            class="kern-card__media"
            :class="[imageClass]"
        >
            <img
                :src="imageSrc"
                :alt="imageAlt"
                :class="`${to || callbackTo ? 'cursor-pointer' : ''}`"
                @error="onImageError"
                @click="to || callbackTo ? (to ? $router.push(to) : callbackTo && callbackTo()) : null"
            />
        </div>
        <div
            class="h-full flex flex-column justify-content-between gap-3 p-3"
            :class="[bodyClass]"
        >
            <header
                class="kern-card__header"
                v-if="!!label || !!title || !!subtitle"
            >
                <p
                    v-if="label"
                    class="kern-card__label"
                >
                    {{ label }}
                </p>
                <h4
                    v-if="title"
                    class="kern-card__title font-medium line-clamp-2 line-height-2 p-0"
                >
                    {{ title }}
                </h4>
                <h3
                    v-if="subtitle"
                    class="kern-card__subtitle"
                >
                    {{ subtitle }}
                </h3>
            </header>
            <section
                v-if="!!$slots.default"
                class="kern-card__content w-full mb-auto text-overflow-ellipsis h-fit overflow-hidden"
            >
                <slot />
            </section>
            <footer
                v-if="!!$slots.footer"
                class="kern-card__footer">
                <slot name="footer" />
            </footer>
        </div>
    </article>
</template>

<style scoped lang="scss">
    .kern-card__media {
        background-color: #f4fdfb;
        width: 100%;
    }

    .kern-card__media img {
        margin: 0 auto;
        width: 100%;
        aspect-ratio: 16 / 9;
        object-fit: cover;
    }
</style>