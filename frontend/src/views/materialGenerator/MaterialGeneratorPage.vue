<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchOrganisationEvents, findEvent } from '@/lib/mobilizonClient';
import InputSelect from '@/components/KERN/inputs/InputSelect.vue';
import InputFile from '@/components/KERN/inputs/InputFile.vue';
import Button from '@/components/KERN/Button.vue';
import Loader from '@/components/KERN/cosmetics/Loader.vue';
import InputText from '@/components/KERN/inputs/InputText.vue';
import InputTextarea from '@/components/KERN/inputs/InputTextarea.vue';
import InputDate from '@/components/KERN/inputs/InputDate.vue';
import InputColor from '@/components/KERN/inputs/InputColor.vue';
import { downloadCanvas } from '@/lib/canvas-drawing';
import { formatOnMonthDayTime, getMainCategoryFromSubCategory, formatInputDate, normalizeStreet } from '@/lib/helper';
import Alert from '@/components/KERN/Alert.vue';
import dayjs from 'dayjs';
import { fontSelectionOptions } from '@/lib/const';
import { dsgApi } from '@/lib/dsgApi';
import { loadImage } from '@/lib/dsgClient';
import InputCheckbox from '@/components/KERN/inputs/InputCheckbox.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';
import { createTextEventList, createTextSingleEvent } from '@/lib/shareInformation';

interface InfoBox {
    infoBoxStartX: number;
    infoBoxStartY: number;
    infoBoxWidth: number;
    infoBoxLength: number;
    headlineStartY?: number;
    organisationInfoStartY?: number;
    backgroundImageDefaultId: string;
    logoStartX: number;
    logoStartY: number;
}

const route = useRoute();
const router = useRouter();

const loading = ref<boolean>(false);

const dimensionOptions: Array<{ text: string; value: string }> = [
    { text: 'Social-Media-Beitrag (1080 x 1350 px)', value: 'post' },
    { text: 'Social-Media-Story (1080 x 1920 px)', value: 'story' },
    { text: 'Text', value: 'textWithEmojis' },
];

const defaultBackgroundImagePaths = [
    { name: 'event_post', path: '/material_generator/event_post_default.png' },
    { name: 'event_story', path: '/material_generator/event_story_default.png' },
    { name: 'eventList_post', path: '/material_generator/eventList_post_default.png' },
    { name: 'eventList_story', path: '/material_generator/eventList_story_default.png' },
];

const dimension: Record<
    string,
    {
        width?: number;
        height?: number;
        preview: string | null;
        canvasData?: { event: InfoBox; eventList: InfoBox };
    }
> = {
    post: {
        width: 1080,
        height: 1350,
        preview: 'canvas',
        canvasData: {
            event: {
                infoBoxStartX: 0,
                infoBoxStartY: 568,
                infoBoxWidth: 800,
                infoBoxLength: 720,
                backgroundImageDefaultId: 'event_post',
                logoStartX: 1024,
                logoStartY: 56,
            },
            eventList: {
                infoBoxStartX: 56,
                infoBoxStartY: 340,
                infoBoxWidth: 958,
                infoBoxLength: 766,
                headlineStartY: 160,
                organisationInfoStartY: 1200,
                backgroundImageDefaultId: 'eventList_post',
                logoStartX: 1024,
                logoStartY: 1148,
            },
        },
    },
    story: {
        width: 1080,
        height: 1920,
        preview: 'canvas',
        canvasData: {
            event: {
                infoBoxStartX: 0,
                infoBoxStartY: 1008,
                infoBoxWidth: 800,
                infoBoxLength: 720,
                backgroundImageDefaultId: 'event_story',
                logoStartX: 1024,
                logoStartY: 144,
            },
            eventList: {
                infoBoxStartX: 56,
                infoBoxStartY: 464,
                infoBoxWidth: 958,
                infoBoxLength: 1096,
                headlineStartY: 264,
                organisationInfoStartY: 1680,
                backgroundImageDefaultId: 'eventList_story',
                logoStartX: 1024,
                logoStartY: 1670,
            },
        },
    },
    textWithEmojis: {
        preview: 'text',
    },
    openDocumentText: {
        preview: null,
    },
};

const showError = ref<boolean>(false);
const errorMessage = ref<string>('');

