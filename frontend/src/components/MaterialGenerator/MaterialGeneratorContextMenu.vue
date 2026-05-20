<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import Divider from '@/components/KERN/cosmetics/Divider.vue';
import Icon from '@/components/KERN/cosmetics/Icon.vue';

const props = defineProps<{
    visible: boolean;
    x: number;
    y: number;
}>();

const emit = defineEmits<{
    close: [];
    bringForward: [];
    sendBackward: [];
    bringToFront: [];
    sendToBack: [];
    duplicate: [];
    delete: [];
}>();

const menuRef = ref<HTMLElement | null>(null);

type ContextAction = 'bringForward' | 'sendBackward' | 'bringToFront' | 'sendToBack' | 'duplicate' | 'delete';

function onAction(action: ContextAction) {
    switch (action) {
        case 'bringForward':
            emit('bringForward');
            break;
        case 'sendBackward':
            emit('sendBackward');
            break;
        case 'bringToFront':
            emit('bringToFront');
            break;
        case 'sendToBack':
            emit('sendToBack');
            break;
        case 'duplicate':
            emit('duplicate');
            break;
        case 'delete':
            emit('delete');
            break;
    }
    emit('close');
}

function onClickOutside(e: MouseEvent) {
    if (props.visible && menuRef.value && !menuRef.value.contains(e.target as Node)) {
        emit('close');
    }
}

function onKeydown(e: KeyboardEvent) {
    if (props.visible && e.key === 'Escape') {
        emit('close');
    }
}

onMounted(() => {
    document.addEventListener('mousedown', onClickOutside, true);
    document.addEventListener('keydown', onKeydown, true);
});
onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onClickOutside, true);
    document.removeEventListener('keydown', onKeydown, true);
});
</script>

<template>
    <div
        v-if="visible"
        ref="menuRef"
        class="mg-context-menu kern-card"
        :style="{ left: x + 'px', top: y + 'px' }"
    >
        <button
            class="mg-context-menu__item flex align-items-center gap-2 w-full border-none bg-transparent cursor-pointer white-space-nowrap py-1 px-3"
            @click="onAction('bringForward')"
        >
            <Icon
                name="keyboard_arrow_up"
                size="sm"
            />
            Ebene nach vorne
        </button>
        <button
            class="mg-context-menu__item flex align-items-center gap-2 w-full border-none bg-transparent cursor-pointer white-space-nowrap py-1 px-3"
            @click="onAction('sendBackward')"
        >
            <Icon
                name="keyboard_arrow_down"
                size="sm"
            />
            Ebene nach hinten
        </button>
        <button
            class="mg-context-menu__item flex align-items-center gap-2 w-full border-none bg-transparent cursor-pointer white-space-nowrap py-1 px-3"
            @click="onAction('bringToFront')"
        >
            <Icon
                name="keyboard_double_arrow_up"
                size="sm"
            />
            Ganz nach vorne
        </button>
        <button
            class="mg-context-menu__item flex align-items-center gap-2 w-full border-none bg-transparent cursor-pointer white-space-nowrap py-1 px-3"
            @click="onAction('sendToBack')"
        >
            <Icon
                name="keyboard_double_arrow_down"
                size="sm"
            />
            Ganz nach hinten
        </button>
        <Divider class="my-1" />
        <button
            class="mg-context-menu__item flex align-items-center gap-2 w-full border-none bg-transparent cursor-pointer white-space-nowrap py-1 px-3"
            @click="onAction('duplicate')"
        >
            <Icon
                name="content-copy"
                size="sm"
            />
            Duplizieren
        </button>
        <button
            class="mg-context-menu__item mg-context-menu__item--danger flex align-items-center gap-2 w-full border-none bg-transparent cursor-pointer white-space-nowrap py-1 px-3"
            @click="onAction('delete')"
        >
            <Icon
                name="delete"
                size="sm"
            />
            Löschen
        </button>
    </div>
</template>

<style scoped>
.mg-context-menu {
    position: absolute;
    z-index: 1000;
    min-width: 200px;
    box-shadow:
        0 6px 24px rgba(0, 0, 0, 0.14),
        0 2px 8px rgba(0, 0, 0, 0.08);
    padding: 0.25rem 0;
    user-select: none;
    align-items: stretch;
}
.mg-context-menu__item {
    border-radius: var(--kern-border-radius-small, 2px);
}
.mg-context-menu__item:hover {
    background: var(--kern-layout-surface-highlight, #f6f9fc);
}
.mg-context-menu__item--danger:hover {
    background: rgba(220, 53, 69, 0.06);
}
</style>
