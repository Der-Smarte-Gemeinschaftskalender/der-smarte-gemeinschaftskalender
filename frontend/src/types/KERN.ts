export interface DescriptionListData {
    key?: string;
    name: string;
    value: any;
    slot?: boolean;
    type?: 'tags' | 'prose' | 'link';
    local_url?: string;
    formatAsHtml?: boolean;
}

export interface Accordion {
    header: string;
    content?: string;
    open?: boolean;
}