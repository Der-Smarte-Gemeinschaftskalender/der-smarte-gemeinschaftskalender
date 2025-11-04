import { createRouter, createWebHashHistory, createWebHistory } from "vue-router";
import { checkLogin, user } from "@/composables/UserComposoable";
import type { RouteToMeta } from "@/types/Route";



const router = createRouter({
    history: import.meta.env.PROD ? createWebHistory() : createWebHashHistory(),
    scrollBehavior() {
        return { top: 0 }
    },
    routes: [
        {
            path: "/",
            name: "landingpage",
            component: () => import("@/views/LandingPage.vue"),
            meta: {
                showNotificationImageInHeader: true,
            }
        },
        {
            path: "/search",
            name: "public.search",
            component: () => import("@/views/public/SearchPage.vue"),
        },
        {
            path: "/calendar",
            name: "public.calendar",
            component: () => import("@/views/public/CalendarPage.vue"),
        },
        {
            path: "/organisations",
            name: "public.organisations",
            component: () => import("@/views/public/OrganisationIndexPage.vue"),
        },
        {
            path: "/organisation/:preferredUsername",
            name: "public.organisations.show",
            component: () => import("@/views/public/OrganisationShowPage.vue"),
        },
        {
            path: "/exports",
            name: "public.exports.index",
            component: () => import("@/views/public/exports/ExportsIndex.vue"),
        },
        {
            path: "/exports/email",
            name: "public.exports.email.create",
            component: () => import("@/views/public/exports/emailNotifications/EmailNotificationsCreate.vue"),
        },
        {
            path: "/exports/email/confirm/:verificationToken",
            name: "public.exports.email.confirm",
            component: () => import("@/views/public/exports/emailNotifications/EmailNotificationsConfirm.vue"),
        },
        {
            path: "/exports/email/unsubscribe/:token",
            name: "public.exports.email.unsubscribe",
            component: () => import("@/views/public/exports/emailNotifications/EmailNotificationsUnsubscribe.vue"),
        },
        {
            path: "/exports/email/edit/:id",
            name: "public.exports.email.edit",
            component: () => import("@/views/public/exports/emailNotifications/EmailNotificationsEdit.vue"),
        },
        {
            path: "/exports/script",
            name: "public.exports.script",
            component: () => import("@/views/public/exports/ExportsScriptPage.vue"),
        },
        {
            path: "/events/:uuid",
            name: "public.event",
            component: () => import("@/views/public/EventPage.vue"),
        },
        {
            path: "/login",
            name: "login",
            component: () => import("@/views/LoginPage.vue"),
        },
        {
            path: "/register",
            name: "register",
            component: () => import("@/views/public/auth/RegisterPage.vue"),
        },
        {
            path: "/forgot-password",
            name: "forgotPassword",
            component: () => import("@/views/public/auth/ForgotPasswordPage.vue"),
        },
        {
            path: "/reset-password/:resetToken",
            name: "resetPassword",
            component: () => import("@/views/public/auth/ResetPasswordPage.vue"),
        },
        {
            path: "/validate/:verificationToken",
            name: "validate",
            component: () => import("@/views/public/auth/ValidatePage.vue"),
        },
        {
            path: "/not-found",
            name: "public.notFound",
            component: () => import("@/views/NotFound.vue"),
        },
        {
            path: "/infomonitor",
            name: "public.infomonitor",
            component: () => import("@/views/public/InfoMonitor.vue"),
            meta: {
                showWithoutLayout: true
            }
        },
        {
            path: "/public",
            children: [
                {
                    path: "about",
                    name: "public.imprint",
                    component: () => import("@/views/public/ImprintPage.vue"),
                },
                {
                    path: "terms",
                    name: "public.terms",
                    component: () => import("@/views/public/TermsPage.vue"),
                },
                {
                    path: "privacy",
                    name: "public.privacy",
                    component: () => import("@/views/public/PrivacyPage.vue"),
                },
            ]
        },
        {
            path: "/app",
            meta: {
                requiresAuth: true,
            },
            component: () => import("@/layouts/AppLayout.vue"),
            children: [
                {
                    path: "",
                    name: "dashboard",
                    component: () => import("@/views/DashboardPage.vue"),
                },
                {
                    path: "not-found",
                    name: "notFound",
                    component: () => import("@/views/NotFound.vue"),
                },
                {
                    path: "/material-generator/events/:uuid",
                    name: "materialGenerator.event",
                    component: () => import("@/views/materialGenerator/MaterialGeneratorPage.vue"),
                },
                {
                    path: "/material-generator/organisation/:preferredUsername",
                    name: "materialGenerator.organisation",
                    component: () => import("@/views/materialGenerator/MaterialGeneratorPage.vue"),
                },
                {
                    path: "/material-generator/markenkit",
                    name: "brandkit",
                    component: () => import("@/views/materialGenerator/BrandKitPage.vue"),
                },
                {
                    path: "series-events",
                    component: () => import("@/layouts/EventsLayout.vue"),
                    children: [
                        {
                            path: "",
                            name: "seriesEvents.index",
                            component: () => import("@/views/seriesEvents/SeriesEventsIndex.vue"),
                        },
                        {
                            path: ":id",
                            name: "seriesEvents.show",
                            component: () => import("@/views/seriesEvents/SeriesEventsShow.vue"),
                        },
                        {
                            path: "create",
                            name: "seriesEvents.create",
                            component: () =>
                                import("@/views/seriesEvents/SeriesEventsCreate.vue"),
                        }
                    ]
                },
                {
                    path: "imported-events",
                    component: () => import("@/layouts/EventsLayout.vue"),
                    children: [
                        {
                            path: "",
                            name: "importedEvents.index",
                            component: () => import("@/views/importedEvents/ImportedEventsIndex.vue"),
                        },
                        {
                            path: ":id",
                            name: "importedEvents.show",
                            component: () => import("@/views/importedEvents/ImportedEventsShow.vue"),
                        },
                        {
                            path: "create",
                            name: "importedEvents.create",
                            component: () =>
                                import("@/views/importedEvents/ImportedEventsCreate.vue"),
                        }
                    ]
                },
                {
                    path: "uploaded-events",
                    component: () => import("@/layouts/EventsLayout.vue"),
                    children: [
                        {
                            path: "",
                            name: "uploadedEvents.index",
                            component: () => import("@/views/uploadedEvents/UploadedEventsIndex.vue"),
                        },
                        {
                            path: ":id",
                            name: "uploadedEvents.show",
                            component: () => import("@/views/uploadedEvents/UploadedEventsShow.vue"),
                        },
                        {
                            path: "create",
                            name: "uploadedEvents.create",
                            component: () => import("@/views/uploadedEvents/UploadedEventsCreate.vue"),
                        }
                    ]
                },
                {
                    path: "single-events",
                    component: () => import("@/layouts/EventsLayout.vue"),
                    children: [
                        {
                            path: "",
                            name: "singleEvents.index",
                            component: () => import("@/views/singleEvents/SingleEventsIndex.vue"),
                        },
                        {
                            path: "create",
                            name: "singleEvents.create",
                            component: () => import("@/views/singleEvents/SingleEventsCreate.vue"),
                        },
                    ]
                },
                {
                    path: "created-events",
                    component: () => import("@/layouts/EventsLayout.vue"),
                    children: [
                        {
                            path: ":id/edit",
                            name: "createdEvent.edit",
                            component: () => import("@/views/createdEvents/CreatedEventsEdit.vue"),
                        },
                        {
                            path: "/findEvent/:uuid",
                            name: "createdEvent.findEvent",
                            component: () => import("@/views/createdEvents/CreatedEventsRedirect.vue"),
                        },
                    ]
                },
                {
                    path: "profile",
                    component: () => import("@/layouts/EventsLayout.vue"),
                    children: [
                        {
                            path: "register-person",
                            name: "registerPerson",
                            component: () => import("@/views/profile/RegisterPerson.vue"),
                        },
                        {
                            path: "",
                            name: "app.profile",
                            component: () => import("@/views/profile/ProfilePage.vue"),
                        },
                    ]
                },
                {
                    path: "organisation",
                    component: () => import("@/layouts/EventsLayout.vue"),
                    children: [
                        {
                            path: "my-organisations",
                            name: "app.myOrganisations",
                            component: () => import("@/views/organisations/MyOrganisationsIndex.vue"),
                        },
                        {
                            path: "request",
                            name: "app.myOrganisations.request",
                            component: () => import("@/views/organisations/RequestNewOrganisation.vue"),
                        },
                        {
                            path: "edit/:preferredUsername",
                            name: "app.organisation.edit",
                            component: () => import("@/views/organisations/OrganisationEdit.vue"),
                        },
                        {
                            path: "members/:preferredUsername",
                            name: "app.organisation.members",
                            component: () => import("@/views/organisations/OrganisationMembers.vue"),
                        },
                    ]
                },
                {
                    path: "admin",
                    component: () => import("@/layouts/AdminLayout.vue"),
                    meta: {
                        allowedTypes: ["admin"],
                    },
                    children: [
                        {
                            path: "",
                            name: "admin.index",
                            component: () => import("@/views/admin/AdminIndex.vue"),
                            meta: {
                                allowedTypes: ["admin"],
                            },
                        },
                        {
                            path: "user/create",
                            name: "admin.user.create",
                            component: () => import("@/views/admin/AdminUserCreate.vue"),
                        },
                        {
                            path: "user/:id",
                            name: "admin.user.edit",
                            component: () => import("@/views/admin/AdminUserEdit.vue"),
                        },
                        {
                            path: "requested-organisations",
                            name: "admin.requestedOrganisations",
                            component: () => import("@/views/admin/RequestedOrganisationsIndex.vue"),
                        },
                        {
                            path: "instance",
                            name: "admin.instance",
                            component: () => import("@/views/admin/InstanceSettings.vue"),
                        }
                    ],
                },
            ],
        },
    ],
});

router.beforeEach(async (to, from, next) => {
    const requiresAuth = to.matched.some((record) => record.meta.requiresAuth);

    if (requiresAuth && !checkLogin()) next({ name: 'login', query: { redirect: to.fullPath } });

    const meta: RouteToMeta = to.meta;
    const typeIsAllowed = meta.allowedTypes?.includes(user.value.type) ?? true;

    if (meta.allowedTypes && !typeIsAllowed) next({ name: 'notFound' });
    else next();
});

export default router;
