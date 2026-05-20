<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
const logoUrl = '/logo.png';
import { instanceInformation, mainFooter } from '@/lib/instanceConfig';

const { name: instanceName, operatedBy } = instanceInformation;
const { t } = useI18n();

const disclaimerText = computed(() =>
    mainFooter.value.disclaimerText ? mainFooter.value.disclaimerText.split('\n') : []
);
   
</script>

<!-- DO NOT FORMAT -->
<template>
    <footer class="flex flex-column align-items-center py-8 gap-4 kern-text main-footer">
        <div class="top-border"></div>
        <div class="">
            <img
                class="kern-logo w-16rem h-auto"
                :src="logoUrl"
                :alt="t('public.footer.logoAlt', { name: instanceName })"
            />
        </div>

        <!-- Text with links -->
        <div class="text-center mb-4 px-3 leading-relaxed max-w-3xl col-11 lg:col-10 xl:col-8">
            <span class="block">
                <span v-if="instanceName">{{ instanceName }}</span>
                {{ $t('public.footer.isInstanceOf') }}
                <a
                    href="https://der-smarte-gemeinschaftskalender.de/"
                    target="_blank"
                    class="underline"
                >{{ $t('public.footer.smartCommunityCalendar') }}</a>{{ $t('public.footer.operatedBy', { operatedBy }) }}
            </span>

            <span class="block">
                {{ $t('public.footer.basedOn') }}
                <a
                    href="https://mobilizon.org/"
                    target="_blank"
                    class="underline"
                >Mobilizon</a>
                {{ $t('public.footer.inFediverseMaintainedBy') }}
                <a
                    href="https://54gradsoftware.de/"
                    target="_blank"
                    class="underline"
                >54 Grad Software GmbH</a>{{ $t('public.footer.maintained') }}
                <div>
                    {{ $t('public.footer.developmentFundedIn') }}
                    <a
                        href="https://gitlab.opencode.de/sh/digitalhub-sh/landesprogramm-offene-innovationen"
                        target="_blank"
                    >
                        {{ $t('public.footer.openInnovationProgram') }}
                    </a>
                    {{ $t('public.footer.coFunded') }}

                </div>
                <div
                    v-if="disclaimerText.length"
                >
                    <p
                        v-for="(line, index) in disclaimerText"
                        :key="index"
                        class="d-block text-lg"
                    >
                        {{ line }}
                    </p>
                </div>
            </span>
        </div>
        <section class="links flex flex-wrap justify-content-center gap-2 col-11 xl:col-10">
            <a
                href="https://der-smarte-gemeinschaftskalender.de/"
                target="_blank"
                class="text-center"
            >{{ $t('public.footer.aboutCalendar') }}</a>

            <RouterLink
                :to="{ name: 'public.terms' }"
                class="text-center text-black-alpha-90"
            >
                {{ $t('public.terms.title') }}
            </RouterLink>

            <RouterLink
                :to="{ name: 'public.imprint' }"
                class="text-center"
            >
                {{ $t('public.imprint.title') }}
            </RouterLink>

            <RouterLink
                :to="{ name: 'public.privacy' }"
                class="text-center"
            >
                {{ $t('public.privacy.title') }}
            </RouterLink>

            <a
                href="https://github.com/Der-Smarte-Gemeinschaftskalender/der-smarte-gemeinschaftskalender/blob/main/LICENSE"
                class="text-center"
            >
                {{ $t('public.footer.licenseInfo') }}
            </a>

            <RouterLink
                :to="{ name: 'public.imprint', hash: '#accessibility' }"
                class="text-center"
            >
                {{ $t('public.footer.accessibilityStatement') }}
            </RouterLink>
        </section>
    </footer>
</template>
<style scoped lang="scss">
footer {
    position: relative;
    width: 100vw;
    background-color: #f4fdfb;

    a {
        color: #111;
        text-decoration: underline;
    }

    .top-border {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 0.5rem;
        background-image: linear-gradient(to right, #a9d3a9, #7dcce8);
    }

    p {
        font-size: 1rem;
        line-height: 1.5;
        color: #111;
    }

    .links a {
        padding: 0.5rem 1.5rem;
        font-size: 1rem;
        line-height: 1.5;
    }
}
</style>
