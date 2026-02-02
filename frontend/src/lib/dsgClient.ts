import { axiosErrorHandler, dsgApi } from "./dsgApi";

import type { ZodSchema, ZodType } from "zod";
import type { AxiosError } from "axios";

import { MobilizonGroupSchema, type MobilizonGroup, MobilizonFieldsSchema } from "@/types/Mobilizon";
import { SingleEventResponseSchema, type SingleEventResponse } from "@/types/events/SingleEvents";
import { type SeriesEvent, SeriesEventSchema } from "@/types/events/SeriesEvents";

import { type Option, type EventsStatistics, MobilizonEventJoinOptions } from "@/types/General";
import type { DefaultEvent, DefaultEventForm } from "@/types/events/DefaultEvents";


export const findSeriesEvent = async (seriesEventId: string | number): Promise<SeriesEvent> => {
    try {
        const { data } = await dsgApi.get(`/series-events/${seriesEventId}`);

        return SeriesEventSchema.parse(data);

    } catch (error) {
        console.error("Error fetching series event:", error);
        throw error;
    }
}

export const findSingleEvent = async (singleEventId: string | number): Promise<SingleEventResponse> => {
    try {
        const { data } = await dsgApi.get(`/single-events/${singleEventId}`);
        return SingleEventResponseSchema.parse(data);

    } catch (error) {
        console.error("Error fetching single event:", error);
        throw error;
    }
}

export const allMobilizionGroups = async (): Promise<MobilizonGroup[] | null> => {
    try {
        const { data } = await dsgApi.get("/organisations/mobilizon-groups");

        return <MobilizonGroup[]>data.groups.map((group: any) => {
            return MobilizonGroupSchema.parse(group);
        });

    } catch (error) {
        console.error(error);
    }

    return null;
};

export const getMobilizionGroupOptions = async (mobilizionGroups?: MobilizonGroup[] | null): Promise<Option[]> => {
    mobilizionGroups = mobilizionGroups ?? await allMobilizionGroups();
    if (!mobilizionGroups) return [];
    return mobilizionGroups.map((group: MobilizonGroup) => {
        return {
            value: group.id,
            text: group.name,
        };
    });
}

export const getEventsStatistics = async (mobilizionGroupId: string | number): Promise<EventsStatistics> => {
    try {
        const { data } = await dsgApi.get(`/created-events/statistics/${mobilizionGroupId}`);
        return data;
    } catch (error) {
        console.error("Error fetching events statistics:", error);
        throw error;
    }
}

type SubmitOptions = {
    headers?: Record<string, string>;
    values: Record<string, any>;
    resDataKey?: string;
    schema?: ZodSchema | ZodType;
};

export const handleSubmitCallback = async (url: string, {
    headers = {},
    values,
    resDataKey = '',
    schema,
}: SubmitOptions) => {
    try {

        const { data } = await dsgApi.post(url, values, {
            headers: {
                ...headers
            }
        });

        schema?.parse(resDataKey ? data[resDataKey] : data);

        return resDataKey ? data[resDataKey] : data;
    }
    catch (error: any | AxiosError) {
        console.error('Error in handleSubmitCallback:', error);

        throw axiosErrorHandler(error)
            ? (error?.response?.data?.message ?? error?.response?.data?.error ?? 'Ein Fehler ist aufgetreten.')
            : 'Ein Fehler ist aufgetreten.';
    }
}

export const prepareEventsValues = <
    F extends DefaultEventForm & Record<string, any> = DefaultEventForm,
    S extends DefaultEvent & Record<string, any> = DefaultEvent
>(values: F, additionalValues: Array<string>): S => {

    //if (values.physicalAddress) values.physicalAddress.description = ''; // Reset description for mobilizon API

    const preparedValues: DefaultEvent = {
        mobilizon_group_id: values.mobilizon_group_id,
        mobilizon_fields: {
            picture: values.picture
                ? {
                    media: {
                        name: values.picture.name,
                        alt: values.pictureAlt || '',
                        file: values.picture as File,
                    }
                }
                : undefined,
            description: values.description || '',
            category: values.category,
            joinOptions: values.joinOptions,
            externalParticipationUrl: values.joinOptions === MobilizonEventJoinOptions.EXTERNAL ? values.externalParticipationUrl : null,
            language: values.language,
            status: values.status,
            visibility: values.visibility,
            onlineAddress: values.onlineAddress || null,
            physicalAddress: values.physicalAddress?.description ? values.physicalAddress : null,
            tags: values.tags || [],
        },
    };

    additionalValues.forEach((key: string) => {
        if (values.hasOwnProperty(key)) preparedValues[key] = values[key];
    });

    if (preparedValues.mobilizon_fields.externalParticipationUrl) {
        const result = MobilizonFieldsSchema.shape.externalParticipationUrl.safeParse(preparedValues.mobilizon_fields);
        preparedValues.mobilizon_fields.externalParticipationUrl = result.success
            ? result.data
            : preparedValues.mobilizon_fields.externalParticipationUrl;
    }

    if (preparedValues.mobilizon_fields.onlineAddress) {
        const result = MobilizonFieldsSchema.shape.onlineAddress.safeParse(preparedValues.mobilizon_fields);
        preparedValues.mobilizon_fields.onlineAddress = result.success
            ? result.data
            : preparedValues.mobilizon_fields.onlineAddress;
    }

    return preparedValues as S;
}


/**
 * Load created event image from a URL and return it as a File object.
 */
export const loadCreatedEventImageByID = async (id: string | number): Promise<File | null> => {
    try {
        return await loadImage(`/created-events/${id}/image`);

    }
    catch (error: any) {
        console.error('Error loading created event image:', error);
        return null;
    }
}
/**
 * Load created event image from a URL and return it as a File object.
 */
export const loadImage = async (path: string, params = {}): Promise<File | null> => {
    try {
        const response = await dsgApi.get(path, {
            params,
            responseType: 'blob',
            validateStatus: status => status < 500,
        });

        const contentType = response.headers['content-type'];

        if (contentType && contentType.includes('application/json')) {
            const text = await response.data.text();
            const json = JSON.parse(text);
            console.warn('Server returned error JSON:', json);
            return null;
        }

        if (response.data.size === 0) {
            return null;
        }

        const fileName = 'event_image.' + contentType.split('/').pop();
        return new File([response.data], fileName, { type: contentType });

    }
    catch (error: any) {
        console.error('Error loading created event image:', error);
        return null;
    }
}