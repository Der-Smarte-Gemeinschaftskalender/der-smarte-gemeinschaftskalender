<script lang="ts" setup>
import { createTextSingleEvent, createTextEventList, createFullEventUrl, createFullUrl } from '@/lib/shareInformation';
import type { IEventDetailed } from '@/types/General';

interface PropsBase {
    type: 'singleEvent' | 'eventList';
    linkToUrl?: string;
}

interface SingleEventProps extends PropsBase {
    type: 'singleEvent';
    event: IEventDetailed;
    eventList?: never;
}

interface EventListProps extends PropsBase {
    type: 'eventList';
    eventList: IEventDetailed[];
    event?: never;
}

type Props = SingleEventProps | EventListProps;

const { type, event, eventList, linkToUrl } = defineProps<Props>();
const fullUrl: string = type === 'singleEvent' ? createFullEventUrl(event!.uuid) : createFullUrl(linkToUrl);
const text: string =
    type === 'singleEvent'
        ? createTextSingleEvent(event, true)
        : createTextEventList('Veranstaltungsübersicht', eventList);
const fullEncodedUrl: string = encodeURIComponent(fullUrl);
const title: string = type === 'singleEvent' ? event.title : 'Veranstaltungsübersicht';
const fullEncodedText: string = encodeURIComponent(text);

