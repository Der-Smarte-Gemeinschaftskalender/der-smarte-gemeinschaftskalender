export interface DescriptionListData {
    key?: string;
    name: string;
    value: any;
    slot?: boolean;
}

export interface Accordion {
    header: string;
    content?: string;
    open?: boolean;
}