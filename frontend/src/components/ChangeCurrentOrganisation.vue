<script lang="ts" setup>
import { ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { getMobilizionGroupOptions } from '@/lib/dsgClient';
import {
    user_organisations,
    current_organisation,
    setOrganisationData,
    getCurrentOrganisationOptionId,
} from '@/composables/OrganisationComposable';

import InputSelect from '@/components/KERN/inputs/InputSelect.vue';

import type { Option } from '@/types/General';
import type { MobilizonGroup } from '@/types/Mobilizon';

const options = ref<Option[]>([]);
const selectedOption = ref<number|null>(null);
const route = useRoute();
const router = useRouter();

const setOrganisationOptions = async () => {
    await setOrganisationData();

    options.value = await getMobilizionGroupOptions(user_organisations.value);
    selectedOption.value = getCurrentOrganisationOptionId(options.value);
    if (!selectedOption.value && options.value.length > 0) {
        selectedOption.value = options.value[0].value;
    }
};

watch(selectedOption, async (newSelectedOption, oldSelectedOption) => {
    if (!newSelectedOption) {
        return;
    }
    if (newSelectedOption !== oldSelectedOption && newSelectedOption != current_organisation.value?.id) {
        const selectedOrganisation = options.value.find((option) => option.value === newSelectedOption);
        if (selectedOrganisation) {
            const newUserOrganisation: MobilizonGroup | undefined = user_organisations.value?.find(
                (organisation) => organisation.id === selectedOrganisation.value
            );
            await setOrganisationData(newUserOrganisation);
            if ((<string>route.name)?.search('index') != -1) {
                router.go(0);
            } else {
                await router.push({
                    name: 'dashboard',
                });
            }
        }
    }
});
setOrganisationOptions();
</script>
<template>
    <InputSelect
        v-if="user_organisations?.length"
        v-model="selectedOption"
        class="p-0"
        name="organisation"
        :options="options"
        :clean="true"
    />
</template>
