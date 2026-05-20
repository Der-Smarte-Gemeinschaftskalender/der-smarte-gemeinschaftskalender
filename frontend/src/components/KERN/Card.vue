<script lang="ts" setup>
import type { RouteLocationRaw } from 'vue-router';

interface Props {
    label?: string;
    title?: string;
    subtitle?: string;
    imageSrc?: string | undefined;
    imageAlt?: string;
    bodyClass?: string;
    imageClass?: string;
    horizontal?: boolean;
    to?: RouteLocationRaw;
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
        <RouterLink
            v-if="imageSrc && to"
            class="kern-card__media"
            :class="[imageClass]"
            :to="to"
        >
            <img
                :src="imageSrc"
                :alt="imageAlt"
                class="cursor-pointer"
                @error="onImageError"
            />
        </RouterLink>
        <div
            v-else-if="imageSrc"
            class="kern-card__media"
            :class="[imageClass]"
        >
            <img
                :src="imageSrc"
                :alt="imageAlt"
                @error="onImageError"
            />
        </div>
        <div
            class="h-full flex flex-column justify-content-between gap-3 p-3"
            :class="[bodyClass]"
        >
            <header
                v-if="!!label || !!title || !!subtitle"
                class="kern-card__header"
            >
                <p
                    v-if="label"
                    class="kern-card__label"
                >
                    {{ label }}
                </p>
                <RouterLink
                    v-if="title && to"
                    class="kern-card__title text-2xl font-medium line-clamp-2 line-height-2 p-0 cursor-pointer"
                    :to="to"
                >
                    {{ title }}
                </RouterLink>
                <h2
                    v-else-if="title"
                    class="kern-card__title text-2xl font-medium line-clamp-2 line-height-2 p-0"
                >
                    {{ title }}
                </h2>
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
                class="kern-card__footer"
            >
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
