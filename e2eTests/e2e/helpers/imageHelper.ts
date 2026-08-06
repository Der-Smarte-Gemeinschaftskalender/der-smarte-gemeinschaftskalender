import { randomFillSync } from 'node:crypto';
import { existsSync, mkdirSync, statSync, writeFileSync } from 'node:fs';
import { deflateSync } from 'node:zlib';

const PNG_SIGNATURE = Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]);
const GENERATED_DIR = './e2e/testFiles/generated';
const IMAGE_WIDTH = 1000;


export const DEFAULT_LARGE_IMAGE_BYTES = 1_800_000;

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
