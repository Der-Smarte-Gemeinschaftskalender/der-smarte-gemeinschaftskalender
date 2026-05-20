import { ref, computed, type Ref } from 'vue';
import {
    Canvas, Textbox, FabricImage, Rect, Circle, Ellipse,
    Triangle, Polygon, Line, Path, Group, ActiveSelection, type FabricObject,
    util, loadSVGFromString,
} from 'fabric';
import { initAligningGuidelines } from '@/composables/useAligningGuidelines';
import type { InfoBox, SerializedFabricObject } from './materialGeneratorConstants';

const AUTO_TAG = '__auto';

type AutoExtras = {
    _tag?: string;
    _autoKey?: string;
    _isBgImage?: boolean;
    _isAvatar?: boolean;
    _isStamp?: boolean;
};

type AutoFabricObject = FabricObject & AutoExtras;

export function useFabricCanvas(
    canvasWidth: Ref<number | undefined>,
    canvasHeight: Ref<number | undefined>,
    selectedDimensionInfoBox: Ref<InfoBox | undefined>,
) {
    let fabricCanvas: Canvas | null = null;
    let cleanupGuidelines: (() => void) | null = null;
    let _ignoreSelPropWatch = false;

    const CUSTOM_PROPS = ['_tag', '_autoKey', '_isBgImage', '_isAvatar', '_isStamp'];

    const undoStack: string[] = [];
    const redoStack: string[] = [];
    const canUndo = ref(false);
    const canRedo = ref(false);
    let _historyLocked = false;

    function saveHistory() {
        if (_historyLocked || !fabricCanvas) return;
        undoStack.push(JSON.stringify(fabricCanvas.toObject(CUSTOM_PROPS)));
        redoStack.length = 0;
        canUndo.value = undoStack.length > 1;
        canRedo.value = false;
    }

    function restoreFromJSON(json: string) {
        if (!fabricCanvas) return;
        _historyLocked = true;
        fabricCanvas.loadFromJSON(json).then(() => {
            fabricCanvas!.renderAll();
            _historyLocked = false;
        });
    }

    function undo() {
        if (!fabricCanvas || undoStack.length <= 1) return;
        const current = undoStack.pop()!;
        redoStack.push(current);
        const prev = undoStack[undoStack.length - 1]!;
        restoreFromJSON(prev);
        canUndo.value = undoStack.length > 1;
        canRedo.value = true;
    }

    function redo() {
        if (!fabricCanvas || !redoStack.length) return;
        const next = redoStack.pop()!;
        undoStack.push(next);
        restoreFromJSON(next);
        canUndo.value = undoStack.length > 1;
        canRedo.value = redoStack.length > 0;
    }

    const selectedType = ref<'none' | 'text' | 'shape' | 'image' | 'stamp' | 'mixed' | 'mixed-text' | 'mixed-shape'>('none');
    const selFill = ref('#000000');
    const selTextColor = ref('#000000');
    const selFontFamily = ref('Arial');
    const selBold = ref(false);
    const selUnderline = ref(false);
    const selFontSize = ref(40);
    const selStampFill = ref('#ffffff');
    const selStampStroke = ref('#c30873');

    const hasTextSelected = computed(() => selectedType.value === 'text' || selectedType.value === 'mixed-text' || selectedType.value === 'mixed');
    const hasShapeSelected = computed(() => selectedType.value === 'shape' || selectedType.value === 'mixed-shape' || selectedType.value === 'mixed');
    const hasStampSelected = computed(() => selectedType.value === 'stamp');
    const hasObjectSelected = computed(() => selectedType.value !== 'none');

    function isTextType(type: string | undefined): boolean {
        return type === 'textbox' || type === 'i-text' || type === 'text';
    }

    function isShapeType(type: string | undefined): boolean {
        return !!type && !isTextType(type) && type !== 'image' && type !== 'activeselection';
    }

    function getSelectionType(obj: FabricObject | null | undefined): 'none' | 'text' | 'shape' | 'image' | 'stamp' | 'mixed' | 'mixed-text' | 'mixed-shape' {
        if (!obj) return 'none';
        if ((obj as AutoFabricObject)._isStamp) return 'stamp';
        if (obj.type === 'activeselection') {
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            const objects = (obj as any)._objects as FabricObject[] | undefined;
            if (!objects || !objects.length) return 'mixed';
            const allStamps = objects.every((o) => (o as AutoFabricObject)._isStamp);
            if (allStamps) return 'stamp';
            const allText = objects.every((o) => isTextType(o.type));
            if (allText) return 'mixed-text';
            const allShape = objects.every((o) => isShapeType(o.type));
            if (allShape) return 'mixed-shape';
            return 'mixed';
        }
        if (isTextType(obj.type)) return 'text';
        if (obj.type === 'image') return 'image';
        return 'shape';
    }

    function getActiveObjects(): FabricObject[] {
        if (!fabricCanvas) return [];
        const active = fabricCanvas.getActiveObject();
        if (!active) return [];
        if (active.type === 'activeselection') {
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            return (active as any)._objects as FabricObject[] ?? [];
        }
        return [active];
    }

    function readSelectionProps(obj: FabricObject | null | undefined) {
        if (!obj) {
            selectedType.value = 'none';
            return;
        }
        selectedType.value = getSelectionType(obj);
        selFill.value = typeof obj.fill === 'string' ? obj.fill : '#000000';

        if (selectedType.value === 'stamp') {
            readStampProps(obj);
        }

        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const objects = obj.type === 'activeselection' ? ((obj as any)._objects as FabricObject[] ?? []) : [];
        const firstText = obj.type === 'activeselection'
            ? objects.find((o) => isTextType(o.type))
            : (isTextType(obj.type) ? obj : undefined);

        if (firstText) {
            const txtObj = firstText as InstanceType<typeof Textbox>;
            selFill.value = typeof firstText.fill === 'string' ? firstText.fill : '#000000';
            selTextColor.value = selFill.value;
            selFontFamily.value = txtObj.fontFamily ?? 'Arial';
            selBold.value = txtObj.fontWeight === 'bold' || (txtObj.fontWeight as number) >= 700;
            selUnderline.value = !!txtObj.underline;
            selFontSize.value = txtObj.fontSize ?? 40;
        }
    }

    function readStampProps(obj: FabricObject) {
        const stamps = obj.type === 'activeselection'
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            ? ((obj as any)._objects as FabricObject[] ?? []).filter((o) => (o as AutoFabricObject)._isStamp)
            : [(obj as AutoFabricObject)._isStamp ? obj : null].filter(Boolean) as FabricObject[];
        if (!stamps.length) return;
        const first = stamps[0];
        if (first instanceof Group) {
            const children = first.getObjects();
            const bgShape = children.find((c) => c.type === 'rect' || c.type === 'path' || c.type === 'polygon' || c.type === 'ellipse' || c.type === 'circle');
            const textChild = children.find((c) => isTextType(c.type));
            if (bgShape) {
                selStampFill.value = typeof bgShape.fill === 'string' ? bgShape.fill : '#ffffff';
                selStampStroke.value = typeof bgShape.stroke === 'string' ? bgShape.stroke : '#c30873';
            }
            if (!bgShape && textChild) {
                selStampStroke.value = typeof textChild.fill === 'string' ? textChild.fill : '#c30873';
            }
        }
    }

    function setupCanvasSelectionListeners() {
        if (!fabricCanvas) return;
        fabricCanvas.on('selection:created', (e) => {
            _ignoreSelPropWatch = true;
            readSelectionProps(e.selected?.[0] && e.selected.length === 1 ? e.selected[0] : fabricCanvas?.getActiveObject());
            _ignoreSelPropWatch = false;
        });
        fabricCanvas.on('selection:updated', (e) => {
            _ignoreSelPropWatch = true;
            readSelectionProps(e.selected?.[0] && e.selected.length === 1 ? e.selected[0] : fabricCanvas?.getActiveObject());
            _ignoreSelPropWatch = false;
        });
        fabricCanvas.on('selection:cleared', () => {
            selectedType.value = 'none';
        });
        fabricCanvas.on('object:modified', () => {
            _ignoreSelPropWatch = true;
            readSelectionProps(fabricCanvas?.getActiveObject());
            _ignoreSelPropWatch = false;
            saveHistory();
        });
    }

    function _onKeyDown(e: KeyboardEvent) {
        if (!fabricCanvas) return;
        const tag = (e.target as HTMLElement)?.tagName;
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
        const activeObj = fabricCanvas.getActiveObject();
        if (activeObj && 'isEditing' in activeObj && (activeObj as { isEditing?: boolean }).isEditing) return;

        if (e.key === 'Delete' || e.key === 'Backspace') {
            e.preventDefault();
            deleteSelected();
        }

        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') {
            e.preventDefault();
            if (e.shiftKey) {
                redo();
            } else {
                undo();
            }
        }
    }

    function initFabricCanvas() {
        const el = document.getElementById('canvas1') as HTMLCanvasElement;
        if (!el) return;
        fabricCanvas = new Canvas(el, {
            width: canvasWidth.value ?? 1080,
            height: canvasHeight.value ?? 1920,
            backgroundColor: '#ffffff',
            preserveObjectStacking: true,
        });
        cleanupGuidelines = initAligningGuidelines(fabricCanvas, {
            lineColor: '#ff4081',
            lineWidth: 0.75,
            margin: 4,
        });
        setupCanvasSelectionListeners();
        setupContextMenuListener();
        document.addEventListener('keydown', _onKeyDown);
        undoStack.length = 0;
        redoStack.length = 0;
        saveHistory();
    }

    function disposeFabricCanvas() {
        document.removeEventListener('keydown', _onKeyDown);
        cleanupGuidelines?.();
        cleanupGuidelines = null;
        fabricCanvas?.dispose();
        fabricCanvas = null;
        undoStack.length = 0;
        redoStack.length = 0;
        canUndo.value = false;
        canRedo.value = false;
    }

    function clearAutoObjects(keyPrefix?: string) {
        if (!fabricCanvas) return;
        const toRemove = fabricCanvas.getObjects().filter((o) => {
            const ext = o as AutoFabricObject;
            if (ext._tag !== AUTO_TAG) return false;
            if (keyPrefix) return ext._autoKey?.startsWith(keyPrefix) ?? false;
            return true;
        });
        toRemove.forEach((o) => fabricCanvas!.remove(o));
        fabricCanvas.renderAll();
    }

    function findAutoObject(key: string): AutoFabricObject | undefined {
        if (!fabricCanvas) return undefined;
        return fabricCanvas.getObjects().find((o) => (o as AutoFabricObject)._autoKey === key) as AutoFabricObject | undefined;
    }

    function addAutoText(
        text: string,
        left: number,
        top: number,
        opts: { fontWeight?: string; fontSize?: number; fontFamily?: string; fill?: string; width?: number } = {},
        defaultTextSize: number = 30,
        defaultFont: string = 'Arial',
        defaultTextColor: string = '#111111',
        autoKey?: string,
    ) {
        if (!fabricCanvas) return;

        if (autoKey) {
            const existing = findAutoObject(autoKey);
            if (existing && (existing.type === 'text' || existing.type === 'textbox' || existing.type === 'i-text')) {
                const txtObj = existing as InstanceType<typeof Textbox>;
                txtObj.set('text', text);
                if (opts.width !== undefined) txtObj.set('width', opts.width);
                fabricCanvas.renderAll();
                return;
            }
        }

        const t = new Textbox(text, {
            left,
            top,
            fontSize: opts.fontSize ?? defaultTextSize,
            fontFamily: opts.fontFamily ?? defaultFont,
            fill: opts.fill ?? defaultTextColor,
            fontWeight: (opts.fontWeight ?? 'normal') as string,
            width: opts.width ?? 500,
            splitByGrapheme: false,
        });
        (t as AutoFabricObject)._tag = AUTO_TAG;
        if (autoKey) (t as AutoFabricObject)._autoKey = autoKey;
        fabricCanvas.add(t);
    }

    function addAutoLine(left: number, top: number, width: number, underlineColor: string, height: number = 5, autoKey?: string) {
        if (!fabricCanvas) return;

        if (autoKey) {
            const existing = findAutoObject(autoKey);
            if (existing) {
                existing.set('fill', underlineColor);
                existing.set('width', width);
                fabricCanvas.renderAll();
                return;
            }
        }

        const r = new Rect({
            left, top, width, height,
            fill: underlineColor,
            strokeWidth: 0,
            selectable: true,
        });
        (r as AutoFabricObject)._tag = AUTO_TAG;
        if (autoKey) (r as AutoFabricObject)._autoKey = autoKey;
        fabricCanvas.add(r);
    }

    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const canvasClearAndLoadImage = async (uploadedImageBlob: Ref<Blob | File | null>, imageBlob: any = null, materialType: 'event' | 'eventList') => {
        clearAutoObjects();
        if (fabricCanvas) {
            const existingBg = fabricCanvas.getObjects().find((o) => (o as AutoFabricObject)._isBgImage);
            if (existingBg) fabricCanvas.remove(existingBg);
        }
        if (!!uploadedImageBlob.value && imageBlob === null) {
            imageBlob = uploadedImageBlob.value;
        }
        if (!imageBlob) {
            let imageId = 'backgroundImage_' + selectedDimensionInfoBox.value?.backgroundImageDefaultId;
            if (materialType === 'event') {
                imageId = 'backgroundImage_default_event_main_category';
                await new Promise((r) => setTimeout(r, 50));
            }
            const imageEl = document.getElementById(imageId) as HTMLImageElement;
            if (!imageEl || !fabricCanvas) return;
            const bgImg = new FabricImage(imageEl, { left: 0, top: 0, selectable: false, evented: false });
            (bgImg as AutoFabricObject)._isBgImage = true;
            fabricCanvas.insertAt(0, bgImg);
            fabricCanvas.renderAll();
        } else {
            if (!fabricCanvas) return;
            const bitmap = await createImageBitmap(imageBlob);
            const offscreen = document.createElement('canvas');
            offscreen.width = bitmap.width;
            offscreen.height = bitmap.height;
            const offCtx = offscreen.getContext('2d');
            if (offCtx) offCtx.drawImage(bitmap, 0, 0);
            const bgImg = new FabricImage(offscreen, { left: 0, top: 0, selectable: false, evented: false });
            (bgImg as AutoFabricObject)._isBgImage = true;
            uploadedImageBlob.value = imageBlob;
            fabricCanvas.insertAt(0, bgImg);
            fabricCanvas.renderAll();
        }
    };

    const drawAvatarCanvas = async (
        loadOrganisationLogo: Ref<boolean>,
        organisationAvatar: Ref<File | null>,
    ) => {
        if (!loadOrganisationLogo.value || !organisationAvatar.value || !fabricCanvas) return;
        const existingAvatar = fabricCanvas.getObjects().find((o) => (o as AutoFabricObject)._isAvatar);
        if (existingAvatar) fabricCanvas.remove(existingAvatar);

        const bitmap = await createImageBitmap(organisationAvatar.value);
        const offscreen = document.createElement('canvas');
        offscreen.width = bitmap.width;
        offscreen.height = bitmap.height;
        const offCtx = offscreen.getContext('2d');
        if (offCtx) offCtx.drawImage(bitmap, 0, 0);

        const maxAvatarWidth = 400;
        const maxAvatarHeight = 180;
        let dWidth = bitmap.width;
        let dHeight = bitmap.height;
        if (dWidth > maxAvatarWidth || dHeight > maxAvatarHeight) {
            const ratio = bitmap.width / bitmap.height;
            if (dWidth > maxAvatarWidth) { dWidth = maxAvatarWidth; dHeight = maxAvatarWidth / ratio; }
            if (dHeight > maxAvatarHeight) { dHeight = maxAvatarHeight; dWidth = maxAvatarHeight * ratio; }
        }
        const scaleX = dWidth / bitmap.width;
        const scaleY = dHeight / bitmap.height;
        const avatarImg = new FabricImage(offscreen, {
            left: (selectedDimensionInfoBox.value?.logoStartX ?? 0) - dWidth,
            top: selectedDimensionInfoBox.value?.logoStartY ?? 0,
            scaleX, scaleY, selectable: true,
        });
        (avatarImg as AutoFabricObject)._isAvatar = true;
        (avatarImg as AutoFabricObject)._tag = AUTO_TAG;
        (avatarImg as AutoFabricObject)._autoKey = 'avatar';
        fabricCanvas.add(avatarImg);
        fabricCanvas.renderAll();
    };

    const downloadFabricCanvas = (downloadFileName: string) => {
        if (!fabricCanvas) return;
        fabricCanvas.discardActiveObject();
        fabricCanvas.renderAll();
        const dataUrl = fabricCanvas.toDataURL({ format: 'png', multiplier: 1, quality: 1 });
        const link = document.createElement('a');
        link.download = downloadFileName.split(' ').join('-').toLowerCase() + '.png';
        link.href = dataUrl;
        link.click();
    };

    const addUserText = (font: string, textColor: string) => {
        if (!fabricCanvas) return;
        const t = new Textbox('Neuer Text', {
            left: 100, top: 100, fontSize: 40,
            fontFamily: font, fill: textColor, width: 300, editable: true,
        });
        fabricCanvas.add(t);
        fabricCanvas.setActiveObject(t);
        fabricCanvas.renderAll();
        saveHistory();
    };

    function addShapeToCanvas(shape: FabricObject) {
        if (!fabricCanvas) return;
        fabricCanvas.add(shape);
        fabricCanvas.setActiveObject(shape);
        fabricCanvas.renderAll();
        saveHistory();
    }

    const addUserRect = (fill: string) => {
        addShapeToCanvas(new Rect({ left: 100, top: 100, width: 200, height: 150, fill, strokeWidth: 0 }));
    };
    const addUserCircle = (fill: string) => {
        addShapeToCanvas(new Circle({ left: 100, top: 100, radius: 100, fill, strokeWidth: 0 }));
    };
    const addUserEllipse = (fill: string) => {
        addShapeToCanvas(new Ellipse({ left: 100, top: 100, rx: 120, ry: 70, fill, strokeWidth: 0 }));
    };
    const addUserTriangle = (fill: string) => {
        addShapeToCanvas(new Triangle({ left: 100, top: 100, width: 180, height: 160, fill, strokeWidth: 0 }));
    };
    const addUserLine = (fill: string) => {
        addShapeToCanvas(new Line([50, 50, 350, 50], { left: 100, top: 100, stroke: fill, strokeWidth: 6 }));
    };
    const addUserStar = (fill: string) => {
        const outerR = 100, innerR = 45, points = 5;
        const starPoints: { x: number; y: number }[] = [];
        for (let i = 0; i < points * 2; i++) {
            const r = i % 2 === 0 ? outerR : innerR;
            const angle = (Math.PI / points) * i - Math.PI / 2;
            starPoints.push({ x: r * Math.cos(angle), y: r * Math.sin(angle) });
        }
        addShapeToCanvas(new Polygon(starPoints, { left: 100, top: 100, fill, strokeWidth: 0 }));
    };
    const addUserArrow = (fill: string) => {
        addShapeToCanvas(new Path('M 0 40 L 160 40 L 160 0 L 240 60 L 160 120 L 160 80 L 0 80 Z', { left: 100, top: 100, fill, strokeWidth: 0 }));
    };
    const addUserHexagon = (fill: string) => {
        const r = 90;
        const hexPoints: { x: number; y: number }[] = [];
        for (let i = 0; i < 6; i++) {
            const angle = (Math.PI / 3) * i - Math.PI / 6;
            hexPoints.push({ x: r * Math.cos(angle), y: r * Math.sin(angle) });
        }
        addShapeToCanvas(new Polygon(hexPoints, { left: 100, top: 100, fill, strokeWidth: 0 }));
    };

    const addUserImage = async (files: File[] | null) => {
        if (!files || !files[0] || !fabricCanvas) return;
        const file = files[0];
        if (file.size > 2 * 1024 * 1024) {
            throw new Error('Die Datei ist zu groß. Bitte wählen Sie ein Bild mit maximal 2 MB.');
        }

        if (file.type === 'image/svg+xml' || file.name.toLowerCase().endsWith('.svg')) {
            const svgString = await file.text();
            const { objects: svgObjects } = await loadSVGFromString(svgString);
            const validObjects = svgObjects.filter(Boolean) as FabricObject[];
            if (!validObjects.length) return;
            const group = new Group(validObjects);
            const cw = canvasWidth.value ?? 1080;
            const ch = canvasHeight.value ?? 1920;
            const maxW = cw * 0.8;
            const maxH = ch * 0.8;
            const origW = group.width ?? 200;
            const origH = group.height ?? 200;
            const scale = Math.min(maxW / origW, maxH / origH, 1);
            group.set({ left: 50, top: 50, scaleX: scale, scaleY: scale });
            fabricCanvas.add(group);
            fabricCanvas.setActiveObject(group);
            fabricCanvas.renderAll();
            saveHistory();
            return;
        } else {
            const bitmap = await createImageBitmap(file);
            const offscreen = document.createElement('canvas');
            offscreen.width = bitmap.width;
            offscreen.height = bitmap.height;
            const offCtx = offscreen.getContext('2d');
            if (offCtx) offCtx.drawImage(bitmap, 0, 0);
            const cw = canvasWidth.value ?? 1080;
            const ch = canvasHeight.value ?? 1920;
            const maxW = cw * 0.8;
            const maxH = ch * 0.8;
            const scale = Math.min(maxW / bitmap.width, maxH / bitmap.height, 1);
            const img = new FabricImage(offscreen, { left: 50, top: 50, scaleX: scale, scaleY: scale });
            fabricCanvas.add(img);
            fabricCanvas.setActiveObject(img);
            fabricCanvas.renderAll();
            saveHistory();
        }
    };

    const addUserStamp = async (svgString: string) => {
        if (!fabricCanvas) return;
        const { objects: svgObjects } = await loadSVGFromString(svgString);
        const validObjects = svgObjects.filter(Boolean) as FabricObject[];
        if (!validObjects.length) return;
        for (const child of validObjects) {
            const t = child.type;
            if (t === 'rect' || t === 'path' || t === 'polygon' || t === 'ellipse' || t === 'circle') {
                if (!child.fill || child.fill === 'transparent' || child.fill === 'none' || child.fill === '') {
                    child.set('fill', '#ffffff');
                }
            }
        }
        const group = new Group(validObjects);
        const cw = canvasWidth.value ?? 1080;
        const maxW = cw * 0.5;
        const origW = group.width ?? 200;
        const scale = Math.min(maxW / origW, 1);
        group.set({ left: 50, top: 50, scaleX: scale, scaleY: scale });
        (group as AutoFabricObject)._isStamp = true;
        fabricCanvas.add(group);
        fabricCanvas.setActiveObject(group);
        fabricCanvas.renderAll();
        saveHistory();
    };

    function applyStampFill(val: string) {
        if (_ignoreSelPropWatch || !fabricCanvas) return;
        const objects = getActiveObjects();
        for (const obj of objects) {
            if (!(obj as AutoFabricObject)._isStamp) continue;
            if (obj instanceof Group) {
                for (const child of obj.getObjects()) {
                    if (child.type === 'rect' || child.type === 'path' || child.type === 'polygon' || child.type === 'ellipse' || child.type === 'circle') {
                        child.set('fill', val);
                    }
                }
            }
        }
        fabricCanvas.renderAll();
        saveHistory();
    }

    function applyStampStroke(val: string) {
        if (_ignoreSelPropWatch || !fabricCanvas) return;
        const objects = getActiveObjects();
        for (const obj of objects) {
            if (!(obj as AutoFabricObject)._isStamp) continue;
            if (obj instanceof Group) {
                for (const child of obj.getObjects()) {
                    if (child.type === 'rect' || child.type === 'path' || child.type === 'polygon' || child.type === 'ellipse' || child.type === 'circle') {
                        child.set('stroke', val);
                    }
                    if (isTextType(child.type)) {
                        child.set('fill', val);
                    }
                }
            }
        }
        fabricCanvas.renderAll();
        saveHistory();
    }

    const deleteSelected = () => {
        if (!fabricCanvas) return;
        const active = fabricCanvas.getActiveObjects();
        if (!active.length) return;
        active.forEach((obj) => fabricCanvas!.remove(obj));
        fabricCanvas.discardActiveObject();
        fabricCanvas.renderAll();
        saveHistory();
    };

    const duplicateSelected = () => {
        if (!fabricCanvas) return;
        const activeObjects = fabricCanvas.getActiveObjects();
        if (!activeObjects.length) return;

        // Capture canvas-absolute positions BEFORE discarding the selection.
        // Objects inside an ActiveSelection have left/top relative to the
        // selection's own origin, so cloning them raw would place all clones
        // at (0,0). calcTransformMatrix gives us the real canvas coordinates.
        const absolutePositions = activeObjects.map((obj) => {
            const decomposed = util.qrDecompose(obj.calcTransformMatrix());
            return { left: decomposed.translateX, top: decomposed.translateY };
        });

        Promise.all(activeObjects.map((obj) => obj.clone())).then((clones: FabricObject[]) => {
            fabricCanvas!.discardActiveObject();

            clones.forEach((cloned, i) => {
                const abs = absolutePositions[i];
                cloned.set({
                    left: (abs?.left ?? cloned.left ?? 0) + 20,
                    top: (abs?.top ?? cloned.top ?? 0) + 20,
                    evented: true,
                });
                fabricCanvas!.add(cloned);
            });

            if (clones.length === 1 && clones[0]) {
                fabricCanvas!.setActiveObject(clones[0]);
            } else {
                const sel = new ActiveSelection(clones, { canvas: fabricCanvas! });
                fabricCanvas!.setActiveObject(sel);
            }

            fabricCanvas!.renderAll();
            saveHistory();
        });
    };

    function bringForward() {
        if (!fabricCanvas) return;
        const objects = getActiveObjects();
        for (const obj of objects) {
            fabricCanvas.bringObjectForward(obj);
        }
        fabricCanvas.renderAll();
        saveHistory();
    }

    function sendBackward() {
        if (!fabricCanvas) return;
        const objects = getActiveObjects();
        const bgCount = fabricCanvas.getObjects().filter(
            (o) => (o as AutoFabricObject)._isBgImage,
        ).length;
        for (const obj of [...objects].reverse()) {
            const idx = fabricCanvas.getObjects().indexOf(obj);
            if (idx > bgCount) {
                fabricCanvas.sendObjectBackwards(obj);
            }
        }
        fabricCanvas.renderAll();
        saveHistory();
    }

    function bringToFront() {
        if (!fabricCanvas) return;
        const objects = getActiveObjects();
        for (const obj of objects) {
            fabricCanvas.bringObjectToFront(obj);
        }
        fabricCanvas.renderAll();
        saveHistory();
    }

    function sendToBack() {
        if (!fabricCanvas) return;
        const objects = getActiveObjects();
        const bgCount = fabricCanvas.getObjects().filter(
            (o) => (o as AutoFabricObject)._isBgImage,
        ).length;
        for (const obj of [...objects].reverse()) {
            fabricCanvas.sendObjectToBack(obj);
            if (bgCount > 0) {
                fabricCanvas.bringObjectForward(obj);
            }
        }
        fabricCanvas.renderAll();
        saveHistory();
    }

    const contextMenu = ref<{ visible: boolean; x: number; y: number }>({ visible: false, x: 0, y: 0 });

    function setupContextMenuListener() {
        if (!fabricCanvas) return;
        const canvasEl = fabricCanvas.upperCanvasEl;
        canvasEl.addEventListener('contextmenu', (e: MouseEvent) => {
            e.preventDefault();
            e.stopPropagation();
            const active = fabricCanvas?.getActiveObject();
            if (!active) {
                contextMenu.value = { visible: false, x: 0, y: 0 };
                return;
            }
            const rect = canvasEl.getBoundingClientRect();
            contextMenu.value = {
                visible: true,
                x: e.clientX - rect.left,
                y: e.clientY - rect.top,
            };
        });
        fabricCanvas.on('mouse:down', () => {
            if (contextMenu.value.visible) {
                hideContextMenu();
            }
        });
    }

    function hideContextMenu() {
        contextMenu.value = { visible: false, x: 0, y: 0 };
    }

    function applySelFill(val: string) {
        if (_ignoreSelPropWatch || !fabricCanvas) return;
        const objects = getActiveObjects();
        if (!objects.length) return;
        for (const obj of objects) {
            if (isShapeType(obj.type)) {
                obj.set('fill', val);
            }
        }
        fabricCanvas.renderAll();
        saveHistory();
    }

    function applySelTextColor(val: string) {
        if (_ignoreSelPropWatch || !fabricCanvas) return;
        const objects = getActiveObjects();
        if (!objects.length) return;
        for (const obj of objects) {
            if (isTextType(obj.type)) {
                obj.set('fill', val);
            }
        }
        fabricCanvas.renderAll();
        saveHistory();
    }

    function applySelFontFamily(val: string) {
        if (_ignoreSelPropWatch || !fabricCanvas) return;
        const objects = getActiveObjects();
        if (!objects.length) return;
        for (const obj of objects) {
            if (isTextType(obj.type)) {
                (obj as InstanceType<typeof Textbox>).set('fontFamily', val);
            }
        }
        fabricCanvas.renderAll();
        saveHistory();
    }

    function applySelBold(val: boolean) {
        if (_ignoreSelPropWatch || !fabricCanvas) return;
        const objects = getActiveObjects();
        if (!objects.length) return;
        for (const obj of objects) {
            if (isTextType(obj.type)) {
                (obj as InstanceType<typeof Textbox>).set('fontWeight', val ? 'bold' : 'normal');
            }
        }
        fabricCanvas.renderAll();
        saveHistory();
    }

    function applySelUnderline(val: boolean) {
        if (_ignoreSelPropWatch || !fabricCanvas) return;
        const objects = getActiveObjects();
        if (!objects.length) return;
        for (const obj of objects) {
            if (isTextType(obj.type)) {
                (obj as InstanceType<typeof Textbox>).set('underline', val);
            }
        }
        fabricCanvas.renderAll();
        saveHistory();
    }

    function applySelFontSize(val: number) {
        if (_ignoreSelPropWatch || !fabricCanvas) return;
        const objects = getActiveObjects();
        if (!objects.length) return;
        for (const obj of objects) {
            if (isTextType(obj.type)) {
                (obj as InstanceType<typeof Textbox>).set('fontSize', val);
            }
        }
        fabricCanvas.renderAll();
        saveHistory();
    }

    function renderAll() {
        fabricCanvas?.renderAll();
    }

    function exportUserObjects(): SerializedFabricObject[] {
        if (!fabricCanvas) return [];
        const userObjs = fabricCanvas.getObjects().filter((o) => {
            const ext = o as AutoFabricObject;
            return ext._tag !== AUTO_TAG && !ext._isBgImage && !ext._isAvatar;
        });
        return userObjs.map((o) => o.toObject(CUSTOM_PROPS));
    }

    function exportAutoOverrides(): Record<string, SerializedFabricObject> {
        if (!fabricCanvas) return {};
        const result: Record<string, SerializedFabricObject> = {};
        for (const o of fabricCanvas.getObjects()) {
            const ext = o as AutoFabricObject;
            if (ext._tag === AUTO_TAG && ext._autoKey) {
                result[ext._autoKey] = o.toObject(CUSTOM_PROPS);
            }
        }
        return result;
    }

    function applyAutoOverrides(overrides: Record<string, SerializedFabricObject>, positionOnly = false) {
        if (!fabricCanvas || !overrides) return;
        for (const o of fabricCanvas.getObjects()) {
            const ext = o as AutoFabricObject;
            if (ext._tag !== AUTO_TAG || !ext._autoKey) continue;
            const saved = overrides[ext._autoKey];
            if (!saved) continue;
            if (saved.left !== undefined) o.set('left', saved.left);
            if (saved.top !== undefined) o.set('top', saved.top);
            if (saved.scaleX !== undefined) o.set('scaleX', saved.scaleX);
            if (saved.scaleY !== undefined) o.set('scaleY', saved.scaleY);
            if (saved.angle !== undefined) o.set('angle', saved.angle);
            if (!positionOnly && (o.type === 'text' || o.type === 'textbox' || o.type === 'i-text')) {
                const txtObj = o as InstanceType<typeof Textbox>;
                if (saved.fontSize !== undefined) txtObj.set('fontSize', saved.fontSize);
                if (saved.fontFamily !== undefined) txtObj.set('fontFamily', saved.fontFamily);
                if (saved.fill !== undefined) txtObj.set('fill', saved.fill);
                if (saved.fontWeight !== undefined) txtObj.set('fontWeight', saved.fontWeight);
            }
        }
        fabricCanvas.renderAll();
    }

    async function importUserObjects(objects: SerializedFabricObject[]) {
        if (!fabricCanvas) return;
        const existingUser = fabricCanvas.getObjects().filter((o) => {
            const ext = o as AutoFabricObject;
            return ext._tag !== AUTO_TAG && !ext._isBgImage && !ext._isAvatar;
        });
        existingUser.forEach((o) => fabricCanvas!.remove(o));

        for (const objData of objects) {
            try {
                const [enlivened] = await util.enlivenObjects([objData]);
                if (enlivened) {
                    fabricCanvas.add(enlivened as FabricObject);
                }
            } catch {
                // silently skip objects that can't be restored
            }
        }
        fabricCanvas.renderAll();
        saveHistory();
    }

    type ZOrderEntry = { kind: 'bg' } | { kind: 'avatar' } | { kind: 'auto'; autoKey: string } | { kind: 'user'; index: number };

    function exportZOrder(): ZOrderEntry[] {
        if (!fabricCanvas) return [];
        const order: ZOrderEntry[] = [];
        let userIdx = 0;
        for (const o of fabricCanvas.getObjects()) {
            const ext = o as AutoFabricObject;
            if (ext._isBgImage) {
                order.push({ kind: 'bg' });
            } else if (ext._isAvatar) {
                order.push({ kind: 'avatar' });
            } else if (ext._tag === AUTO_TAG && ext._autoKey) {
                order.push({ kind: 'auto', autoKey: ext._autoKey });
            } else {
                order.push({ kind: 'user', index: userIdx++ });
            }
        }
        return order;
    }

    function applyZOrder(savedOrder: ZOrderEntry[]) {
        if (!fabricCanvas || !savedOrder?.length) return;

        const objects = fabricCanvas.getObjects();

        const bgObj = objects.find((o) => (o as AutoFabricObject)._isBgImage);
        const avatarObj = objects.find((o) => (o as AutoFabricObject)._isAvatar);
        const autoMap = new Map<string, FabricObject>();
        const userList: FabricObject[] = [];

        for (const o of objects) {
            const ext = o as AutoFabricObject;
            if (ext._isBgImage || ext._isAvatar) continue;
            if (ext._tag === AUTO_TAG && ext._autoKey) {
                autoMap.set(ext._autoKey, o);
            } else {
                userList.push(o);
            }
        }

        const reordered: FabricObject[] = [];
        for (const entry of savedOrder) {
            let obj: FabricObject | undefined;
            if (entry.kind === 'bg') obj = bgObj ?? undefined;
            else if (entry.kind === 'avatar') obj = avatarObj ?? undefined;
            else if (entry.kind === 'auto') obj = autoMap.get(entry.autoKey);
            else if (entry.kind === 'user') obj = userList[entry.index];
            if (obj) reordered.push(obj);
        }

        for (const object of objects) {
            if (!reordered.includes(object)) reordered.push(object);
        }


        for (const object of [...objects]) {
            fabricCanvas.remove(object);
        }
        for (const object of reordered) {
            fabricCanvas.add(object);
        }
        fabricCanvas.renderAll();
    }

    function exportBackgroundDataUrl(): string | null {
        if (!fabricCanvas) return null;
        const bgObj = fabricCanvas.getObjects().find(
            (o) => (o as AutoFabricObject)._isBgImage,
        );
        if (!bgObj) return null;
        const el = (bgObj as FabricImage).getElement?.();
        if (!el) return null;
        try {
            const tmpCanvas = document.createElement('canvas');
            tmpCanvas.width = (el as HTMLCanvasElement | HTMLImageElement).width ?? canvasWidth.value ?? 1080;
            tmpCanvas.height = (el as HTMLCanvasElement | HTMLImageElement).height ?? canvasHeight.value ?? 1920;
            const ctx = tmpCanvas.getContext('2d');
            if (ctx) ctx.drawImage(el as CanvasImageSource, 0, 0);
            return tmpCanvas.toDataURL('image/png');
        } catch {
            return null;
        }
    }

    async function importBackgroundDataUrl(dataUrl: string) {
        if (!fabricCanvas) return;
        const existingBg = fabricCanvas.getObjects().find(
            (o) => (o as AutoFabricObject)._isBgImage,
        );
        if (existingBg) fabricCanvas.remove(existingBg);

        return new Promise<void>((resolve) => {
            const img = new Image();
            img.onload = () => {
                const bgImg = new FabricImage(img, {
                    left: 0, top: 0,
                    selectable: false, evented: false,
                    hasControls: false, lockMovementX: true, lockMovementY: true,
                });
                (bgImg as AutoFabricObject)._isBgImage = true;
                fabricCanvas!.insertAt(0, bgImg);
                fabricCanvas!.renderAll();
                resolve();
            };
            img.src = dataUrl;
        });
    }

    return {
        selectedType,
        selFill,
        selTextColor,
        selFontFamily,
        selBold,
        selUnderline,
        selFontSize,
        canUndo,
        canRedo,
        hasTextSelected,
        hasShapeSelected,
        hasStampSelected,
        hasObjectSelected,
        initFabricCanvas,
        disposeFabricCanvas,
        clearAutoObjects,
        addAutoText,
        addAutoLine,
        canvasClearAndLoadImage,
        drawAvatarCanvas,
        downloadFabricCanvas,
        addUserText,
        addUserRect,
        addUserCircle,
        addUserEllipse,
        addUserTriangle,
        addUserLine,
        addUserStar,
        addUserArrow,
        addUserHexagon,
        addUserImage,
        addUserStamp,
        deleteSelected,
        duplicateSelected,
        bringForward,
        sendBackward,
        bringToFront,
        sendToBack,
        contextMenu,
        hideContextMenu,
        applySelFill,
        applySelTextColor,
        applySelFontFamily,
        applySelBold,
        applySelUnderline,
        applySelFontSize,
        applyStampFill,
        applyStampStroke,
        selStampFill,
        selStampStroke,
        undo,
        redo,
        renderAll,
        exportUserObjects,
        exportAutoOverrides,
        applyAutoOverrides,
        importUserObjects,
        exportBackgroundDataUrl,
        importBackgroundDataUrl,
        exportZOrder,
        applyZOrder,
    };
}
