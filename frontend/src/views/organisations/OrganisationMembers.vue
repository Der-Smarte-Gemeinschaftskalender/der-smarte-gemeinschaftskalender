<script setup lang="ts">
import zod from '@/lib/zod';
import { ref } from 'vue';
import { I18nT, useI18n } from 'vue-i18n';
import { useField, useForm } from 'vee-validate';
import { useRoute, useRouter } from 'vue-router';
import { toTypedSchema } from '@vee-validate/zod';
import { setOrganisationData } from '@/composables/OrganisationComposable';
import { formatDateTime } from '@/lib/helper';
import { dsgApi } from '@/lib/dsgApi';
import { member_role } from '@/lib/const';
import { user } from '@/composables/UserComposoable';

import InputText from '@/components/KERN/inputs/InputText.vue';
import Button from '@/components/KERN/Button.vue';
import Alert from '@/components/KERN/Alert.vue';
import Table from '@/components/KERN/Table.vue';
import Divider from '@/components/KERN/cosmetics/Divider.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';

import type { IOrganisation, Member } from '@/types/General';
import type { AxiosError } from '@/lib/dsgApi';
import type { ZodType } from 'zod';

const { t } = useI18n();

const showErrorMessage = ref<boolean>(false);    
const errorMessageContent = ref<string>('');
const showSuccessMessage = ref<boolean>(false);
const showLeaveGroupConfirm = ref<boolean>(false);
const showErrorMessageLeavingGroup = ref<boolean>(false);
const errorMessageLeavingGroup = ref<string>('');

interface OrganisationMemberInviteForm {
    invite_username: string;
}

const route = useRoute();
const router = useRouter();
const preferredUsername: string = String(route.params.preferredUsername);

const members = ref<any[]>([]);
const organisation = ref<IOrganisation | null>(null);

const validationSchema = toTypedSchema(
    zod.object({
        invite_username: zod
            .string({
                required_error: 'app.organisations.validation.usernameRequired',
            })
            .refine((val) => val.trim().length > 0, {
                message: 'app.organisations.validation.usernameNotEmpty',
            }),
    }) satisfies ZodType<OrganisationMemberInviteForm>
);

const { handleSubmit, errors, isSubmitting, submitCount } = useForm({
    validationSchema,
});

const { value: invite_username } = useField<string>('invite_username');

const onSubmit = handleSubmit(async (values) => {
    showSuccessMessage.value = false;
    showErrorMessage.value = false;
    try {
        const { data } = await dsgApi.post('organisations/invite', {
            username: values.invite_username,
            group_id: organisation.value?.id,
        });

        if (data.errors) {
            showErrorMessage.value = true;
            errorMessageContent.value =
                data.errors[0]?.message || t('app.organisations.members.inviteError');
            return;
        }
        showSuccessMessage.value = true;
        await loadOrganisation();
        invite_username.value = '';
    } catch (error: any | AxiosError) {
        console.error(error);
        showErrorMessage.value = true;
        errorMessageContent.value = error?.response?.data?.error || t('app.organisations.errorOccurred');
    }
});
const loadOrganisation = async () => {
    if (!preferredUsername) {
        showErrorMessage.value = true;
        errorMessageContent.value = t('app.organisations.loadError');
        return;
    }
    try {
        const { data } = await dsgApi.get('organisations/group', {
            params: {
                preferred_username: preferredUsername,
            },
        });

        organisation.value = data || null;
        members.value = organisation.value?.members?.elements || [];

        if (members.value.length === 0) {
            showErrorMessage.value = true;
            errorMessageContent.value = t('app.organisations.members.notMember');
        }
    } catch (error) {
        console.error('Error loading organisation:', error);
        showErrorMessage.value = true;
        errorMessageContent.value = t('app.organisations.loadError');
    }
};

const columns = [
    {
        key: 'actor',
        name: t('app.organisations.members.columnName'),
        format: (value: Member) => value?.name,
    },
    {
        key: 'actor',
        name: t('app.organisations.members.columnUsername'),
        format: (value: Member) => value?.preferredUsername,
    },
    {
        key: 'role',
        name: t('app.organisations.members.columnRole'),
        format: (value: keyof typeof member_role) => member_role[value] || t('app.organisations.unknown'),
    },
    {
        key: 'insertedAt',
        name: t('app.organisations.members.columnCreatedAt'),
        format: (value: string) => formatDateTime(value),
    },
    {
        key: 'aktionen',
        name: '',
    },
];

const removeMember = async (membershipId: string) => {
    try {
        await dsgApi.post('organisations/removeMember', {
            membership_id: membershipId,
            group_id: organisation.value?.id,
            group_preferred_username: organisation.value?.preferredUsername,
        });
        router.go(0);
    } catch (error: any) {
        console.error(error);
        showErrorMessage.value = true;
        errorMessageContent.value =
            error?.response?.data?.error ||
            error?.response?.data?.errors?.[0]?.message ||
            t('app.organisations.errorOccurred');
    }
};

