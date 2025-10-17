<script lang="ts" setup>
import { exportList } from '@/lib/exportList';
import Divider from '@/components/KERN/cosmetics/Divider.vue';
import Button from '@/components/KERN/Button.vue';
import Icon from '@/components/KERN/cosmetics/Icon.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';
</script>
<template>
    <Teleport to="#headerslot">
        <div class="mb-3 mt-4 sm:mb-4 sm:mt-5 md:my-6">
            <h1 class="kern-heading text-theme-primary">Benachrichtigungen abonnieren</h1>
            <h2 class="kern-heading font-semilight text-theme-primary">
                Bleiben Sie automatisch über anstehende Veranstaltungen informiert
            </h2>
        </div>
    </Teleport>
    <div>
        <p class="kern-text kern-text--large text-theme-primary mb-6">
            Dies ist ein kostenloser Service vom Amt Süderbrarup und lässt sich jederzeit unkompliziert abbestellen oder
            anpassen.
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
                            {{ exportItem.link.title }}
                        </Button>
                    </RouterLink>
                </div>
                <div class="kern-col-md-8">
                    <h3 class="kern-heading text-theme-primary">{{ exportItem.title }}</h3>
                    <template v-if="exportItem.customDescription === 'informationBoards'">
                        <p class="kern-text">
                            Zeigen Sie eine vorgefilterte Auswahl an Veranstaltungen automatisch und rotierend auf digitalen Infotafeln oder Bildschirmen an – z. B. in Rathäusern, Bürgerbüros oder Gemeinschaftshäusern.<br/><br/>
                            So funktioniert es:
                            <ol class="ml-5">
                                <li>Öffnen Sie die <b>Veranstaltungssuche</b> und stellen Sie Ihren gewünschten <b>Filter</b> ein (z. B. Kategorien oder Zeiträume wie heute, morgen oder nächste Woche).</li>
                                <li>Klicken Sie oben rechts auf das <Icon name="info"/> Info-Icon im Bereich "Suchergebnis teilen", um eine passende URL zu erzeugen.</li>
                                <li>Kopieren Sie die URL und hinterlegen Sie diese direkt auf einer Infotafel oder einem Bildschirm.</li>
                            </ol>
                            <br/>
                            <b>Tipp:</b> Sie können auch spezifisch für eine Organisation eine Infotafel-Ansicht erstellen – direkt über die öffentliche Seite der jeweiligen Organisation. Auch dort finden Sie ein Info-Icon.
                            <br/><br/>
                            Die Anzeige läuft automatisch durch und zeigt Ihre gefilterten Veranstaltungen fortlaufend im Wechsel an – immer aktuell und ohne zusätzliche Pflege.
                            <br/><br/>
                            Weitere Informationen finden Sie im <LinkToDocs path=""/>.
                        </p>
                    </template>
                    <p
                        class="kern-text"
                        style="white-space: pre-line"
                        v-else
                    >
                        {{ exportItem.description }}
                    </p>
                </div>
            </div>
            <Divider
                class="mt-6 mb-8"
                v-if="index !== exportList.length - 1"
            />
        </div>
    </div>
</template>
