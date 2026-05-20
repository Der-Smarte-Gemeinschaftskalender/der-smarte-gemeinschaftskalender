<script setup lang="ts">
import { ref, computed } from 'vue';
import InputSelect from '@/components/KERN/inputs/InputSelect.vue';
import InputText from '@/components/KERN/inputs/InputText.vue';
import InputDate from '@/components/KERN/inputs/InputDate.vue';
import InputColor from '@/components/KERN/inputs/InputColor.vue';
import InputCheckbox from '@/components/KERN/inputs/InputCheckbox.vue';
import InputRadios from '@/components/KERN/inputs/InputRadios.vue';
import InputFile from '@/components/KERN/inputs/InputFile.vue';
import Button from '@/components/KERN/Button.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import Divider from '@/components/KERN/cosmetics/Divider.vue';
import Dialog from '@/components/KERN/Dialog.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';
import { fontSelectionOptions } from '@/lib/const';
import { formatOnMonthDayTime } from '@/lib/helper';
import { dimensionOptions, type MgTemplate, type CalendarEvent } from '@/composables/MaterialGenerator/materialGeneratorConstants';

const props = defineProps<{
    selectedDimension: string;
    selectedDimensionWidth?: number;
    selectedDimensionHeight?: number;
    selectedDimensionPreview: string | null;
    materialType: 'event' | 'eventList';
    maxEvents: number;
    fromDate: string;
    toDate: string;
    loadOrganisationLogo: boolean;
    selectedFont: string;
    selectedTextColor: string;
    underlineColor: string;
    headlineText: string;
    selectedFontHeadline: string;
    selectedTextColorHeadline: string;
    availableTemplates: MgTemplate[];
    allFetchedEvents: CalendarEvent[];
    selectedEventUuids: Set<string>;
    hasMoreEvents: boolean;
    loadingMoreEvents: boolean;
    isInstanceAdmin: boolean;
    instanceMode: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:selectedDimension', val: string): void;
    (e: 'update:fromDate', val: string): void;
    (e: 'update:toDate', val: string): void;
    (e: 'update:loadOrganisationLogo', val: boolean): void;
    (e: 'update:selectedFont', val: string): void;
    (e: 'update:selectedTextColor', val: string): void;
    (e: 'update:underlineColor', val: string): void;
    (e: 'update:headlineText', val: string): void;
    (e: 'update:selectedFontHeadline', val: string): void;
    (e: 'update:selectedTextColorHeadline', val: string): void;
    (e: 'update:instanceMode', val: boolean): void;
    (e: 'download'): void;
    (e: 'bgFileChange', file: File): void;
    (e: 'saveTemplate', name: string): void;
    (e: 'loadTemplate', id: number): void;
    (e: 'deleteTemplate', id: number): void;
    (e: 'toggleEvent', uuid: string): void;
    (e: 'selectAllEvents', selectAll: boolean): void;
    (e: 'loadMoreEvents'): void;
}>();

const bgFileName = ref('');

const isCanvas = computed(() => props.selectedDimensionPreview === 'canvas');

const newTemplateName = ref('');
const showDeleteDialog = ref(false);
const templateToDeleteId = ref<number | null>(null);
const templateToDeleteName = computed(() =>
    props.availableTemplates.find(t => t.id === templateToDeleteId.value)?.name ?? '',
);

const showEventPicker = ref(false);
const allEventsSelected = computed(() =>
    props.allFetchedEvents.length > 0 &&
    props.allFetchedEvents.every((e) => props.selectedEventUuids.has(e.uuid)),
);
const selectedCount = computed(() =>
    props.allFetchedEvents.filter((e) => props.selectedEventUuids.has(e.uuid)).length,
);

function onBgFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;
    bgFileName.value = file.name;
    emit('bgFileChange', file);
}

const localDimension = computed({
    get: () => props.selectedDimension,
    set: (v) => emit('update:selectedDimension', v),
});
const localInstanceMode = computed({
    get: () => props.instanceMode,
    set: (v) => emit('update:instanceMode', v),
});
const localFromDate = computed({
    get: () => props.fromDate,
    set: (v) => emit('update:fromDate', v),
});
const localToDate = computed({
    get: () => props.toDate,
    set: (v) => emit('update:toDate', v),
});
const localLoadLogo = computed({
    get: () => props.loadOrganisationLogo,
    set: (v) => emit('update:loadOrganisationLogo', v),
});
const localFont = computed({
    get: () => props.selectedFont,
    set: (v) => emit('update:selectedFont', v),
});
const localTextColor = computed({
    get: () => props.selectedTextColor,
    set: (v) => emit('update:selectedTextColor', v),
});
const localUnderlineColor = computed({
    get: () => props.underlineColor,
    set: (v) => emit('update:underlineColor', v),
});
const localHeadline = computed({
    get: () => props.headlineText,
    set: (v) => emit('update:headlineText', v),
});
const localFontHeadline = computed({
    get: () => props.selectedFontHeadline,
    set: (v) => emit('update:selectedFontHeadline', v),
});
const localTextColorHeadline = computed({
    get: () => props.selectedTextColorHeadline,
    set: (v) => emit('update:selectedTextColorHeadline', v),
});

