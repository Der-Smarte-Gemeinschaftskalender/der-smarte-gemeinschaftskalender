<script setup lang="ts">
import { defineProps } from "vue";

interface Row {
  key: string;
  value: string | string[];
}

interface Props {
  title: string;
  ordered?: boolean;
  data: Row[];
}

defineProps<Props>();

const getSlotName = (key: string): string => {
  return key.toLowerCase().replaceAll(" ", "").replaceAll("-", "");
};
</script>

<template>
  <div class="kern-summary">
    <div class="kern-summary__header">
      <span v-if="ordered" class="kern-number" />
      <h3 class="kern-summary__title">
        {{ title }}
      </h3>
    </div>

    <div class="kern-summary__content">
      <dl class="kern-description-list">
        <div
          v-for="row in data"
          :key="row.key"
          class="kern-description-list__row"
        >
          <dt class="kern-description-list__key">
            {{ row.key }}
          </dt>
          <dd class="kern-description-list__value">
            <slot :name="getSlotName(row.key)" :row="row">
              <template v-if="Array.isArray(row.value)">
                <ul>
                  <li v-for="(item, i) in row.value" :key="i">
                    {{ item }}
                  </li>
                </ul>
              </template>
              <template v-else>
                {{ row.value }}
              </template>
            </slot>
          </dd>
        </div>
      </dl>
      <div v-if="$slots['actions']" class="kern-summary__actions">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss"></style>
