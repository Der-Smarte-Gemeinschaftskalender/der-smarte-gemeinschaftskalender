import { createApp } from "vue";
import App from "./App.vue";
import "../public/css/app.scss";
import "primeflex/primeflex.css";
import "@kern-ux/native/dist/kern.min.css";
import "@kern-ux/native/dist/fonts/fira-sans.css";

import router from "@/router/router.ts";
import i18n from '@/i18n';

const app = createApp(App);

app.use(router);
app.use(i18n);
app.mount("#app");