const fromDate = ref(formatInputDate());
const toDate = ref(formatInputDate(new Date(dayjs().add(2, 'month').format('YYYY-MM-DD'))));
const headlineText = ref<string>('Unsere Termine');
const selectedFontHeadline = ref<string>('Arial');
const selectedTextColorHeadline = ref<string>('#111111');
const formatTextOutput = ref('');
const loadOrganisationLogo = ref<boolean>(false);

const uploadedImageBlob = ref<any | null>(null);

const materialType: 'event' | 'eventList' = route.name === 'materialGenerator.event' ? 'event' : 'eventList';

const selectedDimension = ref(dimensionOptions[1].value);
const selectedDimensionData = computed(() => dimension[selectedDimension.value]);
const selectedDimensionInfoBox = computed(() => selectedDimensionData.value?.canvasData[materialType]);

const canvasWidth = computed(() => dimension[selectedDimension.value].width);
const canvasHeight = computed(() => dimension[selectedDimension.value].height);

const organisationUsername = ref((route.params?.preferredUsername as string) ?? null);
const eventUuid = route.params?.uuid ?? null;

const downloadFileName = computed(() => {
    if (materialType === 'eventList') {
        return `${selectedDimension.value}_Veranstaltungsuebersicht_${organisationUsername.value}_${fromDate.value}_${toDate.value}`;
    } else {
        return `${selectedDimension.value}_Veranstaltung_${eventUuid}`;
    }
});

interface Event {
    title: string;
    beginsOn: string;
    endsOn: string;
    uuid: string;
}
const events = ref<Event[]>([]);
const eventListData = ref();
const singleEventData = ref();
const eventMainCategory = ref<string>(''); // used for event info
const organisationAvatarUrl = ref<string | null>(null);
const organisationAvatar = ref<File | null>(null);

const canvas1 = ref(null);

let ctx: CanvasRenderingContext2D, canvas: HTMLCanvasElement;

const loadDefaultValues = async (groupId = null, mobilizonPreferredUsername = null) => {
    try {
        let params = {};
        if (groupId) {
            params = { mobilizon_group_id: groupId };
        } else if (mobilizonPreferredUsername) {
            params = { mobilizon_preferredusername: mobilizonPreferredUsername };
        }
        const { data } = await dsgApi.get('material-generator-values', {
            params,
        });

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
        canvasClearAndLoadImage(uploadedImageBlob.value);
    } catch (error) {
        console.error('Error loading default values:', error);
    }
};

const loadData = async (withoutClear: boolean = false) => {
    showError.value = false;
    try {
        loading.value = true;

        const limit: number = 10;
        if (materialType === 'eventList') {
            organisationUsername.value = route.params?.preferredUsername;

            if (!organisationUsername.value) {
                showError.value = true;
                errorMessage.value = 'Keine Organisation ausgewählt.';
                return;
            }
            if (typeof Number(organisationUsername.value) === 'number') {
                const { data } = await dsgApi.get('organisations/group', {
                    params: {
                        group_id: route.params?.id || route.params?.preferredUsername,
                    },
                });
                organisationUsername.value = data.preferredUsername || route.params?.preferredUsername;
            }
            await loadDefaultValues(null, organisationUsername.value);
            const { elements, name, physicalAddress, avatar } = await fetchOrganisationEvents(
                organisationUsername.value as string,
                new Date(fromDate.value).toISOString(),
                new Date(toDate.value).toISOString(),
                limit
            );
            events.value = elements.map((event: Event) => ({
                title: event.title,
                beginsOn: event.beginsOn,
                endsOn: event.endsOn,
                uuid: event.uuid,
            }));

            if (!events.value.length) {
                showError.value = true;
                errorMessage.value = 'Es wurden keine Veranstaltungen gefunden.';
                return;
            }
            organisationAvatarUrl.value = avatar?.url || null;
            eventListData.value = {
                name,
                physicalAddress,
            };
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
                singleEventData.value?.attributedTo?.preferredUsername
            );
        }
    } catch (error) {
        console.error('Error loading organisation events:', error);
        showError.value = true;
        errorMessage.value = 'Inhalte konnten nicht geladen werden.';
    } finally {
        loading.value = false;
        await displayCanvaData(withoutClear);
    }
};

