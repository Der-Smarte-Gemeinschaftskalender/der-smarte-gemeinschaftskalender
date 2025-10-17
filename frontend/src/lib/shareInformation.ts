import { formatOnDateTime } from "./helper";
import type { IEventDetailed } from "@/types/General";

export const createTextSingleEvent = (event: IEventDetailed, showPhysicalAddress: boolean = false, extraNewLine: boolean = false): string => {
    let text = `📅 ${event.title} \n🕚 ${formatOnDateTime(event.beginsOn)}\n➡️ ${createFullEventUrl(event.uuid)}`;
    if (showPhysicalAddress && event.physicalAddress) {
        text += `\n🏠 ${event?.physicalAddress?.street}\n${event?.physicalAddress?.postalCode} ${event?.physicalAddress?.locality}`;
    }
    if (extraNewLine) {
        text += `\n`;
    }
    return text;
}

export const createTextEventList = (headline: string = "Veranstaltungsübersicht", events: IEventDetailed[]): string => {
    let text = `${headline}\n\n`;
    text += events.map(event => createTextSingleEvent(event, false, true)).join('\n');
    return text;
}

export const createFullEventUrl = (uuid: string): string => {
    return createFullUrl(`/events/${uuid}`);
}

export const createFullUrl = (path: string|undefined): string => {
    return `${import.meta.env.VITE_APP_URL}${path ?? ''}`;
}