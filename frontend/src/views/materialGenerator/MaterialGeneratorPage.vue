<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, computed, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchOrganisationEvents, findEvent, searchEvents as searchInstanceEvents } from '@/lib/mobilizonClient';
import { user } from '@/composables/UserComposoable';
import Loader from '@/components/KERN/cosmetics/Loader.vue';
import InputTextarea from '@/components/KERN/inputs/InputTextarea.vue';
import { formatOnMonthDayTime, getMainCategoryFromSubCategory, formatInputDate, normalizeStreet } from '@/lib/helper';
import Alert from '@/components/KERN/Alert.vue';
import dayjs from 'dayjs';
import { dsgApi } from '@/lib/dsgApi';
import { loadImage } from '@/lib/dsgClient';
import LinkToDocs from '@/components/LinkToDocs.vue';
import { createTextEventList, createTextSingleEvent } from '@/lib/shareInformation';
import { materialGeneratorDefaults } from '@/lib/instanceConfig';

import Legend from '@/components/Legend.vue';
import MaterialGeneratorToolbar from '@/components/MaterialGenerator/MaterialGeneratorToolbar.vue';
import MaterialGeneratorSidebar from '@/components/MaterialGenerator/MaterialGeneratorSidebar.vue';
import MaterialGeneratorContextMenu from '@/components/MaterialGenerator/MaterialGeneratorContextMenu.vue';
import { useFabricCanvas } from '@/composables/MaterialGenerator/useFabricCanvas';
import { useMgTemplates } from '@/composables/MaterialGenerator/useMaterialGeneratorTemplates';
import {
    dimensions, defaultBackgroundImagePaths,
    type CalendarEvent, type EventInfoPayload, type MgGlobalSettings,
} from '@/composables/MaterialGenerator/materialGeneratorConstants';

const route = useRoute();
// eslint-disable-next-line @typescript-eslint/no-unused-vars
const router = useRouter();

const loading = ref(false);
const canvasRendering = ref(false);
const showError = ref(false);
const errorMessage = ref('');

const materialType: 'event' | 'eventList' = route.name === 'materialGenerator.event' ? 'event' : 'eventList';

const selectedDimension = ref('story');
const selectedDimensionData = computed(() => dimensions[selectedDimension.value]);
const selectedDimensionInfoBox = computed(() => selectedDimensionData.value?.canvasData?.[materialType]);
const canvasWidth = computed(() => dimensions[selectedDimension.value].width);
const canvasHeight = computed(() => dimensions[selectedDimension.value].height);

const fromDate = ref(formatInputDate());
const toDate = ref(formatInputDate(new Date(dayjs().add(2, 'month').format('YYYY-MM-DD'))));
const headlineText = ref('Unsere Termine');
const selectedFontHeadline = ref('Arial');
const selectedTextColorHeadline = ref('#111111');
const formatTextOutput = ref('');
const loadOrganisationLogo = ref(false);
const uploadedImageBlob = ref<Blob | File | null>(null);

const isInstanceAdmin = computed(() => user.value?.type === 'admin');
const instanceMode = ref(false);

const organisationUsername = ref((route.params?.preferredUsername as string) ?? null);
const eventUuid = route.params?.uuid ?? null;
const PICKER_PAGE_SIZE = 100;
const allFetchedEvents = ref<CalendarEvent[]>([]);
const allFetchedEventsTotal = ref(0);
const pickerPage = ref(1);
const loadingMore = ref(false);
const selectedEventUuids = ref<Set<string>>(new Set());
const events = computed(() =>
    allFetchedEvents.value.filter((e) => selectedEventUuids.value.has(e.uuid)),
);
const hasMoreEvents = computed(() => allFetchedEvents.value.length < allFetchedEventsTotal.value);
const eventListData = ref();
const singleEventData = ref();
const eventMainCategory = ref('');
const organisationAvatarUrl = ref<string | null>(null);
const organisationAvatar = ref<File | null>(null);

const selectedFont = ref('Arial');
const underlineColor = ref(materialGeneratorDefaults.underlineColor || '#7dcce8');
const selectedTextColor = ref('#111111');
const defaultTextSize = 30;

const toolbarFillColor = ref('#7dcce8');
const toolbarTextColor = ref('#111111');
const toolbarFont = ref('Arial');

const canvas1 = ref(null);

const downloadFileName = computed(() => {
    if (materialType === 'eventList') {
        const scope = instanceMode.value ? 'Instanz' : organisationUsername.value;
        return `${selectedDimension.value}_Veranstaltungsuebersicht_${scope}_${fromDate.value}_${toDate.value}`;
    }
    return `${selectedDimension.value}_Veranstaltung_${eventUuid}`;
});