const leaveGroup = async () => {
    try {
        await dsgApi.post('organisations/leave', {
            group_id: organisation.value?.id,
            group_preferred_username: organisation.value?.preferredUsername,
        });

        await setOrganisationData(null, true);
        router.push({ name: 'app.myOrganisations' });
    } catch (error: any) {
        console.error(error);
        showErrorMessageLeavingGroup.value = true;
        errorMessageLeavingGroup.value =
            error?.response?.data?.error ||
            error?.response?.data?.errors?.[0]?.message ||
            t('app.organisations.errorOccurred');
    }
};

loadOrganisation();
</script>
<template>
    <h1 class="kern-heading text-theme-primary">{{ t('app.organisations.members.title', { name: organisation?.name }) }}</h1>
    <p class="mt-1 mb-6">
        <b>{{ t('app.organisations.hint') }}</b> <I18nT scope="global" keypath="app.organisations.members.hint">
            <template #docs>
                <LinkToDocs
                    path="Terminverwaltung/Organisation/"
                    fragment="mitglieder-verwalten"
                />
            </template>
        </I18nT>
    </p>
    <Alert
        v-if="showErrorMessage"
        :title="t('common.error')"
        :content="errorMessageContent || t('app.organisations.errorOccurred')"
        severity="danger"
    />

    <template v-if="members.length > 0">
        <h2 class="kern-heading text-theme-primary my-6 pb-0">{{ t('app.organisations.members.membersTitle') }}</h2>
        <Table
            v-if="members.length > 0"
            :data="members"
            :columns="columns"
            class="mb-6"
        >
            <template #aktionen="{ row }">
                <Button
                    v-if="row.actor.preferredUsername !== user.person.preferredUsername"
                    icon-left="delete"
                    @click="removeMember(row.id)"
                >
                    {{ t('common.delete') }}
                </Button>
            </template>
        </Table>

        <h2 class="kern-heading text-theme-primary">{{ t('app.organisations.members.inviteTitle') }}</h2>
        <p class="mt-1 mb-6">
            <b>{{ t('app.organisations.hint') }}</b> <I18nT scope="global" keypath="app.organisations.members.inviteHint">
                <template #docs>
                    <LinkToDocs
                        path="Terminverwaltung/Organisation/"
                        fragment="neue-mitglieder-einladen"
                    />
                </template>
            </I18nT>
        </p>
        <form
            novalidate
            @submit.prevent="onSubmit"
        >
            <div class="flex flex-wrap gap-4 align-items-baseline">
                <div class="col-12 sm:col-7 p-0">
                    <InputText
                        v-model="invite_username"
                        :label="t('app.organisations.members.username')"
                        name="invite_username"
                        :errors="submitCount === 0 ? undefined : errors.invite_username"
                    />
                </div>
                <div class="sm:col-4 p-0">
                    <Button
                        :disabled="isSubmitting"
                        class="mt-5 min-w-max"
                        type="submit"
                        icon-left="mail"
                    >
                        {{ t('app.organisations.members.invite') }}
                    </Button>
                </div>
            </div>

            <Alert
                v-if="showSuccessMessage"
                class="mt-2"
                :title="t('app.organisations.saved')"
                :content="t('app.organisations.members.inviteSuccess')"
                severity="success"
            />
            <ConfirmDialog
                v-model="showLeaveGroupConfirm"
                :title="t('app.organisations.members.leaveConfirmTitle')"
                :confirm-text="t('app.organisations.members.leaveConfirm')"
                @confirm="leaveGroup"
            />
            <Divider class="my-6" />

            <section>
                <h2 class="kern-heading text-theme-primary">{{ t('app.organisations.members.leaveTitle') }}</h2>
                <p class="mt-1 mb-6">
                    <I18nT scope="global" keypath="app.organisations.members.leaveHint">
                        <template #docs>
                            <LinkToDocs
                                path="Terminverwaltung/Organisation/"
                                fragment="organisation-verlassen"
                            />
                        </template>
                    </I18nT>
                </p>

                <div class="flex justify-content-center">
                    <Button
                        icon-left="logout"
                        @click="showLeaveGroupConfirm = true"
                    >
                        {{ t('app.organisations.members.leave') }}
                    </Button>
                </div>
            </section>

            <Alert
                v-if="showErrorMessageLeavingGroup"
                class="mt-2"
                :title="t('app.organisations.members.leaveErrorTitle')"
                :content="errorMessageLeavingGroup || t('app.organisations.members.leaveErrorContent')"
                severity="danger"
            />
        </form>
    </template>
</template>
