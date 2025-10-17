import { createApp } from "vue";
import App from "./App.vue";
import "../public/css/app.scss";
import "primeflex/primeflex.css";
import "@kern-ux/native/dist/kern.min.css";
import "@kern-ux/native/dist/fonts/fira-sans.css";

import router from "@/router/router.ts";

const app = createApp(App);

app.use(router);
app.mount("#app");