const {
    selFill, selTextColor, selFontFamily,
    selBold, selUnderline, selFontSize,
    canUndo, canRedo,
    hasTextSelected, hasShapeSelected,
    initFabricCanvas, disposeFabricCanvas,
    clearAutoObjects,
    addAutoText, addAutoLine, canvasClearAndLoadImage, drawAvatarCanvas,
    downloadFabricCanvas, addUserText, addUserRect, addUserCircle,
    addUserEllipse, addUserTriangle, addUserLine, addUserStar,
    addUserArrow, addUserHexagon, addUserImage,
    deleteSelected, duplicateSelected,
    bringForward, sendBackward, bringToFront, sendToBack,
    contextMenu, hideContextMenu,
    applySelFill, applySelTextColor, applySelFontFamily,
    applySelBold, applySelUnderline, applySelFontSize,
    applyStampFill, applyStampStroke,
    selStampFill, selStampStroke,
    hasStampSelected,
    addUserStamp,
    undo, redo,
    exportUserObjects, importUserObjects,
    exportBackgroundDataUrl, importBackgroundDataUrl,
    exportAutoOverrides, applyAutoOverrides,
    exportZOrder, applyZOrder,
} = useFabricCanvas(canvasWidth, canvasHeight, selectedDimensionInfoBox);

const { saveTemplate, deleteTemplate, getTemplate, listForContext, fetchTemplates, error: templateError } = useMgTemplates();

const availableTemplates = computed(() => listForContext(materialType, selectedDimension.value));

const editorFeatures: Record<string, string> = {
    'open-in-new': 'Objekte per Drag & Drop verschieben, skalieren und drehen',
    'image': 'Bild hochladen (PNG, JPEG, SVG)',
    'stamp': 'Stempel (Abgesagt, Verschoben, Aktualisiert)',
    'list_alt': 'Reihenfolge ändern (Ebenen via Rechtsklick im Menü nach vorne/hinten rücken)',
    'content-copy': 'Objekte duplizieren und löschen via Rechtsklick im Menü',
    'edit': 'Textanpassungen: Schriftart, Schriftgröße, Farben, Fett- und Unterstreichung',
    'undo': 'Bearbeitungen rückgängig machen und wiederherstellen',
    'drive-folder-upload': 'Hintergrundbild hochladen',
    'settings': 'Global Schrift, Farben und Trennlinien anpassen',
    'home_repair_service': 'Vorlagen speichern und wiederverwenden für konsistente Designs',
    'checklist': 'Manuelle Auswahl und Bearbeitung der Veranstaltungen',
    'download': 'Grafik als PNG-Datei herunterladen',
};

const infoBoxStartX = computed(() => selectedDimensionInfoBox.value?.infoBoxStartX ?? 0);
const infoBoxStartY = computed(() => selectedDimensionInfoBox.value?.infoBoxStartY ?? 0);
const infoBoxWidth = computed(() => selectedDimensionInfoBox.value?.infoBoxWidth ?? 0);

function autoText(text: string, left: number, top: number, opts: { fontWeight?: string; fontSize?: number; fontFamily?: string; fill?: string; width?: number } = {}, autoKey?: string) {
    addAutoText(text, left, top, opts, defaultTextSize, selectedFont.value, selectedTextColor.value, autoKey);
}
function autoLine(left: number, top: number, width: number, height = 5, autoKey?: string) {
    addAutoLine(left, top, width, underlineColor.value, height, autoKey);
}

const eventLimit = computed(() => selectedDimensionData.value?.maxEvents ?? 10);

const eventListInfo = (evts: CalendarEvent[]) => {
    drawTextHeadline();
    const maxEvts = eventLimit.value;
    const useOneColumn = evts.length <= Math.floor(maxEvts / 2);
    const divideInfoBox = useOneColumn ? 1 : 2;
    const col1X = infoBoxStartX.value + 20;
    const col2X = 550;
    const slotHeight = defaultTextSize * 2 + defaultTextSize + 55;

    const evtsPerColumn = useOneColumn ? evts.length : Math.ceil(maxEvts / 2);
    let index = 0;

    for (let row = 0; row < evtsPerColumn; row++) {
        if (!evts[index]) break;
        const rowY = infoBoxStartY.value + row * slotHeight;
        eventListSingleEventText(evts[index], rowY, col1X, divideInfoBox, index);
        index++;
    }
    if (!useOneColumn) {
        for (let row = 0; row < evtsPerColumn; row++) {
            if (!evts[index]) break;
            const rowY = infoBoxStartY.value + row * slotHeight;
            eventListSingleEventText(evts[index], rowY, col2X, divideInfoBox, index);
            index++;
        }
    }
    formatTextOutput.value = createTextEventList(headlineText.value, evts as unknown as Parameters<typeof createTextEventList>[1]);
};

