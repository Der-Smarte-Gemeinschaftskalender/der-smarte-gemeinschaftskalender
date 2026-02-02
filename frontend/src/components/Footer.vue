<script setup lang="ts">
const logoUrl = '/logo.png';
import { instanceInformation, mainFooter } from '@/lib/instanceConfig';

const { name: instanceName, operatedBy } = instanceInformation;
const { disclaimerText } = mainFooter;
</script>

<template>
    <footer class="flex flex-column align-items-center py-8 gap-4 kern-text main-footer">
        <div class="top-border"></div>
        <div class="">
            <img
                class="kern-logo w-16rem h-auto"
                :src="logoUrl"
                :alt="instanceName + ' Logo'"
            />
        </div>

        <!-- Text with links -->
        <div class="text-center mb-4 px-3 leading-relaxed max-w-3xl col-11 lg:col-10 xl:col-8">
            <span v-if="instanceName">{{ instanceName }}</span>
            ist eine Instanz des
            <a
                href="https://der-smarte-gemeinschaftskalender.de/"
                target="_blank"
                class="underline"
            >
                Smarten Gemeinschaftskalenders
            </a>
            , betrieben vom {{ operatedBy }}.
            <br />
            Der Kalender basiert auf
            <a
                href="https://mobilizon.org/"
                target="_blank"
                class="underline"
            >
                Mobilizon
            </a>
            im Fediverse und wird technisch von der
            <a
                href="https://54gradsoftware.de/"
                target="_blank"
                class="underline"
            >
                54 Grad Software GmbH
            </a>
            betreut.
            <div>
                Seine Entwicklung wurde im
                <a
                    href="https://gitlab.opencode.de/sh/digitalhub-sh/landesprogramm-offene-innovationen"
                    target="_blank"
                >
                    Landesprogramm Offene Innovation in Schleswig-Holstein
                </a>
                mitfinanziert.
            </div>
            <div
                v-if="disclaimerText"
                v-html="disclaimerText"
            ></div>
        </div>
        <section class="links flex flex-wrap justify-content-center gap-2 col-11 xl:col-10">
            <a
                href="https://der-smarte-gemeinschaftskalender.de/"
                target="_blank"
                class="text-center"
            >
                Über den Kalender
            </a>

            <RouterLink
                :to="{ name: 'public.terms' }"
                class="text-center text-black-alpha-90"
            >
                Nutzungsbedingungen
            </RouterLink>

            <RouterLink
                :to="{ name: 'public.imprint' }"
                class="text-center"
            >
                Impressum
            </RouterLink>

            <RouterLink
                :to="{ name: 'public.privacy' }"
                class="text-center"
            >
                Datenschutz
            </RouterLink>

            <a
                href="https://github.com/Der-Smarte-Gemeinschaftskalender/der-smarte-gemeinschaftskalender/blob/main/LICENSE"
                class="text-center"
            >
                Lizenzhinweise
            </a>

            <RouterLink
                :to="{ name: 'public.imprint' }"
                class="text-center"
            >
                Erklärung zur Barrierefreiheit
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
