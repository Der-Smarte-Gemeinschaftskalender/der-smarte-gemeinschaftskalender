<script setup lang="ts">
import { ref } from 'vue';
import { I18nT, useI18n } from 'vue-i18n';
import { useRouter, useRoute } from 'vue-router';
import { setOrganisationData } from '@/composables/OrganisationComposable';
import { requested_organisation_status } from '@/lib/const';
import { dsgApi } from '@/lib/dsgApi';
import { user } from '@/composables/UserComposoable';

import Button from '@/components/KERN/Button.vue';
import Table from '@/components/KERN/Table.vue';
import Alert from '@/components/KERN/Alert.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';

import type { Column } from '@/types/General';
import type { IAlert } from '@/types/components/Alert';

const { t } = useI18n();
const router = useRouter();
const route = useRoute();

const message = ref<IAlert | null>(null);

const columns: Array<Column> = [
    {
        key: 'id',
        name: 'ID',
        format: (_: any, row: any) => {
            return row?.parent?.id;
        },
        align: 'center',
    },
    {
        key: 'parent',
        name: t('app.organisations.index.columnName'),
        format: (value: any) => {
            return value?.name;
        },
        align: 'left',
    },
    {
        key: 'aktionen',
        name: '',
        align: 'right',
    },
];

if (user.value?.type === 'admin') {
    columns.splice(1, 0, {
        key: 'is_featured',
        name: t('app.organisations.index.columnFeatured'),
        format: (col: any) => (col ? t('common.yes') : t('common.no')),
        align: 'center',
    });
}

const columnsRequestedOrganisations: Array<Column> = [
    {
        key: 'id',
        name: 'ID',
        align: 'center',
    },
    {
        key: 'requested_organisation_data',
        name: t('app.organisations.index.columnName'),
        format: (col: any) => col?.name,
        align: 'left',
    },
    {
        key: 'status',
        name: t('app.organisations.index.columnStatus'),
        format: (col: keyof typeof requested_organisation_status) => {
            return requested_organisation_status[col] || t('app.organisations.unknown');
        },
        align: 'center',
    },
];

const rejectInvitation = async (membershipId: string) => {
    try {
        await dsgApi.post('organisations/rejectInvitation', {
            membership_id: membershipId,
        });

        router.go(0);
    } catch (error) {
        console.error(error);
        message.value = {
            title: t('common.error'),
            content: t('app.organisations.index.rejectError'),
            severity: 'danger',
        };
    }
};

const acceptInvitation = async (membershipId: string) => {
    try {
        await dsgApi.post('organisations/acceptInvitation', {
            membership_id: membershipId,
        });

        await setOrganisationData(null, true);
        router.go(0);
    } catch (error) {
        console.error(error);
        message.value = {
            title: t('common.error'),
            content: t('app.organisations.index.acceptError'),
            severity: 'danger',
        };
    }
};


</script>
<template>
    <Alert
        v-if="message"
        :title="message.title"
        :content="message.content"
        :severity="message.severity"
    />
    <div class="flex align-items-center justify-content-between gap-2 mb-1">
        <h1 class="kern-heading text-theme-primary">{{ t('app.organisations.index.title') }}</h1>
        <RouterLink :to="{ name: 'app.myOrganisations.request' }">
            <Button
                :title="t('app.organisations.index.create')"
                :aria-label="t('app.organisations.index.create')"
                variant="primary"
                icon-left="add"
                icon-size="lg"
                class="mb-2"
                :hide-text-on-mobile="true"
            >
                {{ t('app.organisations.index.create') }}
            </Button>
        </RouterLink>
    </div>
    <p class="mt-1 mb-6">
        <b>{{ t('app.organisations.hint') }}</b> <I18nT scope="global" keypath="app.organisations.index.hint">
            <template #docs>
                <LinkToDocs
                    path="Terminverwaltung/Organisation/"
                    fragment="organisationsadministration"
                />
            </template>
        </I18nT>
    </p>
    <Alert
        v-if="route.query.requestSent === 'true'"
        :title="t('app.organisations.index.requestSentTitle')"
        :content="t('app.organisations.index.requestSentContent')"
        severity="info"
    />

    <Table
        :api="{
            url: '/organisations/myOrganisations',
        }"
        :columns="columns"
        class="organisations-table"
    >
        <template #aktionen="{ row }">
            <div
                v-if="row.role === 'ADMINISTRATOR' && row?.parent"
                class="flex justify-content-end flex-wrap gap-2"
            >

                <RouterLink
                    :to="{
                        name: 'app.organisation.edit',
                        params: { preferredUsername: row?.parent?.preferredUsername },
                    }"
                >
                    <Button
                        :title="t('common.edit')"
                        :aria-label="t('common.edit')"
                        icon-left="edit"
                        :hide-text-on-mobile="true"
                    >
                        {{ t('common.edit') }}
                    </Button>
                </RouterLink>
                <RouterLink
                    :to="{
                        name: 'app.organisation.members',
                        params: { preferredUsername: row?.parent?.preferredUsername },
                    }"
                >
                    <Button
                        :title="t('app.organisations.index.members')"
                        :aria-label="t('app.organisations.index.members')"
                        icon-left="group"
                        :hide-text-on-mobile="true"
                    >
                        {{ t('app.organisations.index.members') }}
                    </Button>
                </RouterLink>
                <RouterLink
                    :to="{
                        name: 'public.organisations.show',
                        params: { preferredUsername: row?.parent?.preferredUsername },
                    }"
                >
                    <Button
                        :title="t('app.organisations.index.view')"
                        :aria-label="t('app.organisations.index.view')"
                        icon-left="visibility"
                        :hide-text-on-mobile="true"
                    >
                        {{ t('app.organisations.index.view') }}
                    </Button>
                </RouterLink>
            </div>
            <div
                v-else-if="row.role === 'INVITED'"
                class="flex justify-content-end flex-wrap gap-2"
            >
                <Button
                    :title="t('app.organisations.index.accept')"
                    :aria-label="t('app.organisations.index.accept')"
                    variant="secondary"
                    :hide-text-on-mobile="true"
                    @click="acceptInvitation(row.id)"
                >
                    {{ t('app.organisations.index.acceptInvitation') }}
                </Button>
                <Button
                    :title="t('app.organisations.index.reject')"
                    :aria-label="t('app.organisations.index.reject')"
                    variant="secondary"
                    :hide-text-on-mobile="true"
                    @click="rejectInvitation(row.id)"
                >
                    {{ t('app.organisations.index.rejectInvitation') }}
                </Button>
            </div>
        </template>
    </Table>
    <h2 class="kern-heading font-medium text-theme-primary mt-6">{{ t('app.organisations.index.requestedTitle') }}</h2>
    <Table
        :api="{
            url: '/organisations/requestedOrganisationsFromMe',
        }"
        :columns="columnsRequestedOrganisations"
        class="requested-organisations-table"
    />
</template>