const eventInfo = (event: EventInfoPayload) => {
    let newStartY = infoBoxStartY.value + 32;
    const startX = infoBoxStartX.value + 56;
    newStartY += 50 + 64;
    const textSizeHeadline = 62;
    const titleWidth = infoBoxWidth.value - 50;
    autoText(event.title, startX, newStartY, { fontSize: textSizeHeadline, width: titleWidth }, 'single_title');
    newStartY = infoBoxStartY.value + 384;
    autoLine(infoBoxStartX.value + 20, newStartY, titleWidth, 5, 'single_line');
    newStartY += 78;
    autoText(formatOnMonthDayTime(event.beginsOn), startX, newStartY, { fontSize: 48, width: titleWidth }, 'single_date');
    newStartY += defaultTextSize + 66;
    autoText(event.organisation.name, startX, newStartY, { width: titleWidth }, 'single_org_name');
    if (event.physicalAddress) {
        newStartY += defaultTextSize;
        autoText(normalizeStreet(event?.physicalAddress?.street ?? ''), startX, newStartY, { width: titleWidth }, 'single_org_street');
        newStartY += defaultTextSize;
        autoText(`${event?.physicalAddress?.postalCode} ${event?.physicalAddress?.locality}`, startX, newStartY, { width: titleWidth }, 'single_org_city');
    }
    formatTextOutput.value = createTextSingleEvent(event as unknown as Parameters<typeof createTextSingleEvent>[0], true, false);
};

const drawTextHeadline = () => {
    autoText(headlineText.value, 40, selectedDimensionInfoBox.value?.headlineStartY || 0, {
        fontWeight: 'bold', fontSize: defaultTextSize * 2,
        fontFamily: selectedFontHeadline.value, fill: selectedTextColorHeadline.value,
        width: infoBoxWidth.value - 80,
    }, 'headline');
};

const eventListOrganisation = (name: string, physicalAddress: { street?: string; postalCode?: string; locality?: string } | null) => {
    const orgWidth = infoBoxWidth.value - 80;
    let newStartY = selectedDimensionInfoBox.value?.organisationInfoStartY || 0;
    autoText(name, 40, newStartY, { fontWeight: 'bold', width: orgWidth }, 'org_name');
    newStartY += defaultTextSize + 48;
    autoText(normalizeStreet(physicalAddress?.street ?? '') || '', 40, newStartY, { width: orgWidth }, 'org_street');
    newStartY += defaultTextSize + 10;
    autoText(`${physicalAddress?.postalCode || ''} ${physicalAddress?.locality || ''}`, 40, newStartY, { width: orgWidth }, 'org_city');
};

const truncateText = (text: string, maxLength = 50): string =>
    text.length > maxLength ? text.slice(0, maxLength) + '…' : text;

// "title – org" soll auf eine Zeile passen (~45 Zeichen).
// Titel bekommt Vorrang (max 35 Zeichen), Organisation mind. 10 Zeichen.
const INSTANCE_LABEL_TOTAL = 45;
const INSTANCE_LABEL_MIN_ORG = 10;

const buildInstanceLabel = (title: string, orgName: string): string => {
    const truncatedTitle = truncateText(title, INSTANCE_LABEL_TOTAL - INSTANCE_LABEL_MIN_ORG);
    const orgBudget = Math.max(INSTANCE_LABEL_TOTAL - truncatedTitle.length, INSTANCE_LABEL_MIN_ORG);
    return `${truncatedTitle} – ${truncateText(orgName, orgBudget)}`;
};

const eventListSingleEventText = (event: CalendarEvent, startY: number, startX: number, divideInfoBox = 2, eventIndex = 0): void => {
    const titleY = startY + 5 + defaultTextSize + 15;
    const columnWidth = infoBoxWidth.value / divideInfoBox - 50;
    const label = instanceMode.value && event.organisationName
        ? buildInstanceLabel(event.title, event.organisationName)
        : truncateText(event.title, 50);
    autoText(label, startX, titleY, { width: columnWidth }, `evt_${eventIndex}_title`);
    const dateY = titleY + defaultTextSize * 2 + 15;
    autoText(formatOnMonthDayTime(event.beginsOn), startX, dateY, { width: columnWidth }, `evt_${eventIndex}_date`);
    const lineY = dateY + defaultTextSize + 10;
    autoLine(startX, lineY, columnWidth, 5, `evt_${eventIndex}_line`);
};

const loadDefaultValues = async (groupId: string | null = null, mobilizonPreferredUsername: string | null = null) => {
    try {
        let params = {};
        if (groupId) params = { mobilizon_group_id: groupId };
        else if (mobilizonPreferredUsername) params = { mobilizon_preferredusername: mobilizonPreferredUsername };
        const { data } = await dsgApi.get('material-generator-values', { params });
        if (!data) return;
        if (data.default_text_settings) {
            underlineColor.value = data.default_text_settings.underlineColor || '#7dcce8';
            selectedTextColor.value = data.default_text_settings.selectedTextColor || '#000000';
            selectedFont.value = data.default_text_settings.selectedFont || 'Arial';
        }
        if (data.default_header_settings) {
            headlineText.value = data.default_header_settings.headlineText || 'Unsere Termine';
            selectedFontHeadline.value = data.default_header_settings.selectedFontHeadline || 'Arial';
            selectedTextColorHeadline.value = data.default_header_settings.selectedTextColorHeadline || '#000000';
        }
        if (!mobilizonPreferredUsername) mobilizonPreferredUsername = data.mobilizon_preferredusername || null;
        uploadedImageBlob.value = await loadImage('material-generator-values/image', {
            path: `${mobilizonPreferredUsername}/event${materialType === 'eventList' ? 'List' : ''}${selectedDimension.value === 'story' ? 'Story' : 'Post'}.png`,
        });
        canvasClearAndLoadImage(uploadedImageBlob, uploadedImageBlob.value, materialType);
    } catch {
        showError.value = true;
        errorMessage.value = 'Standardwerte konnten nicht geladen werden.';
    }
};