const displayCanvaData = async (withoutClear: boolean = false) => {
    if (loading.value) return;
    if (!withoutClear) await canvasClearAndLoadImage();
    if (materialType === 'eventList') {
        eventListInfo(events.value);
        eventListOrganisation(eventListData.value?.name || '', eventListData.value?.physicalAddress || '');
        organisationAvatar.value = await loadImage('organisations/avatar', {
            imageUrl: organisationAvatarUrl.value,
        });
        drawAvatarCanvas();
    } else {
        eventMainCategory.value = getMainCategoryFromSubCategory(singleEventData.value.category).value;
        if (!withoutClear) await canvasClearAndLoadImage();
        eventInfo({
            title: singleEventData.value.title,
            beginsOn: singleEventData.value.beginsOn,
            physicalAddress: singleEventData.value?.physicalAddress,
            organisation: singleEventData.value.attributedTo,
        });
        organisationAvatar.value = await loadImage('organisations/avatar', {
            imageUrl: organisationAvatarUrl.value,
        });
        drawAvatarCanvas();
    }
};

const infoBoxStartX = computed<number>(() => selectedDimensionInfoBox.value?.infoBoxStartX ?? 0);
const infoBoxStartY = computed<number>(() => selectedDimensionInfoBox.value?.infoBoxStartY ?? 0);
const infoBoxWidth = computed<number>(() => selectedDimensionInfoBox.value?.infoBoxWidth ?? 0);
const infoBoxLength = computed<number>(() => selectedDimensionInfoBox.value?.infoBoxLength ?? 0);
const defaultTextSize = 30;
const selectedFont = ref<string>('Arial');
const underlineColor = ref<string>('#7dcce8');
const selectedTextColor = ref<string>('#111111');

const eventListInfo = (events: Event[]) => {
    drawTextHeadline();
    let newLine: number = infoBoxStartY.value;
    let index: number = 0;
    // left side
    while (newLine < infoBoxStartY.value + infoBoxLength.value - 100) {
        if (!events[index]) {
            break;
        }
        newLine = eventListSingleEventText(events[index], newLine, infoBoxStartX.value + 20);
        index++;
        if (!newLine) break;
    }
    // right side
    newLine = infoBoxStartY.value;
    while (newLine < infoBoxStartY.value + infoBoxLength.value - 100) {
        if (!events[index]) {
            break;
        }
        newLine = eventListSingleEventText(events[index], newLine, 550);
        index++;
        if (!newLine) break;
    }
    formatTextOutput.value = createTextEventList(headlineText.value, events);
};

const eventInfo = (event) => {
    let newStartY: number = infoBoxStartY.value + 32;
    let startX: number = infoBoxStartX.value + 56;

    newStartY += 50 + 64;
    const textSizeHeadline: number = 62;
    canvasText(breakString(event.title, 23)[0], startX, newStartY, 'normal', textSizeHeadline);

    newStartY += defaultTextSize + 45;
    if (!!breakString(event.title, 23)[1]?.length) {
        canvasText(breakString(event.title, 23)[1], startX, newStartY, 'normal', textSizeHeadline);
    }
    newStartY += defaultTextSize + 45;
    if (!!breakString(event.title, 23)[2].length) {
        canvasText(breakString(event.title, 23)[2], startX, newStartY, 'normal', textSizeHeadline);
    }
    newStartY = infoBoxStartY.value + 384;
    canvasLine(infoBoxStartX.value + 20, newStartY, infoBoxWidth.value - 50);

    newStartY += 78;
    canvasText(formatOnMonthDayTime(event.beginsOn), startX, newStartY, 'normal', 48);

    newStartY += defaultTextSize + 66;
    canvasText(event.organisation.name, startX, newStartY);
    if (event.physicalAddress) {
        newStartY += defaultTextSize;
        canvasText(normalizeStreet(event?.physicalAddress?.street), startX, newStartY);
        newStartY += defaultTextSize;
        canvasText(`${event?.physicalAddress?.postalCode} ${event?.physicalAddress?.locality}`, startX, newStartY);
    }
    formatTextOutput.value = createTextSingleEvent(event, true, false);
};

const drawTextHeadline = () => {
    canvasText(
        headlineText.value,
        40,
        selectedDimensionInfoBox.value?.headlineStartY || 0,
        'bold',
        defaultTextSize * 2,
        selectedFontHeadline.value,
        selectedTextColorHeadline.value
    );
};
const eventListOrganisation = (name: string, physicalAddress) => {
    let newStartY: number = selectedDimensionInfoBox.value?.organisationInfoStartY || 0;
    canvasText(name, 40, newStartY, 'bold');
    newStartY += defaultTextSize + 10;
    canvasText(normalizeStreet(physicalAddress?.street) || '', 40, newStartY);
    newStartY += defaultTextSize + 10;
    canvasText(`${physicalAddress?.postalCode || ''} ${physicalAddress?.locality || ''}`, 40, newStartY);
};