function onSaveTemplate() {
    const name = newTemplateName.value.trim();
    if (!name) return;
    emit('saveTemplate', name);
    newTemplateName.value = '';
}

function onLoadTemplate(id: number) {
    emit('loadTemplate', id);
}

function confirmDeleteTemplate(id: number) {
    templateToDeleteId.value = id;
    showDeleteDialog.value = true;
}

function onDeleteTemplate() {
    if (templateToDeleteId.value !== null) {
        emit('deleteTemplate', templateToDeleteId.value);
    }
    showDeleteDialog.value = false;
    templateToDeleteId.value = null;
}
</script>

<template>
    <aside class="kern-card mg-sidebar">
        <div class="flex flex-column gap-2 p-3">
            <Button
                variant="primary"
                label="Herunterladen"
                icon-left="download"
                class="w-full"
                @click="emit('download')"
            />

            <Divider />

            <Fieldset legend="Format">
                <InputSelect
                    v-model="localDimension"
                    name="dimensionOptions"
                    :options="dimensionOptions"
                />
            </Fieldset>

            <template v-if="isInstanceAdmin && materialType === 'eventList'">
                <Divider />

                <Fieldset legend="Bereich">
                    <InputRadios
                        v-model="localInstanceMode"
                        name="instanceMode"
                        :radios="[
                            { label: 'Meine Organisation', value: false },
                            { label: 'Gesamte Instanz', value: true },
                        ]"
                    />
                </Fieldset>
            </template>

            <template v-if="materialType === 'eventList'">
                <Divider />

                <Fieldset legend="Zeitraum">
                    <div class="flex flex-column gap-2">
                        <InputDate v-model="localFromDate" name="fromDate" label="Startdatum" />
                        <InputDate v-model="localToDate" name="toDate" label="Enddatum" />
                    </div>
                    <p class="kern-form-input__hint mt-1" style="white-space: normal; word-break: break-word;">
                        <b>Hinweis:</b> Es können maximal {{ maxEvents }} Veranstaltungen angezeigt werden
                    </p>
                </Fieldset>

                <div v-if="allFetchedEvents.length">
                    <Divider />

                    <div class="flex align-items-center justify-content-between gap-2 mb-1 mt-1 min-w-0">
                        <span class="font-bold text-sm white-space-nowrap">Veranstaltungen</span>
                        <Button
                            variant="secondary"
                            :label="showEventPicker ? 'Schließen' : `Auswählen (${selectedCount}/${maxEvents})`"
                            class="shrink-0"
                            @click="showEventPicker = !showEventPicker"
                        />
                    </div>
                    <p v-if="selectedCount > maxEvents" class="kern-form-input__hint" style="color: var(--kern-warning, #e67700)">
                        Es werden nur die ersten {{ maxEvents }} ausgewählten Veranstaltungen auf der Grafik angezeigt.
                    </p>

                    <div v-if="showEventPicker" class="kern-card mt-1 p-2" style="max-height: 320px; overflow: hidden auto;">
                        <div class="mb-1">
                            <Button
                                variant="secondary"
                                :label="allEventsSelected ? 'Alle abwählen' : 'Alle auswählen'"
                                @click="emit('selectAllEvents', !allEventsSelected)"
                            />
                        </div>
                        <ul class="list-none m-0 p-0">
                            <li
                                v-for="evt in allFetchedEvents"
                                :key="evt.uuid"
                                class="mg-event-picker__item"
                                :class="{ 'opacity-50': !selectedEventUuids.has(evt.uuid) }"
                            >
                                <label class="flex align-items-start gap-2 py-1 px-1 cursor-pointer overflow-hidden">
                                    <input
                                        type="checkbox"
                                        :checked="selectedEventUuids.has(evt.uuid)"
                                        class="mt-1 shrink-0"
                                        @change="emit('toggleEvent', evt.uuid)"
                                    />
                                    <span class="flex flex-column min-w-0">
                                        <span class="font-medium text-sm text-overflow-ellipsis white-space-nowrap overflow-hidden">{{ evt.title }}</span>
                                        <span class="text-xs text-color-secondary">{{ formatOnMonthDayTime(evt.beginsOn) }}</span>
                                    </span>
                                </label>
                            </li>
                        </ul>
                        <Button
                            v-if="hasMoreEvents"
                            variant="secondary"
                            :label="loadingMoreEvents ? 'Laden …' : 'Weitere Veranstaltungen laden'"
                            class="w-full mt-2"
                            :disabled="loadingMoreEvents"
                            @click="emit('loadMoreEvents')"
                        />
                    </div>
                </div>
            </template>

            <template v-if="isCanvas">
                <Divider />

                <InputCheckbox
                    v-if="!(materialType === 'eventList' && instanceMode)"
                    v-model="localLoadLogo"
                    label="Logo anzeigen"
                    name="loadOrganisationLogo"
                />

                <Divider />

                <Fieldset legend="Hintergrundbild">
                    <InputFile
                        v-model="bgFileName"
                        name="bgFile"
                        accept="image/png, image/jpeg"
                        @change="onBgFileChange"
                    />
                    <p class="kern-form-input__hint mt-1">
                        <template v-if="selectedDimensionWidth && selectedDimensionHeight">
                            {{ selectedDimensionWidth }} × {{ selectedDimensionHeight }} px →
                        </template>
                        <LinkToDocs
                            path="Werbemittelgenerator/"
                            fragment="hintergrunde-fur-veranstaltungsubersichten-gestalten"
                        >
                            Layout-Vorlagen
                        </LinkToDocs>
                    </p>
                </Fieldset>

                <Divider />

                <Fieldset legend="Globale Einstellungen">
                    <div class="flex align-items-end gap-2">
                        <div class="flex-1 min-w-0">
                            <InputSelect
                                v-model="localFont"
                                name="selectedFont"
                                label="Schriftart Texte"
                                :options="fontSelectionOptions"
                            />
                        </div>
                        <InputColor v-model="localTextColor" name="selectedTextColor" label="Farbe" />
                    </div>

                    <template v-if="materialType === 'eventList'">
                        <div class="flex align-items-end gap-2 mt-2">
                            <div class="flex-1 min-w-0">
                                <InputSelect
                                    v-model="localFontHeadline"
                                    name="selectedFontHeadline"
                                    label="Schriftart Überschriften"
                                    :options="fontSelectionOptions"
                                />
                            </div>
                            <InputColor v-model="localTextColorHeadline" name="selectedTextColorHeadline" label="Farbe" />
                        </div>
                        <InputText
                            v-model="localHeadline"
                            label="Überschrift"
                            name="headlineText"
                            class="mt-2"
                        />
                    </template>

                    <InputColor v-model="localUnderlineColor" name="underlineColor" label="Trennlinien" class="mt-2" />
                </Fieldset>

                <Divider />

                <Fieldset legend="Vorlagen">
                    <div v-if="props.availableTemplates.length" class="flex flex-column gap-2">
                        <div
                            v-for="tpl in props.availableTemplates"
                            :key="tpl.id"
                            class="flex align-items-center gap-2"
                        >
                            <Button
                                variant="secondary"
                                :label="tpl.name"
                                :title="'Vorlage laden: ' + tpl.name"
                                class="flex-1 min-w-0"
                                @click="onLoadTemplate(tpl.id)"
                            />
                            <Button
                                variant="secondary"
                                icon-left="delete"
                                icon-size="sm"
                                title="Vorlage löschen"
                                aria-label="Vorlage löschen"
                                @click.stop="confirmDeleteTemplate(tpl.id)"
                            />
                        </div>
                    </div>
                    <p v-else class="kern-form-input__hint">
                        Keine Vorlagen für dieses Format gespeichert.
                    </p>

                    <div class="flex align-items-end gap-2 mt-3">
                        <div class="flex-1">
                            <InputText
                                v-model="newTemplateName"
                                label="Neue Vorlage speichern unter"
                                name="newTemplateName"
                            />
                        </div>
                        <Button
                            variant="primary"
                            label="Speichern"
                            @click="onSaveTemplate"
                        />
                    </div>
                </Fieldset>

                <Dialog v-model="showDeleteDialog" title="Vorlage löschen">
                    <p>
                        Vorlage „{{ templateToDeleteName }}" wirklich löschen?
                    </p>
                    <template #footer>
                        <div class="flex gap-2 justify-content-end">
                            <Button
                                variant="secondary"
                                label="Abbrechen"
                                @click="showDeleteDialog = false"
                            />
                            <Button
                                variant="primary"
                                label="Löschen"
                                @click="onDeleteTemplate"
                            />
                        </div>
                    </template>
                </Dialog>
            </template>
        </div>
    </aside>
</template>

<style scoped>
.mg-sidebar {
    align-items: stretch;
    overflow: hidden;
}

.mg-event-picker__item {
    border-bottom: 1px solid var(--kern-layout-border, #dfe7ef);
}
.mg-event-picker__item:last-child {
    border-bottom: none;
}
</style>