let _isRedrawing = false;
let _pendingRedraw: { withoutClear: boolean; positionOnlyOverrides: boolean } | null = null;
let _suppressWatcherRedraw = false;

const displayCanvaData = async (withoutClear = false, positionOnlyOverrides = false) => {
    if (loading.value) return;

    if (_isRedrawing) {
        _pendingRedraw = { withoutClear, positionOnlyOverrides };
        return;
    }

    _isRedrawing = true;
    canvasRendering.value = true;

    try {
        const overrides = exportAutoOverrides();

        if (!withoutClear) {
            await canvasClearAndLoadImage(uploadedImageBlob, null, materialType);
        } else {
            clearAutoObjects();
        }

        if (materialType === 'eventList') {
            eventListInfo(events.value);
            if (!instanceMode.value) {
                eventListOrganisation(eventListData.value?.name || '', eventListData.value?.physicalAddress || '');
                organisationAvatar.value = await loadImage('organisations/avatar', { imageUrl: organisationAvatarUrl.value });
                await drawAvatarCanvas(loadOrganisationLogo, organisationAvatar);
            }
        } else {
            eventMainCategory.value = getMainCategoryFromSubCategory(singleEventData.value?.category)?.value ?? '';
            if (!withoutClear) await canvasClearAndLoadImage(uploadedImageBlob, null, materialType);
            eventInfo({
                title: singleEventData.value.title,
                beginsOn: singleEventData.value.beginsOn,
                physicalAddress: singleEventData.value?.physicalAddress,
                organisation: singleEventData.value.attributedTo,
                uuid: singleEventData.value.uuid,
            });
            organisationAvatar.value = await loadImage('organisations/avatar', { imageUrl: organisationAvatarUrl.value });
            await drawAvatarCanvas(loadOrganisationLogo, organisationAvatar);
        }

        if (Object.keys(overrides).length) {
            applyAutoOverrides(overrides, positionOnlyOverrides);
        }
    } finally {
        _isRedrawing = false;
        canvasRendering.value = false;
    }

    if (_pendingRedraw) {
        const pending = _pendingRedraw;
        _pendingRedraw = null;
        await nextTick();
        await displayCanvaData(pending.withoutClear, pending.positionOnlyOverrides);
    }
};

const fetchInstanceEvents = async (page: number) => {
    const result = await searchInstanceEvents(
        page,
        PICKER_PAGE_SIZE,
        '',
        'INTERNAL',
        new Date(fromDate.value).toISOString(),
        new Date(toDate.value).toISOString(),
    );
    const elements = (result?.searchEvents?.elements ?? []).map((event: { title: string; beginsOn: string; endsOn: string; uuid: string; attributedTo?: { name?: string } }) => ({
        title: event.title, beginsOn: event.beginsOn, endsOn: event.endsOn, uuid: event.uuid,
        organisationName: event.attributedTo?.name ?? '',
    }));
    return { elements, total: result?.searchEvents?.total ?? 0 };
};

