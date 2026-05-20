export interface IAlert {
    title: string;
    content?: string;
    severity?: "info" | "success" | "warning" | "danger";
}