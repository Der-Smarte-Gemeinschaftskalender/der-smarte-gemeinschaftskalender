<script setup lang="ts">
import { current_organisation } from '@/composables/OrganisationComposable';
import { dsgApi } from '@/lib/dsgApi';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { fontSelectionOptions } from '@/lib/const';
import InputText from '@/components/KERN/inputs/InputText.vue';
import InputColor from '@/components/KERN/inputs/InputColor.vue';
import InputSelect from '@/components/KERN/inputs/InputSelect.vue';
import Button from '@/components/KERN/Button.vue';
import Alert from '@/components/KERN/Alert.vue';
import InputImage from '@/components/KERN/inputs/InputImage.vue';
import { loadImage } from '@/lib/dsgClient';
import LinkToDocs from '@/components/LinkToDocs.vue';

const router = useRouter();
const showSuccessSavingAlert = ref<boolean>(false);
const showErrorAlert = ref<boolean>(false);

const underlineColor = ref<string>('#7dcce8');
const selectedTextColor = ref<string>('#000000');
const selectedFont = ref<string>('Arial');

const headlineText = ref<string>('Unsere Termine');
const selectedFontHeadline = ref<string>('Arial');
const selectedTextColorHeadline = ref<string>('#000000');

const eventListStoryImage = ref<any>(null);
const eventListPostImage = ref<any>(null);
const eventStoryImage = ref<any>(null);
const eventPostImage = ref<any>(null);

const loadBrandKit = async () => {
    try {
        if (!current_organisation.value) {
            router.push({ name: 'dashboard' });
            return;
        }
        const { data } = await dsgApi.get('material-generator-values', {
            params: {
                mobilizon_group_id: current_organisation.value.id,
            },
        });
        headlineText.value = data.default_header_settings?.headlineText || 'Unsere Termine';
        underlineColor.value = data.default_text_settings?.underlineColor || '#7dcce8';
        selectedTextColor.value = data.default_text_settings?.selectedTextColor || '#000000';
        selectedFont.value = data.default_text_settings?.selectedFont || 'Arial';
        selectedFontHeadline.value = data.default_header_settings?.selectedFontHeadline || 'Arial';
        selectedTextColorHeadline.value = data.default_header_settings?.selectedTextColorHeadline || '#000000';
        const { mobilizon_preferredusername } = data;
        eventListStoryImage.value = await loadImage('material-generator-values/image', {
            path: `${mobilizon_preferredusername}/eventListStory.png`,
        });
        eventListPostImage.value = await loadImage('material-generator-values/image', {
            path: `${mobilizon_preferredusername}/eventListPost.png`,
        });
        eventStoryImage.value = await loadImage('material-generator-values/image', {
            path: `${mobilizon_preferredusername}/eventStory.png`,
        });
        eventPostImage.value = await loadImage('material-generator-values/image', {
            path: `${mobilizon_preferredusername}/eventPost.png`,
        });
    } catch (error) {
        console.error('Error loading brand kit:', error);
    }
};

