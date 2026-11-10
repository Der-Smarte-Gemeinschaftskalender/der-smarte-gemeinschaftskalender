<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { fontSelectionOptions } from '@/lib/const';
import InputSelect from '@/components/KERN/inputs/InputSelect.vue';
import InputColor from '@/components/KERN/inputs/InputColor.vue';
import InputNumber from '@/components/KERN/inputs/InputNumber.vue';
import Button from '@/components/KERN/Button.vue';

export interface StampOption {
    label: string;
    file: string;
}

defineProps<{
    hasTextSelected: boolean;
    hasShapeSelected: boolean;
    hasStampSelected: boolean;
    selFontFamily: string;
    selTextColor: string;
    selFill: string;
    selBold: boolean;
    selUnderline: boolean;
    selFontSize: number;
    selStampFill: string;
    selStampStroke: string;
    toolbarFont: string;
    toolbarTextColor: string;
    toolbarFillColor: string;
    canUndo: boolean;
    canRedo: boolean;
    stampOptions: StampOption[];
}>();

const emit = defineEmits<{
    (e: 'update:selFontFamily', val: string): void;
    (e: 'update:selTextColor', val: string): void;
    (e: 'update:selFill', val: string): void;
    (e: 'update:selBold', val: boolean): void;
    (e: 'update:selUnderline', val: boolean): void;
    (e: 'update:selFontSize', val: number): void;
    (e: 'update:selStampFill', val: string): void;
    (e: 'update:selStampStroke', val: string): void;
    (e: 'update:toolbarFont', val: string): void;
    (e: 'update:toolbarTextColor', val: string): void;
    (e: 'update:toolbarFillColor', val: string): void;
    (e: 'addText'): void;
    (e: 'addShape', shape: string): void;
    (e: 'addImage', files: File[]): void;
    (e: 'addStamp', file: string): void;
    (e: 'undo'): void;
    (e: 'redo'): void;
}>();

const { t } = useI18n();

const showShapesMenu = ref(false);
const showStampsMenu = ref(false);
const shapesMenuRef = ref<HTMLElement | null>(null);
const stampsMenuRef = ref<HTMLElement | null>(null);



function toggleShapesMenu() {
    showShapesMenu.value = !showShapesMenu.value;
    showStampsMenu.value = false;
}

function addShapeAndClose(shape: string) {
    emit('addShape', shape);
    showShapesMenu.value = false;
}

function toggleStampsMenu() {
    showStampsMenu.value = !showStampsMenu.value;
    showShapesMenu.value = false;
}

function addStampAndClose(file: string) {
    emit('addStamp', file);
    showStampsMenu.value = false;
}

function onDocumentClick(e: MouseEvent) {
    if (shapesMenuRef.value && !shapesMenuRef.value.contains(e.target as Node)) {
        showShapesMenu.value = false;
    }
    if (stampsMenuRef.value && !stampsMenuRef.value.contains(e.target as Node)) {
        showStampsMenu.value = false;
    }
}

import { onMounted, onBeforeUnmount } from 'vue';
import Icon from '@/components/KERN/cosmetics/Icon.vue';
onMounted(() => document.addEventListener('click', onDocumentClick, true));
onBeforeUnmount(() => document.removeEventListener('click', onDocumentClick, true));
</script>