const loadData = async (withoutClear = false) => {
    showError.value = false;
    try {
        loading.value = true;
        if (materialType === 'eventList') {
            if (instanceMode.value) {
                const { elements: fetched, total } = await fetchInstanceEvents(1);
                allFetchedEvents.value = fetched;
                allFetchedEventsTotal.value = total;
                pickerPage.value = 1;
                selectedEventUuids.value = new Set(
                    fetched.slice(0, eventLimit.value).map((e: CalendarEvent) => e.uuid),
                );
                if (!fetched.length) {
                    showError.value = true;
                    errorMessage.value = 'Es wurden keine Veranstaltungen gefunden.';
                    return;
                }
                organisationAvatarUrl.value = null;
                eventListData.value = null;
            } else {
                organisationUsername.value = route.params?.preferredUsername as string;
                if (!organisationUsername.value) {
                    showError.value = true;
                    errorMessage.value = 'Keine Organisation ausgewählt.';
                    return;
                }
                if (typeof Number(organisationUsername.value) === 'number') {
                    const { data } = await dsgApi.get('organisations/group', {
                        params: { group_id: route.params?.id || route.params?.preferredUsername },
                    });
                    organisationUsername.value = data.preferredUsername || route.params?.preferredUsername;
                }
                await loadDefaultValues(null, organisationUsername.value);
                const { elements, total, name, physicalAddress, avatar } = await fetchOrganisationEvents(
                    organisationUsername.value as string,
                    new Date(fromDate.value).toISOString(),
                    new Date(toDate.value).toISOString(),
                    PICKER_PAGE_SIZE,
                    1,
                );
                const fetched = elements.map((event: CalendarEvent) => ({
                    title: event.title, beginsOn: event.beginsOn, endsOn: event.endsOn, uuid: event.uuid,
                }));
                allFetchedEvents.value = fetched;
                allFetchedEventsTotal.value = total;
                pickerPage.value = 1;
                selectedEventUuids.value = new Set(
                    fetched.slice(0, eventLimit.value).map((e: CalendarEvent) => e.uuid),
                );
                if (!fetched.length) {
                    showError.value = true;
                    errorMessage.value = 'Es wurden keine Veranstaltungen gefunden.';
                    return;
                }
                organisationAvatarUrl.value = avatar?.url || null;
                eventListData.value = { name, physicalAddress };
            }
        } else {
            if (!route.params?.uuid) {
                showError.value = true;
                errorMessage.value = 'Keine Veranstaltung ausgewählt.';
                return;
            }
            const response = await findEvent(route.params?.uuid as string);
            singleEventData.value = response;
            organisationAvatarUrl.value = singleEventData.value?.attributedTo?.avatar?.url;
            await loadDefaultValues(
                singleEventData.value.attributedTo.id,
                singleEventData.value?.attributedTo?.preferredUsername,
            );
        }
    } catch {
        showError.value = true;
        errorMessage.value = 'Inhalte konnten nicht geladen werden.';
    } finally {
        loading.value = false;
        await displayCanvaData(withoutClear);
    }
};

const refetchEvents = async () => {
    if (materialType !== 'eventList') return;
    if (!instanceMode.value && !organisationUsername.value) return;
    showError.value = false;
    try {
        let fetched: CalendarEvent[];
        let total: number;
        if (instanceMode.value) {
            ({ elements: fetched, total } = await fetchInstanceEvents(1));
        } else {
            const response = await fetchOrganisationEvents(
                organisationUsername.value as string,
                new Date(fromDate.value).toISOString(),
                new Date(toDate.value).toISOString(),
                PICKER_PAGE_SIZE,
                1,
            );
            total = response.total;
            fetched = response.elements.map((event: CalendarEvent) => ({
                title: event.title, beginsOn: event.beginsOn, endsOn: event.endsOn, uuid: event.uuid,
            }));
        }
        allFetchedEvents.value = fetched;
        allFetchedEventsTotal.value = total;
        pickerPage.value = 1;
        selectedEventUuids.value = new Set(
            fetched.slice(0, eventLimit.value).map((e: CalendarEvent) => e.uuid),
        );
        if (!fetched.length) {
            showError.value = true;
            errorMessage.value = 'Es wurden keine Veranstaltungen gefunden.';
            return;
        }
    } catch {
        showError.value = true;
        errorMessage.value = 'Veranstaltungen konnten nicht geladen werden.';
        return;
    }
    await displayCanvaData(true);
};

function onToggleEvent(uuid: string) {
    const s = new Set(selectedEventUuids.value);
    if (s.has(uuid)) s.delete(uuid);
    else s.add(uuid);
    selectedEventUuids.value = s;
    displayCanvaData(true);
}

function onSelectAllEvents(selectAll: boolean) {
    if (selectAll) {
        selectedEventUuids.value = new Set(allFetchedEvents.value.map((e) => e.uuid));
    } else {
        selectedEventUuids.value = new Set();
    }
    displayCanvaData(true);
}

async function onLoadMoreEvents() {
    if (loadingMore.value || !hasMoreEvents.value) return;
    if (!instanceMode.value && !organisationUsername.value) return;
    loadingMore.value = true;
    try {
        const nextPage = pickerPage.value + 1;
        let newEvents: CalendarEvent[];
        if (instanceMode.value) {
            ({ elements: newEvents } = await fetchInstanceEvents(nextPage));
        } else {
            const { elements } = await fetchOrganisationEvents(
                organisationUsername.value as string,
                new Date(fromDate.value).toISOString(),
                new Date(toDate.value).toISOString(),
                PICKER_PAGE_SIZE,
                nextPage,
            );
            newEvents = elements.map((event: CalendarEvent) => ({
                title: event.title, beginsOn: event.beginsOn, endsOn: event.endsOn, uuid: event.uuid,
            }));
        }
        allFetchedEvents.value = [...allFetchedEvents.value, ...newEvents];
        pickerPage.value = nextPage;
    } catch {
        showError.value = true;
        errorMessage.value = 'Weitere Veranstaltungen konnten nicht geladen werden.';
    } finally {
        loadingMore.value = false;
    }
}

const MAX_FILE_SIZE = 2 * 1024 * 1024; // 2 MB

