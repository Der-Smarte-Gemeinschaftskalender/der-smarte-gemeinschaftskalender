<script lang="ts" setup>
import { computed, ref, watch } from 'vue';
import { findOrganisationOptions } from '@/lib/mobilizonClient';

import Fieldset from '@/components/KERN/Fieldset.vue';
import InputSelect from '@/components/KERN/inputs/InputSelect.vue';
import InputTextarea from '@/components/KERN/inputs/InputTextarea.vue';

import { MobilizonCategoryAndAll, type Option } from '@/types/General';
import { mobilizon_category_options_all } from '@/lib/const';

import LinkToDocs from '@/components/LinkToDocs.vue';

const organisationOptions = ref<Option[]>([]);

const category = ref<MobilizonCategoryAndAll>(MobilizonCategoryAndAll.ALL);
const organisation = ref<string>(null);

const scriptOutput = computed<string>(() => {
    return `<script src="${import.meta.env.VITE_APP_URL}/fetchEvents.js"  data-organisation-username="${organisation.value}" data-category="${category.value}" data-limit="5" id="dsg-event-script" data-dsg-url="${import.meta.env.VITE_APP_URL}"></` + 'script>';
});
const scriptOutputHTML = computed<string>(() => {
    return `<ul id="dsg-event-list"></ul>`;
});

watch(organisation, (newOrganisationUsername) => {
    if (newOrganisationUsername != 'ALL') {
        category.value = MobilizonCategoryAndAll.ALL;
    }
});

findOrganisationOptions(true).then((options) => {
    organisationOptions.value = options;
    organisation.value = options[0]?.value || null;
});
</script>
<template>
    <div>
        <h2 class="kern-heading font-medium p-0 mb-2 text-theme-primary">
            {{ $t('public.exports.script.title') }}
        </h2>
        <h4 class="kern-heading font-medium p-0 text-theme-primary">
            {{ $t('public.exports.script.pageSubtitle') }}
        </h4>
        <p class="mt-4 kern-text">
            <b>{{ $t('public.exports.script.noticeLabel') }}</b>
            {{ $t('public.exports.script.noticePrefix') }}
            <LinkToDocs path="Entwicklungsbereich/Kalender%20einbinden/" />.
        </p>
        <Fieldset class="mb-4">
            <section class="flex justify-content-between">
                <InputSelect
                    v-model="organisation"
                    class="col-5"
                    name="organisation"
                    :label="$t('public.exports.script.organisationLabel')"
                    :options="organisationOptions"
                    :disabled="!organisationOptions.length"
                />
                <InputSelect
                    v-model="category"
                    class="col-5"
                    name="category"
                    :label="$t('public.exports.script.categoryLabel')"
                    :options="mobilizon_category_options_all"
                    :disabled="organisation !== 'ALL'"
                />
            </section>
        </Fieldset>
        <InputTextarea
            v-model="scriptOutput"
            :label="$t('public.exports.script.jsEmbedLabel')"
            name="scriptOutput"
        />
        <InputTextarea
            v-model="scriptOutputHTML"
            class="mt-4"
            :label="$t('public.exports.script.htmlEmbedLabel')"
            name="scriptOutputhtml"
        />
    </div>
</template>
<style lang="scss"></style>