const eventListSingleEventText = (event: Event, startY: number, startX: number, divideInfoBox: number = 2): number => {
    let newStartY: number = startY + 10;

    newStartY += defaultTextSize + 15;
    canvasText(breakString(event.title, 30)[0], startX, newStartY);

    newStartY += defaultTextSize;
    if (!!breakString(event.title, 25)[1].length) {
        canvasText(breakString(event.title, 30)[1], startX, newStartY);
    }

    newStartY += defaultTextSize + 10;
    canvasText(formatOnMonthDayTime(event.beginsOn), startX, newStartY);

    newStartY += 20;
    canvasLine(startX, newStartY, infoBoxWidth.value / divideInfoBox - 50);

    return newStartY;
};

function breakString(str: string, maxStringLength: number, line: number = 0): any[] {
    if (str.length <= maxStringLength) return [str, '', ''];
    let resultLine1: string = '';
    let resultLine2: string = '';
    let resultLine3: string = '';
    for (const word of str.split(' ')) {
        if (resultLine1.length + word.length < maxStringLength) {
            resultLine1 += word + ' ';
        } else {
            break;
        }
    }
    if (str.slice(resultLine1.length).length) {
        for (const word of str.slice(resultLine1.length).split(' ')) {
            if (resultLine2.length + word.length < maxStringLength) {
                resultLine2 += word + ' ';
            } else {
                break;
            }
        }
        if (str.slice(resultLine2.length).length) {
            for (const word of str.slice(resultLine1.length + resultLine2.length).split(' ')) {
                if (resultLine3.length + word.length < maxStringLength) {
                    resultLine3 += word + ' ';
                } else {
                    break;
                }
            }
        }
    }
    return [resultLine1, resultLine2, resultLine3];
}

// --- generic canvas helper ---

const canvasText = (
    text: string,
    startX: number,
    startY: number,
    weight: string = 'normal',
    textSize: number = defaultTextSize,
    fontName: string = selectedFont.value,
    textColor: string = selectedTextColor.value
) => {
    ctx.fillStyle = textColor;
    ctx.font = `${weight} ${textSize}px ${fontName}`;
    ctx.fillText(text, startX, startY);
};

const canvasLine = (startX: number, startY: number, width: number, height: number = 5) => {
    ctx.fillStyle = underlineColor.value;
    ctx.fillRect(startX, startY, width, height);
};

const canvasClear = async () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
};

// --- other

const canvasClearAndLoadImage = async (imageBlob = null) => {
    canvasClear();
    if (!!uploadedImageBlob.value && imageBlob === null) {
        imageBlob = uploadedImageBlob.value;
    }
    if (!imageBlob) {
        let imageId = 'backgroundImage_' + selectedDimensionInfoBox.value?.backgroundImageDefaultId;
        if (materialType === 'event') {
            imageId = 'backgroundImage_default_event_main_category';
            await new Promise((r) => setTimeout(r, 50));
        }
        const image: HTMLImageElement = document.getElementById(imageId) as HTMLImageElement;
        if (!image) return;
        ctx.drawImage(image, 0, 0);
    } else {
        createImageBitmap(imageBlob).then((imageBitmap) => {
            ctx.drawImage(imageBitmap, 0, 0);
            uploadedImageBlob.value = imageBlob;
        });
        await new Promise((r) => setTimeout(r, 50));
    }
};

const drawAvatarCanvas = () => {
    if (!loadOrganisationLogo.value || !organisationAvatar.value) return;
    createImageBitmap(organisationAvatar.value).then((imageBitmap) => {
        const maxAvatarWidth = 400;
        const maxAvatarHeight = 180;
        let dWidth = imageBitmap.width;
        let dHeight = imageBitmap.height;
        if (dWidth > maxAvatarWidth || dHeight > maxAvatarHeight) {
            const ratio = imageBitmap.width / imageBitmap.height;
            if (dWidth > maxAvatarWidth) {
                dWidth = maxAvatarWidth;
                dHeight = maxAvatarWidth / ratio;
            }
            if (dHeight > maxAvatarHeight) {
                dHeight = maxAvatarHeight;
                dWidth = maxAvatarHeight * ratio;
            }
        }
        ctx.drawImage(
            imageBitmap,
            selectedDimensionInfoBox.value?.logoStartX - dWidth,
            selectedDimensionInfoBox.value?.logoStartY,
            dWidth,
            dHeight
        );
    });
};
const handleUploadedFile = async (files: any[] | null) => {
    if (!files) return;
    const file = files[0];
    if (!file) {
        return;
    }
    await canvasClearAndLoadImage(file);
    await new Promise((r) => setTimeout(r, 500));
    displayCanvaData();
};