const saveBrandKit = async () => {
    try {
        showSuccessSavingAlert.value = false;
        showErrorAlert.value = false;
        await dsgApi.post(
            'material-generator-values',
            {
                mobilizon_group_id: current_organisation.value.id,
                mobilizon_preferredusername: current_organisation.value?.preferredUsername,
                default_text_settings: {
                    underlineColor: underlineColor.value,
                    selectedTextColor: selectedTextColor.value,
                    selectedFont: selectedFont.value,
                },
                default_header_settings: {
                    headlineText: headlineText.value,
                    selectedFontHeadline: selectedFontHeadline.value,
                    selectedTextColorHeadline: selectedTextColorHeadline.value,
                },
                eventListStoryImage: eventListStoryImage.value,
                eventListPostImage: eventListPostImage.value,
                eventStoryImage: eventStoryImage.value,
                eventPostImage: eventPostImage.value,
            },
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );
        showSuccessSavingAlert.value = true;
    } catch (error) {
        console.error('Error saving brand kit:', error);
        showErrorAlert.value = true;
    }
};
loadBrandKit();
</script>
<template>
    <div>
        <h1 class="kern-heading text-theme-primary">Markenkit</h1>
        <p class="mt-3 mb-5">
            <b>Hinweis:</b>
            Im Markenkit können Sie die Designvorgaben Ihrer Organisation speichern. Diese können für die Erstellung von
            Veranstalungsakündigungen genutzt werden. Jedes Mitglied Ihrer Organisation kann bei der Nutzung des
            Werbemittelgenerators darauf zugreifen. So sparen Sie Zeit und stellen ein einheitliches Erscheinungsbild
            sicher. Ihr Logo hinterlegen Sie im Bereich Organisation. Weitere Informationen finden Sie im <LinkToDocs
                path="Werbemittelgenerator/"
                fragment="markenkit"
            />.
        </p>
    </div>
    <div>
        <h2 class="kern-heading text-theme-primary mt-5">Farben und Schriften</h2>
        <h3 class="kern-heading text-theme-primary mt-5">Texte</h3>

        <InputColor
            v-model="underlineColor"
            class="mt-3"
            name="underlineColor"
            label="Farbe der Trennlinien"
        />
        <InputColor
            v-model="selectedTextColor"
            class="mt-3"
            name="selectedTextColor"
            label="Textfarbe"
        />
        <InputSelect
            v-model="selectedFont"
            label="Schriftart"
            name="selectedFont"
            :options="fontSelectionOptions"
        />

        <h3 class="kern-heading text-theme-primary mt-5">Überschrift (optional)</h3>

        <InputText
            v-model="headlineText"
            label="Text Überschrift"
            name="headlineText"
        />

        <InputSelect
            v-model="selectedFontHeadline"
            label="Schriftart"
            name="selectedFontHeadline"
            :options="fontSelectionOptions"
        />

        <InputColor
            v-model="selectedTextColorHeadline"
            class="mt-3"
            name="selectedTextColorHeadline"
            label="Textfarbe"
        />
        <h2 class="kern-heading text-theme-primary mt-5">Hintergrundbilder</h2>
        <Alert title="Information">
            <p>
                Mit dem Werbemittelgenerator können Ankündigungen für einzelne Veranstaltungen oder Übersichten mehrerer
                anstehender Veranstaltungen erstellt werden.
            </p>
            <p>
                Hier können Hintergrundbilder für die verschiedenen Formate und Ankündigungsarten bereitgestellt werden.
                Layout-Vorlagen zur passgenauen Gestaltung der Hintergründe finden Sie im
                <LinkToDocs
                    path="Werbemittelgenerator/"
                    fragment="richtlinien-fur-die-hintergrunderstellung"
                />.
            </p>
        </Alert>
        <h3 class="kern-heading text-theme-primary mt-5">Veranstaltungsübersichten</h3>
        <div class="kern-row">
            <div class="kern-col-xxl-6 mb-3">
                <InputImage
                    v-model="eventListStoryImage"
                    label="Format: Story"
                    name="picture"
                    :accept="['image/png']"
                    class="mb-2"
                />
                <span>
                    <b>Größe:</b>
                    1080 x 1920 px
                </span>
            </div>
            <div class="kern-col-xxl-6 mb-3">
                <InputImage
                    v-model="eventListPostImage"
                    label="Format: Beitrag"
                    name="picture"
                    :accept="['image/png']"
                    class="mb-2"
                />
                <span>
                    <b>Größe:</b>
                    1080 x 1350 px
                </span>
            </div>
        </div>
        <h3 class="kern-heading text-theme-primary mt-5">Einzelne Veranstaltung</h3>
        <div class="kern-row">
            <div class="kern-col-xxl-6 mb-3">
                <InputImage
                    v-model="eventStoryImage"
                    label="Format: Story"
                    name="picture"
                    :accept="['image/png']"
                    class="mb-2"
                />
                <span>
                    <b>Größe:</b>
                    1080 x 1920 px
                </span>
            </div>
            <div class="kern-col-xxl-6 mb-3">
                <InputImage
                    v-model="eventPostImage"
                    label="Format: Beitrag"
                    name="picture"
                    :accept="['image/png']"
                    class="mb-2"
                />
                <span>
                    <b>Größe:</b>
                    1080 x 1350 px
                </span>
            </div>
        </div>
        <Alert
            v-if="showSuccessSavingAlert"
            title="Gespeichert"
            content="Die Einstellungen wurden erfolgreich gespeichert."
            severity="success"
        />
        <Alert
            v-if="showErrorAlert"
            title="Fehler"
            content="Die Einstellungen konnten nicht gespeichert werden."
            severity="danger"
        />
        <Button
            class="mt-3"
            label="Speichern"
            @click="saveBrandKit"
        />
    </div>
</template>
