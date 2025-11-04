<script lang="ts" setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';

import { user } from '@/composables/UserComposoable';
import { user_types_keys } from '@/lib/const';
import Card from '@/components/KERN/Card.vue';
import LogoutButton from '@/components/LogoutButton.vue';
import Icon from './KERN/cosmetics/Icon.vue';
import Divider from './KERN/cosmetics/Divider.vue';
import ChangeCurrentOrganisation from './ChangeCurrentOrganisation.vue';
import router from '@/router/router';
import { current_organisation, user_organisations } from '@/composables/OrganisationComposable';

const route = useRoute();

const navigationItems = computed(() => [
    { title: 'Dashboard', name: 'dashboard', icon: 'home' },
    {
        title: 'Einzeltermine',
        name: 'singleEvents.index',
        icon: 'calendar_month',
        disabled: !user_organisations.value?.length,
    },
    {
        title: 'Serientermine',
        name: 'seriesEvents.index',
        icon: 'autorenew',
        disabled: !user_organisations.value?.length,
    },
    {
        title: 'Kalenderdateien',
        name: 'uploadedEvents.index',
        icon: 'drive-folder-upload',
        disabled: !user_organisations.value?.length,
    },
    {
        title: 'Kalenderintegrationen',
        name: 'importedEvents.index',
        icon: 'link',
        disabled: !user_organisations.value?.length,
    },
    {
        title: 'Werbemittel',
        name: 'materialGenerator.organisation',
        params: { preferredUsername: current_organisation.value?.preferredUsername },
        icon: 'wall_art',
        disabled: !user_organisations.value?.length,
    },
    {
        title: 'Markenkit',
        name: 'brandkit',
        icon: 'home_repair_service',
        disabled: !user_organisations.value?.length,
    },
]);

let profileNavigationItems = [
    { title: 'Meine Organisationen', name: 'app.myOrganisations', icon: 'checklist', disabled: !user.value?.person },
    { title: 'Profil bearbeiten', name: 'app.profile', icon: 'edit' },
];
let adminNavigationItems: { title: string; name: string; icon: string }[] = [];
if (user.value.type === 'admin') {
    adminNavigationItems = [
        { title: 'Organisationsanfragen', name: 'admin.requestedOrganisations', icon: 'checklist' },
        { title: 'Nutzer*innen verwalten', name: 'admin.index', icon: 'person' },
        { title: 'Instanz verwalten', name: 'admin.instance', icon: 'edit' },
    ];
}

const selectOption = async (option: any) => {
    await router.push({
        name: option.name,
        params: option?.params || {},
    });
};

const selected = (name: string) => {
    return route.name === name;
};
</script>
<template>
    <Card
        class="h-min"
        :body-class="'p-2'"
    >
        <div class="pl-2">
            <h4 class="kern-heading text-theme-primary font-medium p-0 mb-2 mt-3">
                <Icon
                    name="person"
                    v-if="user?.person?.preferredUsername"
                />
                {{ user?.person?.preferredUsername }}
            </h4>
            <h6
                v-if="user?.type"
                class="kern-heading font-light p-0 text-600"
            >
                {{ user_types_keys[user.type as keyof typeof user_types_keys] }}
            </h6>
            <ChangeCurrentOrganisation />
        </div>
        <Divider class="w-full my-4" />
        <div class="px-2 w-full mb-3">
            <ul>
                <li
                    v-for="navigationItem in navigationItems"
                    :key="navigationItem.name"
                    :class="{ selected: selected(navigationItem.name), disabled: !!navigationItem?.disabled }"
                    class="my-2 py-1 px-3 w-full select-none cursor-pointer"
                    @click="selectOption(navigationItem)"
                >
                    <p class="flex align-items-center">
                        <Icon
                            :name="navigationItem.icon"
                            class="mr-2"
                            :color="selected(navigationItem.name) ? 'white' : 'black'"
                        />
                        {{ navigationItem.title }}
                    </p>
                </li>
            </ul>
        </div>
        <Divider class="w-full my-2" />
        <div class="pl-2">
            <ul>
                <li
                    v-for="navigationItem in profileNavigationItems"
                    :key="navigationItem.name"
                    :class="{ selected: selected(navigationItem.name), disabled: !!navigationItem?.disabled }"
                    class="my-3 py-1 px-3 w-full select-none cursor-pointer"
                    @click="selectOption(navigationItem)"
                >
                    <p class="flex align-items-center">
                        <Icon
                            :name="navigationItem.icon"
                            class="mr-2"
                            :color="selected(navigationItem.name) ? 'white' : 'black'"
                        />
                        {{ navigationItem.title }}
                    </p>
                </li>
            </ul>
        </div>
        <template v-if="adminNavigationItems.length">
            <Divider class="w-full my-2" />
            <div class="pl-2">
                <ul>
                    <li
                        v-for="navigationItem in adminNavigationItems"
                        :key="navigationItem.name"
                        :class="{ selected: selected(navigationItem.name) }"
                        class="my-3 py-1 px-3 w-full select-none cursor-pointer"
                        @click="selectOption(navigationItem)"
                    >
                        <p class="flex align-items-center">
                            <Icon
                                :name="navigationItem.icon"
                                class="mr-2"
                                :color="selected(navigationItem.name) ? 'white' : 'black'"
                            />
                            {{ navigationItem.title }}
                        </p>
                    </li>
                </ul>
            </div>
        </template>
        <LogoutButton class="mx-auto mb-2" />
    </Card>
</template>

<style scoped lang="scss">
ul {
    list-style: none;

    li {
        border-radius: 0.5rem;
        &.selected {
            background-color: #2b2c6a;
            color: white;
        }
    }
}
.disabled {
    opacity: 0.5;
    pointer-events: none;
}

@media (max-width: 991px) {
    .kern-card {
        border: 0 !important;
    }
}
</style>
