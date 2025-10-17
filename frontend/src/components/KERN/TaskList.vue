<script setup lang="ts">
import { defineProps } from "vue";

interface Task {
  title: string;
  status: "active" | "completed" | "warning" | "info";
}

interface Props {
  title: string;
  ordered?: boolean;
  tasks: Task[];
}

defineProps<Props>();

const status = {
  active: {
    title: "Aktiv",
    severity: "info",
  },
  completed: {
    title: "Erledigt",
    severity: "success",
  },
  warning: {
    title: "Warnung",
    severity: "warning",
  },
  info: {
    title: "Info",
    severity: "info",
  },
};
</script>

<template>
  <div class="kern-tasklist">
    <div class="kern-tasklist__header">
      <h2 class="kern-heading">
        {{ title }}
      </h2>
    </div>

    <ul class="kern-tasklist__list">
      <li v-for="task in tasks" :key="task.title" class="kern-tasklist__item">
        <span v-if="ordered" class="kern-number" />
        <div class="kern-tasklist__title">
          <slot name="task-title" class="kern-link" :task="task">
            <p class="kern-text">
              {{ task.title }}
            </p>
          </slot>
          <div class="kern-tasklist__status">
            <span
              :class="`kern-badge kern-badge--${status[task.status].severity}`"
            >
              <span
                class="kern-icon kern-icon--success kern-icon--sm"
                aria-hidden="true"
              />
              <span class="kern-badge__title">{{
                status[task.status].title
              }}</span>
            </span>
          </div>
        </div>
      </li>
    </ul>
  </div>
</template>

<style scoped lang="scss"></style>