onMounted(async () => {
    canvas = document.getElementById('canvas1') as HTMLCanvasElement;
    ctx = canvas.getContext('2d') as CanvasRenderingContext2D;
    loadData();
});

watch(selectedDimension, async (newValue) => {
    if (newValue === 'post' || newValue === 'story') {
        loadData();
    }
});
watch(selectedFont, async (newValue) => {
    displayCanvaData();
});
watch(underlineColor, async (newValue) => {
    displayCanvaData();
});
watch(selectedTextColor, async (newValue) => {
    displayCanvaData();
});
watch(headlineText, async (newValue) => {
    displayCanvaData();
});
watch(selectedTextColorHeadline, async (newValue) => {
    displayCanvaData();
});
watch(loadOrganisationLogo, async (newValue) => {
    displayCanvaData();
});
watch(selectedFontHeadline, async (newValue) => {
    displayCanvaData();
});
watch(fromDate, async (newValue) => {
    loadData();
});
watch(toDate, async (newValue) => {
    loadData();
});
</script>
<template>
    <!--
    <Teleport to="#headerslot">
        <div class="my-6">
            <h1 class="kern-heading text-theme-primary">Werbemittelgenerator</h1>
        </div>
    </Teleport>
    -->
    <div>
        <h1 class="kern-heading text-theme-primary">Werbemittelgenerator</h1>
        <h2 class="kern-heading text-theme-primary">
            <span v-if="materialType === 'eventList'">– Veranstaltungsübersichten (mehrere Veranstaltungen)</span>
            <span v-else>– Ankündigung einer einzelnen Veranstaltung</span>
        </h2>
        <p
            class="mt-3 mb-5"
            v-if="materialType === 'eventList'"
        >
            <b>Hinweis:</b>
            Gestalten Sie Übersichten für anstehende Veranstaltungen im Design Ihrer Organisation. Nutzen Sie dafür auch
            das Markenkit. Ankündigungen für einzelne Veranstaltungen können über den Button der Veranstaltung generiert
            werden. Weitere Informationen finden Sie im
            <LinkToDocs
                path="Werbemittelgenerator/"
                fragment="bedienung-des-werbemittelgenerators"
            />
            .
        </p>
        <p
            class="mt-3 mb-5"
            v-else
        >
            <b>Hinweis:</b>
            Gestalten Sie Ankündigungen für einzelne Veranstaltungen im Design Ihrer Organisation. Nutzen Sie dafür auch
            das Markenkit. Übersichten für mehrere Veranstaltungen können über den Menüpunkt “Werbemittel” im Seitenmenü
            erstellt werden. Weitere Informationen finden Sie im
            <LinkToDocs
                path="Werbemittelgenerator/"
                fragment="werbemittelgenerator-fur-ankundigungen-einzelner-veranstaltungen"
            />
            .
        </p>
    </div>
    <div class="flex justify-content-end mb-3">
        <Button
            class="mt-3"
            @click="downloadCanvas(canvas, downloadFileName)"
            icon-left="download"
            v-if="selectedDimensionData?.preview === 'canvas'"
        >
            Design herunterladen
        </Button>
    </div>
    <div class="kern-row gap-5">
        <div class="kern-col-xl-5">
            <h2 class="kern-heading text-theme-primary">Einstellungen</h2>
            <InputSelect
                label="Format"
                name="dimensionOptions"
                :options="dimensionOptions"
                v-model="selectedDimension"
            />
            <template v-if="materialType === 'eventList'">
                <h3 class="kern-heading text-theme-primary">Zeitraum</h3>
                <div class="kern-row">
                    <div class="kern-col">
                        <InputDate
                            name="fromDate"
                            v-model="fromDate"
                            label="Startdatum"
                        />
                    </div>
                    <div class="kern-col">
                        <InputDate
                            name="toDate"
                            v-model="toDate"
                            label="Enddatum"
                        />
                    </div>
                </div>
                <p class="mt-3">
                    <b>Hinweis:</b>
                    Es können maximal 10 Veranstaltungen angezeigt werden.
                </p>
            </template>
            <template v-if="selectedDimensionData?.preview === 'canvas'">
                <InputFile
                    class="mt-3"
                    name="selectedFile"
                    label="Hintergrundbild"
                    accept="image/png, image/jpeg"
                    @file-change="handleUploadedFile"
                />
                <p class="mt-2">
                    <b>Hinweis:</b>
                    <LinkToDocs
                        path="Werbemittelgenerator/"
                        fragment="hintergrunde-fur-veranstaltungsubersichten-gestalten"
                    >
                        Layout-Vorlagen
                    </LinkToDocs>
                </p>
                <p
                    v-if="selectedDimensionData.width && selectedDimensionData.height"
                    class="mt-2"
                >
                    <b>Größe:</b>
                    {{ selectedDimensionData.width }} x {{ selectedDimensionData.height }} px
                </p>
                <div class="mt-3 mb-3">
                    <InputCheckbox
                        v-model="loadOrganisationLogo"
                        label="Logo laden"
                        name="loadOrganisationLogo"
                    />
                </div>
                <template v-if="materialType === 'eventList'">
                    <h3 class="kern-heading text-theme-primary mt-5">Überschrift (optional)</h3>

                    <InputText
                        label="Text Überschrift"
                        name="headlineText"
                        v-model="headlineText"
                    />

                    <InputSelect
                        label="Schriftart"
                        name="selectedFontHeadline"
                        :options="fontSelectionOptions"
                        v-model="selectedFontHeadline"
                    />

                    <InputColor
                        class="mt-3"
                        name="selectedTextColorHeadline"
                        label="Textfarbe"
                        v-model="selectedTextColorHeadline"
                    />
                </template>
                <h3 class="kern-heading text-theme-primary mt-5">Veranstaltung</h3>

                <InputColor
                    class="mt-3"
                    name="underlineColor"
                    label="Farbe der Trennlinien"
                    v-model="underlineColor"
                />
                <InputColor
                    class="mt-3"
                    name="selectedTextColor"
                    label="Textfarbe"
                    v-model="selectedTextColor"
                />
                <InputSelect
                    label="Schriftart"
                    name="selectedFont"
                    :options="fontSelectionOptions"
                    v-model="selectedFont"
                />
            </template>
        </div>

        <div class="kern-col ml-auto">
            <h2 class="kern-heading text-theme-primary">Vorschau</h2>
            <Alert
                title="Fehler"
                v-if="showError"
                severity="danger"
                :content="errorMessage || 'Es ist ein Fehler aufgetreten.'"
            />
            <div class="flex justify-content-start">
                <div
                    style=""
                    v-show="!loading"
                >
                    <div
                        v-if="selectedDimensionData.preview === 'canvas'"
                        style="width: 100%"
                    >
                        <canvas
                            ref="canvas1"
                            id="canvas1"
                            :width="canvasWidth"
                            :height="canvasHeight"
                            style="width: 100%"
                        ></canvas>
                    </div>
                </div>
                <div
                    v-if="selectedDimensionData.preview === 'text'"
                    class="mb-3"
                >
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
                <div
                    v-else-if="selectedDimensionData.preview === null"
                    class="mb-3"
                >
                    <p class="kern-text">Es gibt keine Vorschau für diese Dateiformat.</p>
                </div>
                <div
                    v-if="loading"
                    class="flex align-items-center justify-content-center"
                >
                    <Loader />
                    <p>Daten werden geladen</p>
                </div>
            </div>
        </div>
    </div>
    <img
        v-for="{ name, path } in defaultBackgroundImagePaths"
        style="display: none"
        :src="path"
        :id="'backgroundImage_' + name"
    />
    <img
        v-if="eventMainCategory"
        style="display: none"
        :src="`/material_generator/event_main_category/${selectedDimension}/${eventMainCategory}.png`"
        :id="'backgroundImage_default_event_main_category'"
    />
</template>
<style scoped>
.opacityZero {
    opacity: 0;
}
h1 {
    padding-bottom: 0px !important;
}
h2 {
    word-break: break-all;
}
</style>