const onBgFileChange = async (file: File) => {
    if (file.size > MAX_FILE_SIZE) {
        showError.value = true;
        errorMessage.value = 'Das Hintergrundbild ist zu groß. Bitte wählen Sie ein Bild mit maximal 2 MB.';
        return;
    }
    showError.value = false;
    uploadedImageBlob.value = file;
    await canvasClearAndLoadImage(uploadedImageBlob, file, materialType);
    await new Promise((r) => setTimeout(r, 500));
    displayCanvaData();
};

async function onAddImage(files: File[]) {
    try {
        showError.value = false;
        await addUserImage(files);
    } catch (err: unknown) {
        showError.value = true;
        errorMessage.value = err instanceof Error ? err.message : 'Bild konnte nicht hinzugefügt werden.';
    }
}

function onDownload() {
    downloadFabricCanvas(downloadFileName.value);
}

function collectGlobalSettings(): MgGlobalSettings {
    return {
        selectedFont: selectedFont.value,
        selectedTextColor: selectedTextColor.value,
        underlineColor: underlineColor.value,
        headlineText: headlineText.value,
        selectedFontHeadline: selectedFontHeadline.value,
        selectedTextColorHeadline: selectedTextColorHeadline.value,
        loadOrganisationLogo: loadOrganisationLogo.value,
        selectedEventCount: selectedEventUuids.value.size,
    };
}

function applyGlobalSettings(gs: MgGlobalSettings) {
    selectedFont.value = gs.selectedFont;
    selectedTextColor.value = gs.selectedTextColor;
    underlineColor.value = gs.underlineColor;
    headlineText.value = gs.headlineText;
    selectedFontHeadline.value = gs.selectedFontHeadline;
    selectedTextColorHeadline.value = gs.selectedTextColorHeadline;
    loadOrganisationLogo.value = gs.loadOrganisationLogo;

    if (materialType === 'eventList' && gs.selectedEventCount != null && allFetchedEvents.value.length > 0) {
        const count = Math.min(gs.selectedEventCount, allFetchedEvents.value.length, eventLimit.value);
        selectedEventUuids.value = new Set(
            allFetchedEvents.value.slice(0, count).map((e) => e.uuid),
        );
    }
}

async function onSaveTemplate(name: string) {
    await saveTemplate({
        name,
        material_type: materialType,
        dimension: selectedDimension.value,
        global_settings: collectGlobalSettings(),
        objects_data: {
            userObjects: exportUserObjects(),
            backgroundImageDataUrl: exportBackgroundDataUrl(),
            autoOverrides: exportAutoOverrides(),
            zOrder: exportZOrder(),
        },
    });
}

async function onLoadTemplate(id: number) {
    const tpl = getTemplate(id);
    if (!tpl) return;

    _suppressWatcherRedraw = true;
    applyGlobalSettings(tpl.global_settings);
    await nextTick();
    _suppressWatcherRedraw = false;

    if (tpl.objects_data.backgroundImageDataUrl) {
        await importBackgroundDataUrl(tpl.objects_data.backgroundImageDataUrl);
    } else {
        await canvasClearAndLoadImage(uploadedImageBlob, null, materialType);
    }

    clearAutoObjects();

    await displayCanvaData(true);

    if (tpl.objects_data.autoOverrides) {
        applyAutoOverrides(tpl.objects_data.autoOverrides);
    }

    await importUserObjects(tpl.objects_data.userObjects);

    if (tpl.objects_data.zOrder) {
        applyZOrder(tpl.objects_data.zOrder);
    }
}

async function onDeleteTemplate(id: number) {
    await deleteTemplate(id);
}

function onAddShape(shape: string) {
    const fill = toolbarFillColor.value;
    const map: Record<string, (f: string) => void> = {
        rect: addUserRect, circle: addUserCircle, ellipse: addUserEllipse,
        triangle: addUserTriangle, line: addUserLine, star: addUserStar,
        arrow: addUserArrow, hexagon: addUserHexagon,
    };
    map[shape]?.(fill);
}

function onAddText() {
    addUserText(toolbarFont.value, toolbarTextColor.value);
}

import type { StampOption } from '@/components/MaterialGenerator/MaterialGeneratorToolbar.vue';

import abgesagtSvg from '@/components/MaterialGenerator/svgs/AbgesagtBadge.svg?raw';
import verschobenSvg from '@/components/MaterialGenerator/svgs/VerschobenBadge.svg?raw';
import aktualisiertSvg from '@/components/MaterialGenerator/svgs/AktualisiertBadge.svg?raw';

const stampSvgMap: Record<string, string> = {
    'AbgesagtBadge.svg': abgesagtSvg,
    'VerschobenBadge.svg': verschobenSvg,
    'AktualisiertBadge.svg': aktualisiertSvg,
};

const stampOptions: StampOption[] = [
    { label: 'Abgesagt', file: 'AbgesagtBadge.svg' },
    { label: 'Verschoben', file: 'VerschobenBadge.svg' },
    { label: 'Aktualisiert', file: 'AktualisiertBadge.svg' },
];

