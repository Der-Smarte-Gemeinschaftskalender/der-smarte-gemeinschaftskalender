import { randomFillSync } from 'node:crypto';
import { existsSync, mkdirSync, statSync, writeFileSync } from 'node:fs';
import { deflateSync } from 'node:zlib';

const PNG_SIGNATURE = Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]);
const GENERATED_DIR = './e2e/testFiles/generated';
const IMAGE_WIDTH = 1000;


export const DEFAULT_LARGE_IMAGE_BYTES = 1_800_000;

/** Maximale Bildgröße, die das Formular akzeptiert (siehe MobilizonFieldsFormSchema.picture im Frontend). */
export const MAX_PICTURE_BYTES = 2_097_152;

/** Bewusst über dem Formular-Limit, um die Fehlermeldung zu prüfen. */
export const OVERSIZED_IMAGE_BYTES = MAX_PICTURE_BYTES + 500_000;

/** Realistische Flyer-Größe für den JPEG-Fall (unter dem Formular-Limit). */
export const DEFAULT_JPEG_IMAGE_BYTES = 1_200_000;

// Baseline-JPEG (240x160, sRGB) als Grundgerüst für createJpegTestImage.
const BASE_JPEG_BASE64 =
    '/9j/4AAQSkZJRgABAQAAAAAAAAD/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYn' +
    'KSopGR8tMC0oMCUoKSj/2wBDAQcHBwoIChMKChMoGhYaKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgo' +
    'KCgoKCgoKCj/wAARCACgAPADASIAAhEBAxEB/8QAFwABAQEBAAAAAAAAAAAAAAAAAAQDB//EABUQAQEAAAAAAAAAAAAAAAAAAAAS' +
    '/8QAFgEBAQEAAAAAAAAAAAAAAAAAAAQF/8QAFREBAQAAAAAAAAAAAAAAAAAAABL/2gAMAwEAAhEDEQA/AOZySokluWxJTySokksl' +
    'PJKiSSyU8kqJJLJTySokkslPJKiSSyU8kqJJLJTySokkslPJKiSSyU8kqJJLJTySokkslPJKiSSyU8kqJJLJTySokkslPJKiSSyV' +
    'EEN4IR2olhBDeCCyWEEN4ILJYQQ3ggslhBDeCCyWEEN4ILJYQQ3ggslhBDeCCyWEEN4ILJYQQ3ggslhBDeCCyWEEN4ILJYQQ3ggs' +
    'lhBDeCCyWEEN4ILJUSS3ghHaiWEkt4ILJYSS3ggslhJLeCCyWEkt4ILJYSS3ggslhJLeCCyWEkt4ILJYSS3ggslhJLeCCyWEkt4I' +
    'LJYSS3ggslhJLeCCyWEkt4ILJYSS3ggslRBCiCElqJTwQoggslPBCiCCyU8EKIILJTwQoggslPBCiCCyU8EKIILJTwQoggslPBCi' +
    'CCyU8EKIILJTwQoggslPBCiCCyU8EKIILJTwQoggslPBCiCCyW8kqIIR2olPJKiCCyU8kqIILJTySoggslPJKiCCyU8kqIILJTyS' +
    'oggslPJKiCCyU8kqIILJTySoggslPJKiCCyU8kqIILJTySoggslPJKiCCyU8kqIILJbwQoklHaiU8EKJJLJTwQokkslPBCiSSyU8' +
    'EKJJLJTwQokkslPBCiSSyU8EKJJLJTwQokkslPBCiSSyU8EKJJLJTwQokkslPBCiSSyU8EKJJLJTwQokkslRBDeCEdqJYQQ3ggsl' +
    'hBDeCCyWEEN4ILJYQQ3ggslhBDeCCyWEEN4ILJYQQ3ggslhBDeCCyWEEN4ILJYQQ3ggslhBDeCCyWEEN4ILJYQQ3ggslhBDeCCyV' +
    'EEN5JR2olhBDeSSyWEEN5JLJYQQ3kkslhBDeSSyWEEN5JLJYQQ3kkslhBDeSSyWEEN5JLJYQQ3kkslhBDeSSyWEEN5JLJYQQ3kks' +
    'lhBDeSSyWEEN5JLJUSSoghHaiU8kqIILJTySoggslPJKiCCyU8kqIILJTySoggslPJKiCCyU8kqIILJTySoggslPJKiCCyU8kqII' +
    'LJTySoggslPJKiCCyU8kqIILJTySoggslvJKiSUlqJTySokkslPJKiSSyU8kqJJLJTySokkslPJKiSSyU8kqJJLJTySokkslPJKi' +
    'SSyU8kqJJLJTySokkslPJKiSSyU8kqJJLJTySokkslPJKiSSyX//2Q==';

