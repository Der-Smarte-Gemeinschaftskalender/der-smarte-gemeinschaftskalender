<script lang="ts" setup>
import { ref } from 'vue';
import { findOrganisations } from '@/lib/mobilizonClient';

import OrganisationCard from '@/components/OrganisationCard.vue';

import type { IOrganisation } from '@/types/General';


const organisations = ref<IOrganisation[]>([]);

const loadOrganisations = async () => {
    try {
        const result = await findOrganisations();
        organisations.value = result.elements;
    }
    catch (error) {
        console.error('Error loading events:', error);
    }
};
loadOrganisations();
</script>
<template>
    <Teleport to="#headerslot">
        <div class="mb-3 mt-4 sm:mb-4 sm:mt-5 md:my-6">
            <h1 class="kern-heading text-theme-primary">Organisationen</h1>
        </div>
    </Teleport>
  <div class="cards-template">
    <div
        v-for="organisation in organisations"
        :key="organisation.id"
        class=""
    >
      <OrganisationCard
          :organisation="organisation"
          class="h-full"
      />
    </div>
  </div>
</template>
<style scoped lang="scss">
.kern-row {


    .kern-card {
        width: 100%;
        max-width: 350px;
    }
}
</style>