async function onAddStamp(file: string) {
    const svgString = stampSvgMap[file];
    if (!svgString) {
        showError.value = true;
        errorMessage.value = 'Stempel konnte nicht geladen werden.';
        return;
    }
    try {
        await addUserStamp(svgString);
    } catch {
        showError.value = true;
        errorMessage.value = 'Stempel konnte nicht geladen werden.';
    }
}

watch(selFill, applySelFill);
watch(selTextColor, applySelTextColor);
watch(selFontFamily, applySelFontFamily);
watch(selBold, applySelBold);
watch(selUnderline, applySelUnderline);
watch(selFontSize, applySelFontSize);
watch(selStampFill, applyStampFill);
watch(selStampStroke, applyStampStroke);

onMounted(() => {
    initFabricCanvas();
    loadData();
    fetchTemplates();
});
onBeforeUnmount(() => {
    disposeFabricCanvas();
});

watch(selectedDimension, async () => {
    if (selectedDimension.value === 'post' || selectedDimension.value === 'story') {
        disposeFabricCanvas();
        await new Promise((r) => setTimeout(r, 50));
        initFabricCanvas();
        loadData();
    }
});
watch(selectedFont, () => { if (!_suppressWatcherRedraw) displayCanvaData(true, true); });
watch(underlineColor, () => { if (!_suppressWatcherRedraw) displayCanvaData(true, true); });
watch(selectedTextColor, () => { if (!_suppressWatcherRedraw) displayCanvaData(true, true); });
watch(headlineText, () => { if (!_suppressWatcherRedraw) displayCanvaData(true, true); });
watch(selectedTextColorHeadline, () => { if (!_suppressWatcherRedraw) displayCanvaData(true, true); });
watch(loadOrganisationLogo, () => { if (!_suppressWatcherRedraw) displayCanvaData(true, true); });
watch(selectedFontHeadline, () => { if (!_suppressWatcherRedraw) displayCanvaData(true, true); });
watch(fromDate, () => refetchEvents());
watch(toDate, () => refetchEvents());
watch(instanceMode, () => { if (materialType === 'eventList') loadData(); });
watch(templateError, (msg) => {
    if (msg) {
        showError.value = true;
        errorMessage.value = msg;
    }
});
</script>

