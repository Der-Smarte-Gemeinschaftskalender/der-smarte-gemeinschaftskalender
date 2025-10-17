<script setup lang="ts">
import { ref } from 'vue';

interface Props {
    id: string;
    value: number;
    max: number;
    showProgressLabel?: boolean;
}
withDefaults(defineProps<Props>(), {
    showProgressLabel: false,
});
</script>

<template>
    <div class="kern-progress">
        <progress
            :id="id"
            :value="value"
            :max="max"
        ></progress>
        <label
            v-if="showProgressLabel"
            class="kern-progress__label"
            :for="id"
        >
            <slot>{{ value }} von {{ max }}</slot>
        </label>
    </div>
</template>