<template>
    <div class="flex flex-wrap align-items-end gap-2 mb-3">
        <div class="flex flex-wrap gap-2">
            <div class="flex flex-column gap-1">
            <span class="text-base font-semibold text-color-secondary">{{ t('app.materialGenerator.toolbar.undo') }}</span>
            <div class="flex gap-1">
                <Button
                    variant="secondary"
                    icon-left="undo"
                    icon-size="sm"
                    :title="t('app.materialGenerator.toolbar.undo')"
                    :aria-label="t('app.materialGenerator.toolbar.undo')"
                    :disabled="!canUndo"
                    @click="emit('undo')"
                />
                <Button
                    variant="secondary"
                    icon-left="redo"
                    icon-size="sm"
                    :title="t('app.materialGenerator.toolbar.redo')"
                    :aria-label="t('app.materialGenerator.toolbar.redo')"
                    :disabled="!canRedo"
                    @click="emit('redo')"
                />
            </div>
        </div>

        <div class="flex flex-column gap-1">
            <span class="text-base font-semibold text-color-secondary">{{ t('app.materialGenerator.toolbar.add') }}</span>
            <div class="flex gap-1">
                <Button
                    variant="secondary"
                    icon-left="text_fields"
                    icon-size="sm"
                    :title="t('app.materialGenerator.toolbar.addText')"
                    :aria-label="t('app.materialGenerator.toolbar.addText')"
                    @click="emit('addText')"
                />
                <div ref="shapesMenuRef" class="mg-dropdown-wrap">
                    <Button
                        variant="secondary"
                        icon-left="square"
                        :icon-right="showShapesMenu ? 'keyboard_arrow_up' : 'keyboard_arrow_down'"
                        icon-size="sm"
                        :title="t('app.materialGenerator.toolbar.addShape')"
                        :aria-label="t('app.materialGenerator.toolbar.addShape')"
                        @click="toggleShapesMenu()"
                    />
                    <div v-show="showShapesMenu" class="mg-dropdown kern-card">
                        <button class="mg-dropdown__item" @click="addShapeAndClose('rect')"><Icon name="square" size="sm" /> {{ t('app.materialGenerator.toolbar.shapes.rect') }}</button>
                        <button class="mg-dropdown__item" @click="addShapeAndClose('circle')"><Icon name="circle" size="sm" /> {{ t('app.materialGenerator.toolbar.shapes.circle') }}</button>
                        <button class="mg-dropdown__item" @click="addShapeAndClose('ellipse')"><Icon name="ellipse" size="sm" /> {{ t('app.materialGenerator.toolbar.shapes.ellipse') }}</button>
                        <button class="mg-dropdown__item" @click="addShapeAndClose('triangle')"><Icon name="triangle" size="sm" /> {{ t('app.materialGenerator.toolbar.shapes.triangle') }}</button>
                        <button class="mg-dropdown__item" @click="addShapeAndClose('line')"><Icon name="line" size="sm" /> {{ t('app.materialGenerator.toolbar.shapes.line') }}</button>
                        <button class="mg-dropdown__item" @click="addShapeAndClose('star')"><Icon name="star" size="sm" /> {{ t('app.materialGenerator.toolbar.shapes.star') }}</button>
                        <button class="mg-dropdown__item" @click="addShapeAndClose('arrow')"><Icon name="arrow-forward" size="sm" /> {{ t('app.materialGenerator.toolbar.shapes.arrow') }}</button>
                        <button class="mg-dropdown__item" @click="addShapeAndClose('hexagon')"><Icon name="hexagon" size="sm" /> {{ t('app.materialGenerator.toolbar.shapes.hexagon') }}</button>
                    </div>
                </div>
                <Button
                    variant="secondary"
                    icon-left="image"
                    icon-size="sm"
                    :title="t('app.materialGenerator.toolbar.addImage')"
                    :aria-label="t('app.materialGenerator.toolbar.addImage')"
                    @click="($refs.imageFileInput as HTMLInputElement)?.click()"
                />
                <input
                    ref="imageFileInput"
                    type="file"
                    accept="image/png, image/jpeg, image/webp, image/svg+xml"
                    style="display: none;"
                    @change="(e: globalThis.Event) => emit('addImage', Array.from(((e.target) as HTMLInputElement).files ?? []))"
                />
                <div ref="stampsMenuRef" class="mg-dropdown-wrap">
                    <Button
                        variant="secondary"
                        icon-left="stamp"
                        :icon-right="showStampsMenu ? 'keyboard_arrow_up' : 'keyboard_arrow_down'"
                        icon-size="sm"
                        :title="t('app.materialGenerator.toolbar.addStamp')"
                        :aria-label="t('app.materialGenerator.toolbar.addStamp')"
                        @click="toggleStampsMenu()"
                    />
                    <div v-show="showStampsMenu" class="mg-dropdown kern-card">
                        <button
                            v-for="stamp in stampOptions"
                            :key="stamp.file"
                            class="mg-dropdown__item"
                            @click="addStampAndClose(stamp.file)"
                        >
                            <Icon name="stamp" size="sm" />
                            {{ stamp.label }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <div class="flex flex-row justify-center align-items-center gap-3 kern-card p-2 mg-toolbar-props-card" style="min-height: 44px">
            <template v-if="hasTextSelected">
                <InputSelect
                    :model-value="selFontFamily"
                    name="selFontFamily"
                    :label="t('app.materialGenerator.toolbar.font')"
                    :options="fontSelectionOptions"
                    class="mg-toolbar-props__select"
                    @update:model-value="(v) => v && emit('update:selFontFamily', v)"
                />
                <div class="flex align-items-center gap-1 white-space-nowrap">
                    <InputColor :model-value="selTextColor" :label="t('app.materialGenerator.toolbar.textColor')" name="selTextColor" @update:model-value="(v) => v && emit('update:selTextColor', v)" />
                </div>
                <InputNumber
                    :model-value="selFontSize"
                    name="selFontSize"
                    :label="t('app.materialGenerator.toolbar.size')"
                    class="mg-toolbar-props__number"
                    min="8"
                    max="400"
                    @update:model-value="(v) => v != null && emit('update:selFontSize', Number(v))"
                />
                <div class="kern-form-input">
                    <label class="kern-form-input__label text-base">{{ t('app.materialGenerator.toolbar.formatting') }}</label>
                    <div class="flex gap-1">
                        <Button
                            :variant="selBold ? 'primary' : 'secondary'"
                            icon-left="bold"
                            icon-size="sm"
                            :title="t('app.materialGenerator.toolbar.bold')"
                            :aria-label="t('app.materialGenerator.toolbar.bold')"
                            @click="emit('update:selBold', !selBold)"
                        />
                        <Button
                            :variant="selUnderline ? 'primary' : 'secondary'"
                            icon-left="underline"
                            icon-size="sm"
                            :title="t('app.materialGenerator.toolbar.underline')"
                            :aria-label="t('app.materialGenerator.toolbar.underline')"
                            @click="emit('update:selUnderline', !selUnderline)"
                        />
                    </div>
                </div>
            </template>

            <template v-if="hasShapeSelected && !hasTextSelected">
                <div class="flex align-items-center gap-1 white-space-nowrap">
                    <InputColor :model-value="selFill" name="selFill" :label="t('app.materialGenerator.toolbar.fillColor')" @update:model-value="(v) => v && emit('update:selFill', v)" />
                </div>
            </template>

            <template v-if="hasShapeSelected && hasTextSelected">
                <div class="flex align-items-center gap-1 white-space-nowrap">
                    <InputColor :model-value="selFill" name="selFillCombo" :label="t('app.materialGenerator.toolbar.fillColor')" @update:model-value="(v) => v && emit('update:selFill', v)" />
                </div>
            </template>

            <template v-if="hasStampSelected">
                <div class="flex align-items-center gap-1 white-space-nowrap">
                    <InputColor :model-value="selStampFill" name="selStampFill" :label="t('app.materialGenerator.toolbar.background')" @update:model-value="(v) => v && emit('update:selStampFill', v)" />
                </div>
                <div class="flex align-items-center gap-1 white-space-nowrap">
                    <InputColor :model-value="selStampStroke" name="selStampStroke" :label="t('app.materialGenerator.toolbar.borderAndText')" @update:model-value="(v) => v && emit('update:selStampStroke', v)" />
                </div>
            </template>

            <template v-if="!hasTextSelected && !hasShapeSelected && !hasStampSelected">
                <InputSelect
                    :model-value="toolbarFont"
                    name="toolbarFont"
                    :label="t('app.materialGenerator.toolbar.font')"
                    :options="fontSelectionOptions"
                    class="mg-toolbar-props__select"
                    @update:model-value="(v) => v && emit('update:toolbarFont', v)"
                />
                <div class="flex align-items-center gap-1 white-space-nowrap">
                    <InputColor :model-value="toolbarTextColor" name="toolbarTextColor" :label="t('app.materialGenerator.toolbar.textColor')" @update:model-value="(v) => v && emit('update:toolbarTextColor', v)" />
                </div>
                <div class="flex align-items-center gap-1 white-space-nowrap">
                    <InputColor :model-value="toolbarFillColor" name="toolbarFillColor" :label="t('app.materialGenerator.toolbar.fillColor')" @update:model-value="(v) => v && emit('update:toolbarFillColor', v)" />
                </div>
            </template>
        </div>
    </div>
</template>
<style scoped>
.mg-toolbar-props-card {
    max-width: 100%;
    overflow-x: auto;
}
.mg-toolbar-props__select { 
    min-width: 130px; 
    max-width: 240px; 
}
.mg-toolbar-props__number { 
    max-width: 80px; 
}
.mg-toolbar-props__number :deep(.kern-form-input__input) {
    width: 56px; 
    text-align: center; 
}

.mg-dropdown-wrap { position: relative; }
.mg-dropdown {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    z-index: 100;
    min-width: 170px;
    padding: 0.25rem 0;
}
.mg-dropdown__item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.45rem 0.75rem;
    border: none;
    background: none;
    cursor: pointer;
}
.mg-dropdown__item:hover { 
    background: var(--kern-layout-surface-highlight, #f6f9fc); 
}
</style>