<template>
    <div>
        <h1 class="kern-heading text-theme-primary">Werbemittelgenerator</h1>
        <h2 class="kern-heading text-theme-primary">
            <span v-if="materialType === 'eventList'">– Veranstaltungsübersichten (mehrere Veranstaltungen)</span>
            <span v-else>– Ankündigung einer einzelnen Veranstaltung</span>
        </h2>
        <p v-if="materialType === 'eventList'" class="mt-3 mb-5">
            <b>Hinweis:</b>
            Gestalten Sie Übersichten für anstehende Veranstaltungen im Design Ihrer Organisation. Nutzen Sie dafür auch
            das Markenkit. Ankündigungen für einzelne Veranstaltungen können über den Button der Veranstaltung generiert
            werden. Weitere Informationen finden Sie im
            <LinkToDocs path="Werbemittelgenerator/" fragment="bedienung-des-werbemittelgenerators" />.
        </p>
        <p v-else class="mt-3 mb-5">
            <b>Hinweis:</b>
            Gestalten Sie Ankündigungen für einzelne Veranstaltungen im Design Ihrer Organisation. Nutzen Sie dafür auch
            das Markenkit. Übersichten für mehrere Veranstaltungen können über den Menüpunkt "Werbemittel" im Seitenmenü
            erstellt werden. Weitere Informationen finden Sie im
            <LinkToDocs path="Werbemittelgenerator/" fragment="werbemittelgenerator-fur-ankundigungen-einzelner-veranstaltungen" />.
        </p>
    </div>

    <Alert
        v-if="showError"
        title="Fehler"
        severity="danger"
        :content="errorMessage || 'Es ist ein Fehler aufgetreten.'"
        class="mb-5"
    />

    <MaterialGeneratorToolbar
        v-if="selectedDimensionData?.preview === 'canvas'"
        v-model:sel-font-family="selFontFamily"
        v-model:sel-text-color="selTextColor"
        v-model:sel-fill="selFill"
        v-model:sel-bold="selBold"
        v-model:sel-underline="selUnderline"
        v-model:sel-font-size="selFontSize"
        v-model:sel-stamp-fill="selStampFill"
        v-model:sel-stamp-stroke="selStampStroke"
        v-model:toolbar-font="toolbarFont"
        v-model:toolbar-text-color="toolbarTextColor"
        v-model:toolbar-fill-color="toolbarFillColor"
        :has-text-selected="hasTextSelected"
        :has-shape-selected="hasShapeSelected"
        :has-stamp-selected="hasStampSelected"
        :can-undo="canUndo"
        :can-redo="canRedo"
        :stamp-options="stampOptions"
        @add-text="onAddText"
        @add-shape="onAddShape"
        @add-image="onAddImage"
        @add-stamp="onAddStamp"
        @undo="undo"
        @redo="redo"
    />

    <div v-if="loading" class="flex align-items-center justify-content-center mt-4">
        <Loader />
        <p class="ml-2">Daten werden geladen</p>
    </div>

    <div v-show="!loading" class="mg-layout">
        <div class="mg-layout__canvas">
            <div v-if="selectedDimensionData.preview === 'canvas'" class="mg-canvas-wrap" style="position: relative;">
                <canvas id="canvas1" ref="canvas1" :width="canvasWidth" :height="canvasHeight"></canvas>
                <Transition name="mg-fade">
                    <div v-if="canvasRendering" class="mg-canvas-overlay">
                        <Loader />
                    </div>
                </Transition>
                <MaterialGeneratorContextMenu
                    :visible="contextMenu.visible"
                    :x="contextMenu.x"
                    :y="contextMenu.y"
                    @close="hideContextMenu"
                    @bring-forward="bringForward"
                    @send-backward="sendBackward"
                    @bring-to-front="bringToFront"
                    @send-to-back="sendToBack"
                    @duplicate="duplicateSelected"
                    @delete="deleteSelected"
                />
            </div>
            <div v-if="selectedDimensionData.preview === 'text'" class="mb-3">
                <InputTextarea
                    v-model="formatTextOutput"
                    label="Ausgabetext"
                    name="formatTextOutput"
                    rows="40"
                    cols="50"
                    v-bind="$attrs"
                    style="height: 550px"
                />
            </div>
            <div v-else-if="selectedDimensionData.preview === null" class="mb-3">
                <p class="kern-text">Es gibt keine Vorschau für dieses Dateiformat.</p>
            </div>

            <Legend
                v-if="selectedDimensionData?.preview === 'canvas'"
                title="Funktionen des Editors"
                :icons="editorFeatures"
                class="mt-3"
            />
        </div>

        <MaterialGeneratorSidebar
            v-model:selected-dimension="selectedDimension"
            v-model:from-date="fromDate"
            v-model:to-date="toDate"
            v-model:load-organisation-logo="loadOrganisationLogo"
            v-model:selected-font="selectedFont"
            v-model:selected-text-color="selectedTextColor"
            v-model:underline-color="underlineColor"
            v-model:headline-text="headlineText"
            v-model:selected-font-headline="selectedFontHeadline"
            v-model:selected-text-color-headline="selectedTextColorHeadline"
            :selected-dimension-width="selectedDimensionData.width"
            :selected-dimension-height="selectedDimensionData.height"
            :selected-dimension-preview="selectedDimensionData.preview"
            :material-type="materialType"
            :max-events="eventLimit"
            :available-templates="availableTemplates"
            :all-fetched-events="allFetchedEvents"
            :selected-event-uuids="selectedEventUuids"
            :has-more-events="hasMoreEvents"
            :loading-more-events="loadingMore"
            :is-instance-admin="isInstanceAdmin"
            :instance-mode="instanceMode"
            @download="onDownload"
            @bg-file-change="onBgFileChange"
            @save-template="onSaveTemplate"
            @load-template="onLoadTemplate"
            @delete-template="onDeleteTemplate"
            @toggle-event="onToggleEvent"
            @select-all-events="onSelectAllEvents"
            @load-more-events="onLoadMoreEvents"
            @update:instance-mode="instanceMode = $event"
        />
    </div>

    <img
        v-for="{ name, path } in defaultBackgroundImagePaths"
        :id="'backgroundImage_' + name"
        :key="name"
        style="display: none"
        :src="path"
    />
    <img
        v-if="eventMainCategory"
        :id="'backgroundImage_default_event_main_category'"
        style="display: none"
        :src="`/material_generator/event_main_category/${selectedDimension}/${eventMainCategory}.png`"
    />
</template>

<style scoped>
h1 { padding-bottom: 0 !important; }
h2 { word-break: break-all; }

.mg-layout {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: var(--kern-space-default, 16px);
    align-items: start;
}
.mg-layout > * {
    min-width: 0;
    overflow: hidden;
}
@media (max-width: 900px) {
    .mg-layout { grid-template-columns: 1fr; }
}
.mg-layout__canvas {
    min-width: 0;
    overflow: hidden;
}
.mg-canvas-wrap {
    border: 1px solid var(--kern-layout-border, #dfe7ef);
    border-radius: var(--kern-border-radius-default, 4px);
    overflow: hidden;
    line-height: 0;
    max-width: 100%;
}
.mg-canvas-wrap :deep(.canvas-container),
.mg-canvas-wrap :deep(canvas) {
    max-width: 100% !important;
    height: auto !important;
    display: block;
}
.mg-canvas-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.7);
    z-index: 10;
    pointer-events: none;
}
.mg-fade-enter-active,
.mg-fade-leave-active { transition: opacity 0.2s ease; }
.mg-fade-enter-from,
.mg-fade-leave-to { opacity: 0; }
</style>
