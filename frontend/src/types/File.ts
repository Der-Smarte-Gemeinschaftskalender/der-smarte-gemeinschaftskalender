export const mimeTypes: Record<string, string> = {
    // all
    'audio/*': 'validation.fileFormats.allAudio',
    'video/*': 'validation.fileFormats.allVideo',
    'image/*': 'validation.fileFormats.allImage',
    'text/*': 'validation.fileFormats.allText',
    'application/*': 'validation.fileFormats.allApplication',
    'calendar/*': 'validation.fileFormats.calendar',

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