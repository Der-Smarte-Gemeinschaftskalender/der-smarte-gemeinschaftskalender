import { Point, type Canvas, type FabricObject } from 'fabric';

export interface AligningGuidelinesOptions {
    margin?: number;
    lineColor?: string;
    lineWidth?: number;
}

type ACoords = NonNullable<FabricObject['aCoords']>;
type ACoordsWithCenter = ACoords & { c: Point };

interface VLine { x: number; y1: number; y2: number }
interface HLine { y: number; x1: number; x2: number }

export function initAligningGuidelines(
    canvas: Canvas,
    options: AligningGuidelinesOptions = {},
): () => void {
    const margin = options.margin ?? 4;
    const lineColor = options.lineColor ?? '#ff4081';
    const lineWidth = options.lineWidth ?? 0.75;

    const verticalLines: VLine[] = [];
    const horizontalLines: HLine[] = [];
    let activeObj: FabricObject | null = null;
    let viewportTransform: number[] = [1, 0, 0, 1, 0, 0];

    const keys = <T extends object>(o: T) => Object.keys(o) as (keyof T)[];

    function centerFromACoords(ac: ACoords): Point {
        return new Point(
            (ac.tl.x + ac.br.x) / 2,
            (ac.tl.y + ac.br.y) / 2,
        );
    }

    function coordsWithCenter(obj: FabricObject): ACoordsWithCenter {
        return { ...obj.aCoords!, c: obj.getCenterPoint() } as ACoordsWithCenter;
    }

    function draggingCoords(obj: FabricObject): ACoordsWithCenter {
        const ac = obj.aCoords!;
        const acCenter = centerFromACoords(ac);
        const realCenter = obj.getCenterPoint();
        const dx = acCenter.x - realCenter.x;
        const dy = acCenter.y - realCenter.y;
        const corrected = {} as Record<string, Point>;
        for (const k of keys(ac)) {
            corrected[k as string] = new Point(ac[k].x - dx, ac[k].y - dy);
        }
        corrected.c = realCenter;
        return corrected as unknown as ACoordsWithCenter;
    }

    function isInRange(a: number, b: number): boolean {
        return Math.abs(Math.round(a) - Math.round(b)) <= margin / canvas.getZoom();
    }

    function getCtx(): CanvasRenderingContext2D {
        return canvas.getSelectionContext();
    }

    function drawLine(x1: number, y1: number, x2: number, y2: number) {
        const ctx = getCtx();
        const vt = canvas.viewportTransform ?? viewportTransform;
        const p1 = new Point(x1, y1).transform(vt);
        const p2 = new Point(x2, y2).transform(vt);
        ctx.save();
        ctx.lineWidth = lineWidth;
        ctx.strokeStyle = lineColor;
        ctx.beginPath();
        ctx.moveTo(p1.x, p1.y);
        ctx.lineTo(p2.x, p2.y);
        ctx.stroke();
        ctx.restore();
    }

    function drawVerticalLine(coords: VLine) {
        if (!activeObj?.aCoords) return;
        const mc = draggingCoords(activeObj);
        const match = keys(mc).some(k => Math.abs(mc[k].x - coords.x) < 0.0001);
        if (!match) return;
        drawLine(coords.x, Math.min(coords.y1, coords.y2), coords.x, Math.max(coords.y1, coords.y2));
    }

    function drawHorizontalLine(coords: HLine) {
        if (!activeObj?.aCoords) return;
        const mc = draggingCoords(activeObj);
        const match = keys(mc).some(k => Math.abs(mc[k].y - coords.y) < 0.0001);
        if (!match) return;
        drawLine(Math.min(coords.x1, coords.x2), coords.y, Math.max(coords.x1, coords.x2), coords.y);
    }

    function traverseObjects(active: FabricObject, objects: FabricObject[]) {
        const movCoords = draggingCoords(active);
        const snapXPoints: number[] = [];
        const snapYPoints: number[] = [];

        for (const obj of objects) {
            if (obj === active) continue;
            const objC = coordsWithCenter(obj);

            for (const activeKey of keys(movCoords)) {
                for (const objKey of keys(objC)) {
                    if (isInRange(movCoords[activeKey].y, objC[objKey].y)) {
                        const y = objC[objKey].y;
                        const offset = movCoords[activeKey].y - y;
                        snapYPoints.push(movCoords.c.y - offset);

                        const x1 = Math.min(objC[objKey].x, movCoords[activeKey].x);
                        const x2 = Math.max(objC[objKey].x, movCoords[activeKey].x);
                        horizontalLines.push({ y, x1, x2 });
                    }
                }
            }

            for (const activeKey of keys(movCoords)) {
                for (const objKey of keys(objC)) {
                    if (isInRange(movCoords[activeKey].x, objC[objKey].x)) {
                        const x = objC[objKey].x;
                        const offset = movCoords[activeKey].x - x;
                        snapXPoints.push(movCoords.c.x - offset);

                        const y1 = Math.min(objC[objKey].y, movCoords[activeKey].y);
                        const y2 = Math.max(objC[objKey].y, movCoords[activeKey].y);
                        verticalLines.push({ x, y1, y2 });
                    }
                }
            }
        }

        snap(active, movCoords, snapXPoints, snapYPoints);
    }

    function snap(
        obj: FabricObject,
        movCoords: ACoordsWithCenter,
        snapXPoints: number[],
        snapYPoints: number[],
    ) {
        const nearest = (list: number[], origin: number) => {
            if (!list.length) return origin;
            const sorted = list
                .map(v => ({ v, d: Math.abs(origin - v) }))
                .sort((a, b) => a.d - b.d);
            return sorted[0]?.v ?? origin;
        };

        obj.setPositionByOrigin(
            new Point(
                nearest(snapXPoints, movCoords.c.x),
                nearest(snapYPoints, movCoords.c.y),
            ),
            'center',
            'center',
        );
    }

    function onMouseDown() {
        verticalLines.length = 0;
        horizontalLines.length = 0;
        viewportTransform = (canvas.viewportTransform ?? [1, 0, 0, 1, 0, 0]) as number[];
    }

    function onObjectMoving(e: { target?: FabricObject }) {
        const target = e.target;
        if (!target) return;
        activeObj = target;

        verticalLines.length = 0;
        horizontalLines.length = 0;

        const objects = canvas.getObjects().filter(o => o !== target);
        traverseObjects(target, objects);
    }

    function onBeforeRender() {
        try {
            canvas.clearContext(canvas.getSelectionContext());
        } catch {
            // ignore if contextTop is not ready
        }
    }

    function onAfterRender() {
        for (const vl of verticalLines) drawVerticalLine(vl);
        for (const hl of horizontalLines) drawHorizontalLine(hl);
    }

    function onMouseUp() {
        verticalLines.length = 0;
        horizontalLines.length = 0;
        activeObj = null;
        canvas.renderAll();
    }

    canvas.on('mouse:down', onMouseDown);
    canvas.on('object:moving', onObjectMoving);
    canvas.on('before:render', onBeforeRender);
    canvas.on('after:render', onAfterRender);
    canvas.on('mouse:up', onMouseUp);

    return () => {
        canvas.off('mouse:down', onMouseDown);
        canvas.off('object:moving', onObjectMoving);
        canvas.off('before:render', onBeforeRender);
        canvas.off('after:render', onAfterRender);
        canvas.off('mouse:up', onMouseUp);
        verticalLines.length = 0;
        horizontalLines.length = 0;
        activeObj = null;
    };
}
