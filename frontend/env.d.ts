/// <reference types="vite/client" />

declare module '*.svg?raw' {
    const content: string;
    export default content;
}
interface ImportMetaEnv {
    readonly VITE_APP_TIMEZONE?: string;
}