const copyContent = () => {
    const copyText = document.getElementById('copyToClipboardInput') as HTMLInputElement;
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
};
</script>
<template>
    <div
        class="flex flex-row-reverse flex-wrap gap-1"
        style="min-height: 51px"
    >
        <slot />
        <a
            class="cursor-pointer"
            aria-label="Kopieren"
            title="Kopieren"
            @click="copyContent()"
        >
            <!--<Icon
                name="content-copy"
                class="w-2rem h-2rem sm:w-3rem sm:h-3rem"
            />-->
            <svg
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                class="w-2rem h-2rem sm:w-3rem sm:h-3rem"
            >
                <title>Kopieren</title>
                <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M7.5875 17.4125C7.97917 17.8042 8.45 18 9 18H18C18.55 18 19.0208 17.8042 19.4125 17.4125C19.8042 17.0208 20 16.55 20 16V4C20 3.45 19.8042 2.97917 19.4125 2.5875C19.0208 2.19583 18.55 2 18 2H9C8.45 2 7.97917 2.19583 7.5875 2.5875C7.19583 2.97917 7 3.45 7 4V16C7 16.55 7.19583 17.0208 7.5875 17.4125ZM3.5875 21.4125C3.97917 21.8042 4.45 22 5 22H15C15.2833 22 15.5208 21.9042 15.7125 21.7125C15.9042 21.5208 16 21.2833 16 21C16 20.7167 15.9042 20.4792 15.7125 20.2875C15.5208 20.0958 15.2833 20 15 20H5V7C5 6.71667 4.90417 6.47917 4.7125 6.2875C4.52083 6.09583 4.28333 6 4 6C3.71667 6 3.47917 6.09583 3.2875 6.2875C3.09583 6.47917 3 6.71667 3 7V20C3 20.55 3.19583 21.0208 3.5875 21.4125Z"
                    fill="#171A2B"
                />
            </svg>
            <textarea
                id="copyToClipboardInput"
                type="text"
                :value="text"
                style="display: none"
                aria-details="Kopieren"
            />
        </a>
        <a
            :href="`mailto:?to=&amp;body=${fullEncodedText};subject=${title}`"
            target="_blank"
            rel="nofollow noopener"
            aria-details="Email"
            title="Email"
        >
            <span
                class="dark:text-white material-design-icon email-icon dark:text-white"
                aria-hidden="true"
                role="img"
            >
                <svg
                    fill="currentColor"
                    class="material-design-icon__svg w-2rem h-2rem sm:w-3rem sm:h-3rem"
                    viewBox="0 0 24 24"
                >
                    <title>Email</title>
                    <path
                        d="M20,8L12,13L4,8V6L12,11L20,6M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z"
                    >
                        <!---->
                    </path>
                </svg>
            </span>
        </a>
        <a
            :href="`https://t.me/share/url?url=${fullEncodedUrl}`"
            class="telegram"
            target="_blank"
            rel="nofollow noopener"
            aria-details="Telegram"
            title="Telegram"
        >
            <span
                class="dark:text-white"
                aria-hidden="true"
                role="img"
            >
                <svg
                    fill="currentColor"
                    class="material-design-icon__svg w-2rem h-2rem sm:w-3rem sm:h-3rem"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <title>Telegram</title>
                    <g
                        id="SVGRepo_bgCarrier"
                        stroke-width="0"
                    ></g>
                    <g
                        id="SVGRepo_tracerCarrier"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    ></g>
                    <g id="SVGRepo_iconCarrier">
                        <path
                            fill-rule="evenodd"
                            clip-rule="evenodd"
                            d="M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12ZM12.3583 9.38244C11.3857 9.787 9.44177 10.6243 6.52657 11.8944C6.05318 12.0827 5.8052 12.2669 5.78263 12.4469C5.74448 12.7513 6.12559 12.8711 6.64455 13.0343C6.71515 13.0565 6.78829 13.0795 6.86327 13.1038C7.37385 13.2698 8.06068 13.464 8.41773 13.4717C8.74161 13.4787 9.1031 13.3452 9.50219 13.0711C12.226 11.2325 13.632 10.3032 13.7202 10.2831C13.7825 10.269 13.8688 10.2512 13.9273 10.3032C13.9858 10.3552 13.98 10.4536 13.9738 10.48C13.9361 10.641 12.4401 12.0318 11.6659 12.7515C11.4246 12.9759 11.2534 13.135 11.2184 13.1714C11.14 13.2528 11.0601 13.3298 10.9833 13.4038C10.509 13.8611 10.1532 14.204 11.003 14.764C11.4114 15.0331 11.7381 15.2556 12.0641 15.4776C12.4201 15.7201 12.7752 15.9619 13.2347 16.2631C13.3517 16.3398 13.4635 16.4195 13.5724 16.4971C13.9867 16.7925 14.3589 17.0579 14.8188 17.0155C15.086 16.991 15.362 16.7397 15.5022 15.9903C15.8335 14.2193 16.4847 10.382 16.6352 8.80081C16.6484 8.66228 16.6318 8.48498 16.6185 8.40715C16.6051 8.32932 16.5773 8.21842 16.4761 8.13633C16.3563 8.03911 16.1714 8.01861 16.0886 8.02C15.7125 8.0267 15.1354 8.22735 12.3583 9.38244Z"
                            fill="#000000"
                        ></path>
                    </g>
                </svg>
            </span>
        </a>
        <a
            :href="`https://www.linkedin.com/shareArticle?mini=true&amp;url=${fullEncodedUrl}&amp;title=${fullEncodedText}`"
            target="_blank"
            rel="nofollow noopener"
            aria-details="LinkedIn"
            title="LinkedIn"
        >
            <span
                class="dark:text-white material-design-icon linkedin-icon dark:text-white"
                aria-hidden="true"
                role="img"
            >
                <svg
                    fill="currentColor"
                    class="material-design-icon__svg w-2rem h-2rem sm:w-3rem sm:h-3rem"
                    viewBox="0 0 24 24"
                >
                    <title>LinkedIn</title>
                    <path
                        d="M19 3A2 2 0 0 1 21 5V19A2 2 0 0 1 19 21H5A2 2 0 0 1 3 19V5A2 2 0 0 1 5 3H19M18.5 18.5V13.2A3.26 3.26 0 0 0 15.24 9.94C14.39 9.94 13.4 10.46 12.92 11.24V10.13H10.13V18.5H12.92V13.57C12.92 12.8 13.54 12.17 14.31 12.17A1.4 1.4 0 0 1 15.71 13.57V18.5H18.5M6.88 8.56A1.68 1.68 0 0 0 8.56 6.88C8.56 5.95 7.81 5.19 6.88 5.19A1.69 1.69 0 0 0 5.19 6.88C5.19 7.81 5.95 8.56 6.88 8.56M8.27 18.5V10.13H5.5V18.5H8.27Z"
                    >
                        <!---->
                    </path>
                </svg>
            </span>
        </a>
        <a
            :href="`https://wa.me/?text=${fullEncodedText}`"
            target="_blank"
            rel="nofollow noopener"
            aria-details="WhatsApp"
            title="WhatsApp"
        >
            <span
                class="dark:text-white material-design-icon whatsapp-icon dark:text-white"
                aria-hidden="true"
                role="img"
            >
                <svg
                    fill="currentColor"
                    class="material-design-icon__svg w-2rem h-2rem sm:w-3rem sm:h-3rem"
                    viewBox="0 0 24 24"
                >
                    <title>WhatsApp</title>
                    <path
                        d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2M12.05 3.67C14.25 3.67 16.31 4.53 17.87 6.09C19.42 7.65 20.28 9.72 20.28 11.92C20.28 16.46 16.58 20.15 12.04 20.15C10.56 20.15 9.11 19.76 7.85 19L7.55 18.83L4.43 19.65L5.26 16.61L5.06 16.29C4.24 15 3.8 13.47 3.8 11.91C3.81 7.37 7.5 3.67 12.05 3.67M8.53 7.33C8.37 7.33 8.1 7.39 7.87 7.64C7.65 7.89 7 8.5 7 9.71C7 10.93 7.89 12.1 8 12.27C8.14 12.44 9.76 14.94 12.25 16C12.84 16.27 13.3 16.42 13.66 16.53C14.25 16.72 14.79 16.69 15.22 16.63C15.7 16.56 16.68 16.03 16.89 15.45C17.1 14.87 17.1 14.38 17.04 14.27C16.97 14.17 16.81 14.11 16.56 14C16.31 13.86 15.09 13.26 14.87 13.18C14.64 13.1 14.5 13.06 14.31 13.3C14.15 13.55 13.67 14.11 13.53 14.27C13.38 14.44 13.24 14.46 13 14.34C12.74 14.21 11.94 13.95 11 13.11C10.26 12.45 9.77 11.64 9.62 11.39C9.5 11.15 9.61 11 9.73 10.89C9.84 10.78 10 10.6 10.1 10.45C10.23 10.31 10.27 10.2 10.35 10.04C10.43 9.87 10.39 9.73 10.33 9.61C10.27 9.5 9.77 8.26 9.56 7.77C9.36 7.29 9.16 7.35 9 7.34C8.86 7.34 8.7 7.33 8.53 7.33Z"
                    >
                        <!---->
                    </path>
                </svg>
            </span>
        </a>
        <a
            :href="`https://toot.kytta.dev/?text=${fullEncodedText}`"
            class="mastodon"
            target="_blank"
            rel="nofollow noopener"
            title="Mastodon"
        >
            <span>
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 216.4144 232.00976"
                    class="w-2rem h-2rem sm:w-3rem sm:h-3rem"
                    style="scale: 0.85"
                >
                    <title>Mastodon</title>
                    <path
                        d="M211.80734 139.0875c-3.18125 16.36625-28.4925 34.2775-57.5625 37.74875-15.15875 1.80875-30.08375 3.47125-45.99875 2.74125-26.0275-1.1925-46.565-6.2125-46.565-6.2125 0 2.53375.15625 4.94625.46875 7.2025 3.38375 25.68625 25.47 27.225 46.39125 27.9425 21.11625.7225 39.91875-5.20625 39.91875-5.20625l.8675 19.09s-14.77 7.93125-41.08125 9.39c-14.50875.7975-32.52375-.365-53.50625-5.91875C9.23234 213.82 1.40609 165.31125.20859 116.09125c-.365-14.61375-.14-28.39375-.14-39.91875 0-50.33 32.97625-65.0825 32.97625-65.0825C49.67234 3.45375 78.20359.2425 107.86484 0h.72875c29.66125.2425 58.21125 3.45375 74.8375 11.09 0 0 32.975 14.7525 32.975 65.0825 0 0 .41375 37.13375-4.59875 62.915"
                    ></path>
                    <path
                        d="M177.50984 80.077v60.94125h-24.14375v-59.15c0-12.46875-5.24625-18.7975-15.74-18.7975-11.6025 0-17.4175 7.5075-17.4175 22.3525v32.37625H96.20734V85.42325c0-14.845-5.81625-22.3525-17.41875-22.3525-10.49375 0-15.74 6.32875-15.74 18.7975v59.15H38.90484V80.077c0-12.455 3.17125-22.3525 9.54125-29.675 6.56875-7.3225 15.17125-11.07625 25.85-11.07625 12.355 0 21.71125 4.74875 27.8975 14.2475l6.01375 10.08125 6.015-10.08125c6.185-9.49875 15.54125-14.2475 27.8975-14.2475 10.6775 0 19.28 3.75375 25.85 11.07625 6.36875 7.3225 9.54 17.22 9.54 29.675"
                        fill="#fff"
                    ></path>
                </svg>
            </span>
        </a>
    </div>
</template>
<style lang="scss" scoped>
a:visited {
    color: black;
}
a {
    color: black !important;
}
a:hover {
    border-bottom: 3px solid black;
}
</style>
