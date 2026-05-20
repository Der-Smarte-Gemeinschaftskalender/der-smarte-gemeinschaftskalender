<script lang="ts" setup>
import { useI18n } from 'vue-i18n';
import { exportList } from '@/lib/exportList';
import Divider from '@/components/KERN/cosmetics/Divider.vue';
import Button from '@/components/KERN/Button.vue';
import Icon from '@/components/KERN/cosmetics/Icon.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';

import { instanceInformation } from '@/lib/instanceConfig';


const { t } = useI18n();
</script>
<template>
    <Teleport to="#headerslot">
        <div class="mb-3 mt-4 sm:mb-4 sm:mt-5 md:my-6">
            <h1 class="kern-heading text-theme-primary">{{ $t('public.exports.title') }}</h1>
            <h2 class="kern-heading font-semilight text-theme-primary">
                {{ $t('public.exports.subtitle') }}
            </h2>
        </div>
    </Teleport>
    <div>
        <p class="kern-text kern-text--large text-theme-primary mb-6">
            {{ $t('public.exports.introText', { operatedBy: instanceInformation.operatedBy }) }}
        </p>
        <div
            v-for="(exportItem, index) in exportList"
            :key="exportItem"
        >
            <div class="kern-row">
                <div class="kern-col-md-4 pt-3">
                    <RouterLink
                        :to="{ name: exportItem.link.name }"
                        class=""
                    >
                        <Button
                            :icon-left="exportItem.link.icon"
                            class="w-full"
                        >
                            {{ $t(exportItem.link.titleKey) }}
                        </Button>
                    </RouterLink>
                </div>
                <div class="kern-col-md-8">
                    <h3 class="kern-heading text-theme-primary">{{ $t(exportItem.titleKey) }}</h3>
                    <template v-if="exportItem.customDescription === 'informationBoards'">
                        <p class="kern-text mb-4">{{ $t('public.exports.infoboards.intro') }}</p>
                        <p class="kern-text mb-2">{{ $t('public.exports.infoboards.howItWorks') }}</p>
                        <ol class="ml-5 mb-4 kern-text list-decimal">
                            <li>
                                {{ $t('public.exports.infoboards.step1Prefix') }} <b>{{ $t('public.exports.infoboards.step1Search') }}</b>
                                {{ $t('public.exports.infoboards.step1Middle') }} <b>{{ $t('public.exports.infoboards.step1Filter') }}</b>
                                {{ $t('public.exports.infoboards.step1Suffix') }}
                            </li>
                            <li>
                                {{ $t('public.exports.infoboards.step2Prefix') }} 
                                <Icon name="info" />
                                {{ $t('public.exports.infoboards.step2Suffix') }}
                            </li>
                            <li>
                                {{ $t('public.exports.infoboards.step3') }}
                            </li>
                        </ol>
                        <p class="kern-text mb-4"><b>{{ $t('public.exports.infoboards.tipLabel') }}</b> {{ $t('public.exports.infoboards.tipText') }}</p>
                        <p class="kern-text mb-4">
                            {{ $t('public.exports.infoboards.description') }}
                        </p>
                        <p class="kern-text">
                            {{ $t('public.exports.infoboards.moreInfoPrefix') }} <LinkToDocs path="" />.
                        </p>
                    </template>
                    <p
                        v-else
                        class="kern-text"
                        style="white-space: pre-line"
                    >
                        {{ $t(exportItem.descriptionKey) }}
                    </p>
                </div>
            </div>
            <Divider
                v-if="index !== exportList.length - 1"
                class="mt-6 mb-8"
            />
        </div>
    </div>
</template>