const CRC_TABLE = (() => {
    const table = new Uint32Array(256);
    for (let n = 0; n < 256; n++) {
        let c = n;
        for (let k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
        table[n] = c >>> 0;
    }
    return table;
})();

function crc32(buffer: Buffer): number {
    let crc = 0xffffffff;
    for (let i = 0; i < buffer.length; i++) {
        crc = CRC_TABLE[(crc ^ buffer[i]!) & 0xff]! ^ (crc >>> 8);
    }
    return (crc ^ 0xffffffff) >>> 0;
}

function pngChunk(type: string, data: Buffer): Buffer {
    const length = Buffer.alloc(4);
    length.writeUInt32BE(data.length);

    const typeAndData = Buffer.concat([Buffer.from(type, 'ascii'), data]);

    const crc = Buffer.alloc(4);
    crc.writeUInt32BE(crc32(typeAndData));

    return Buffer.concat([length, typeAndData, crc]);
}


export function createLargeTestImage(
    targetBytes: number = DEFAULT_LARGE_IMAGE_BYTES,
    fileName: string = 'large-test-image.png'
): string {
    const filePath = `${GENERATED_DIR}/${fileName}`;

    if (existsSync(filePath) && statSync(filePath).size >= targetBytes) {
        return filePath;
    }

    const bytesPerRow = IMAGE_WIDTH * 3 + 1; // RGB + Filter-Byte pro Zeile
    const height = Math.max(1, Math.ceil(targetBytes / bytesPerRow));

    const raw = Buffer.alloc(bytesPerRow * height);
    randomFillSync(raw);
    for (let y = 0; y < height; y++) {
        raw[y * bytesPerRow] = 0; // Filtertyp "None"
    }

    const ihdr = Buffer.alloc(13);
    ihdr.writeUInt32BE(IMAGE_WIDTH, 0);
    ihdr.writeUInt32BE(height, 4);
    ihdr[8] = 8; // bit depth
    ihdr[9] = 2; // color type: truecolor
    ihdr[10] = 0; // compression
    ihdr[11] = 0; // filter
    ihdr[12] = 0; // interlace

    const png = Buffer.concat([
        PNG_SIGNATURE,
        pngChunk('IHDR', ihdr),
        pngChunk('IDAT', deflateSync(raw, { level: 0 })),
        pngChunk('IEND', Buffer.alloc(0)),
    ]);

    mkdirSync(GENERATED_DIR, { recursive: true });
    writeFileSync(filePath, png);

    return filePath;
}

/**
 * Erzeugt ein gültiges Baseline-JPEG in der gewünschten Dateigröße (z.B. ein "Flyer" wie ihn
 * Nutzer*innen hochladen). Die Zielgröße wird über JPEG-Kommentarsegmente (COM) aufgefüllt,
 * das Bild bleibt dabei für jeden Decoder lesbar.
 */
export function createJpegTestImage(
    targetBytes: number = DEFAULT_JPEG_IMAGE_BYTES,
    fileName: string = 'test-flyer.jpg'
): string {
    const filePath = `${GENERATED_DIR}/${fileName}`;

    if (existsSync(filePath) && statSync(filePath).size === targetBytes) {
        return filePath;
    }

    const base = Buffer.from(BASE_JPEG_BASE64, 'base64');
    const maxCommentPayload = 65_531; // 65535 minus 2 Byte Längenfeld minus 2 Byte Marker
    const parts = [base.subarray(0, 2)]; // SOI

    let missingBytes = targetBytes - base.length;
    while (missingBytes > 4) {
        const payload = Math.min(maxCommentPayload, missingBytes - 4);

        const header = Buffer.alloc(4);
        header.writeUInt16BE(0xfffe, 0); // COM-Marker
        header.writeUInt16BE(payload + 2, 2);

        parts.push(header, Buffer.alloc(payload, 0x20));
        missingBytes -= payload + 4;
    }

    parts.push(base.subarray(2));

    mkdirSync(GENERATED_DIR, { recursive: true });
    writeFileSync(filePath, Buffer.concat(parts));

    return filePath;
}

/**
 * Erzeugt eine Datei in einem Format, das das Formular nicht annimmt (z.B. HEIC vom iPhone
 * oder ein TIFF-Scan). Für die Formularprüfung zählt der aus der Dateiendung abgeleitete
 * MIME-Typ, der Inhalt ist dafür unerheblich.
 */
export function createUnsupportedFormatTestFile(fileName: string): string {
    const filePath = `${GENERATED_DIR}/${fileName}`;

    mkdirSync(GENERATED_DIR, { recursive: true });
    writeFileSync(filePath, Buffer.alloc(2048, 0x2a));

    return filePath;
}

/**
 * Erzeugt eine Datei, die keine erlaubte Bilddatei ist (PDF-Flyer als typischer Fehlgriff).
 * Mit einer Bildendung im Dateinamen kommt sie durch die Formularprüfung und landet beim Server.
 */
export function createNonImageTestFile(fileName: string = 'test-flyer.pdf'): string {
    const filePath = `${GENERATED_DIR}/${fileName}`;

    const pdf = Buffer.from(
        '%PDF-1.4\n' +
            '1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n' +
            '2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n' +
            '3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]>>endobj\n' +
            'trailer<</Root 1 0 R>>\n' +
            '%%EOF\n',
        'ascii'
    );

    mkdirSync(GENERATED_DIR, { recursive: true });
    writeFileSync(filePath, pdf);

    return filePath;
}
