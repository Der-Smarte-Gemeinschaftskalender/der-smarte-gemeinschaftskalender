import i18n from '@/i18n';

/** Wildcard types, described by a translated text (e.g. "all image formats"). */
const mimeTypeGroups: Record<string, string> = {
    'audio/*': 'app.shared.fileFormats.allAudio',
    'video/*': 'app.shared.fileFormats.allVideo',
    'image/*': 'app.shared.fileFormats.allImage',
    'text/*': 'app.shared.fileFormats.allText',
    'application/*': 'app.shared.fileFormats.allApplication',
    'calendar/*': 'app.shared.fileFormats.calendar',
};

/** Concrete types, described by their (language independent) file extension. */
export const mimeTypes: Record<string, string> = {
    // audio
    'audio/mpeg': 'MP3',
    'audio/mp3': 'MP3',
    'audio/wav': 'WAV',
    'audio/wave': 'WAV',
    'audio/x-wav': 'WAV',
    'audio/x-mpeg-3': 'MP3',
    'audio/x-mp3': 'MP3',
    'audio/x-mpegurl': 'M3U',
    'audio/mpegurl': 'M3U',
    'audio/x-scpls': 'PLS',
    'audio/scpls': 'PLS',

    // Video
    'video/mp4': 'MP4',
    'video/x-ms-wmv': 'WMV',
    'video/x-ms-asf': 'ASF',

    // image
    'image/jpeg': 'JPG',
    'image/png': 'PNG',
    'image/gif': 'GIF',
    'image/bmp': 'BMP',
    'image/tiff': 'TIF',
    'image/svg+xml': 'SVG',
    'image/webp': 'WEBP',
    'image/x-icon': 'ICO',
    'image/avif': 'AVIF',

    // application
    'application/pdf': 'PDF',
    'application/zip': 'ZIP',
    'application/x-zip-compressed': 'ZIP',
    'application/x-zip': 'ZIO',
    'application/ics': 'ICS',

    // text
    'text/plain': 'TXT',
    'text/html': 'HTML',
    'text/css': 'CSS',
    'text/javascript': 'JS',
    'text/csv': 'CSV',
    'text/calendar': 'ICS',
    'text/ical': 'ICS',
    'text/x-vCalendar': 'VCS',
    'text/x-vCard': 'VCF',
};

/**
 * Human readable name of a mime type, e.g. "JPG" or "all image formats". Unknown types are
 * returned unchanged.
 */
export const describeMimeType = (type: string): string => {
    if (mimeTypeGroups[type]) return i18n.global.t(mimeTypeGroups[type]);
    return mimeTypes[type] ?? type;
};
