<script setup lang="ts">
import zod from '@/lib/zod';
import { ref } from 'vue';
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
                required_error: 'Der Benutzername ist erforderlich.',
            })
            .refine((val) => val.trim().length > 0, {
                message: 'Der Benutzername darf nicht leer sein.',
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

        if (!!data.errors) {
            showErrorMessage.value = true;
            errorMessageContent.value =
                data.errors[0]?.message || 'Benutzer konnte nicht gefunden oder eingeladen werden.';
            return;
        }
        showSuccessMessage.value = true;
        await loadOrganisation();
        invite_username.value = '';
    } catch (error: any | AxiosError) {
        console.error(error);
        showErrorMessage.value = true;
        errorMessageContent.value = error?.response?.data?.error || 'Es ist ein Fehler aufgetreten.';
    }
});
const loadOrganisation = async () => {
    if (!preferredUsername) {
        showErrorMessage.value = true;
        errorMessageContent.value = 'Organisation konnte nicht geladen werden.';
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
    } catch (error) {
        console.error('Error loading organisation:', error);
        showErrorMessage.value = true;
        errorMessageContent.value = 'Organisation konnte nicht geladen werden.';
    }
};

const columns = [
    {
        key: 'actor',
        name: 'Name',
        format: (value: Member) => value?.name,
    },
    {
        key: 'actor',
        name: 'Benutzername',
        format: (value: Member) => value?.preferredUsername,
    },
    {
        key: 'role',
        name: 'Rolle',
        format: (value: keyof typeof member_role) => member_role[value] || 'Unbekannt',
    },
    {
        key: 'insertedAt',
        name: 'Erstellt am',
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
        });
        router.go(0);
    } catch (error) {
        console.error(error);
    }
};

const leaveGroup = async () => {
    try {
        const { data } = await dsgApi.post('organisations/leave', {
            group_id: organisation.value?.id,
        });
        if (data.errors) {
            showErrorMessageLeavingGroup.value = true;
            errorMessageLeavingGroup.value = data.errors[0].message;
        } else {
            await setOrganisationData(null, true);
            router.push({ name: 'app.myOrganisations' });
        }
    } catch (error) {
        console.error(error);
        showErrorMessageLeavingGroup.value = true;
    }
};

loadOrganisation();
</script>
<template>
    <h1 class="kern-heading text-theme-primary">{{ organisation?.name }} - Mitglieder verwalten</h1>
    <p class="mt-1 mb-6">
        <b>Hinweis:</b>
        In dieser Ansicht können Sie alle Mitglieder der Organisation einsehen, diese entfernen und neue Mitglieder
        einladen. Weitere Informationen finden Sie im
        <LinkToDocs
            path="Terminverwaltung/Organisation/"
            fragment="mitglieder-verwalten"
        />
        .
    </p>

    <h2 class="kern-heading text-theme-primary my-6 pb-0">Mitglieder der Organisation</h2>
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
                Löschen
            </Button>
        </template>
    </Table>

    <h2 class="kern-heading text-theme-primary">Neues Mitglied einladen</h2>
    <p class="mt-1 mb-6">
        <b>Hinweis:</b>
        Das Mitglied, welches Sie zu Ihrer Organisation einladen wollen, muss bereits registriert sein. Nutzen Sie den
        individuellen Benutzernamen und achten Sie auf die genaue Schreibweise. Weitere Informationen finden Sie im
        <LinkToDocs
            path="Terminverwaltung/Organisation/"
            fragment="neue-mitglieder-einladen"
        />
        .
    </p>
    <form
        novalidate
        @submit.prevent="onSubmit"
    >
        <Alert
            v-if="showErrorMessage"
            title="Fehler"
            :content="errorMessageContent || 'Es ist ein Fehler aufgetreten.'"
            severity="danger"
        />
        <div class="flex flex-wrap gap-4 align-items-baseline">
            <div class="col-12 sm:col-7 p-0">
                <InputText
                    v-model="invite_username"
                    label="Benutzername"
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
                    Benutzer einladen
                </Button>
            </div>
        </div>

        <Alert
            v-if="showSuccessMessage"
            class="mt-2"
            title="Gespeichert"
            :content="'Benutzer wurde erfolgeich eingeladen.'"
            severity="success"
        />
        <ConfirmDialog
            v-model="showLeaveGroupConfirm"
            title="Wills du diese Organisation wirklich verlassen?"
            @confirm="leaveGroup"
            confirmText="Verlassen"
        />
        <Divider class="my-6" />

        <section>
            <h2 class="kern-heading text-theme-primary">Organisation verlassen</h2>
            <p class="mt-1 mb-6">
                Hinweis: Die Organisation kann nicht verlassen werden, wenn Sie das einzige Mitglied sind. Um die
                Organisation zu löschen, wenden Sie sich bitte an die Administrator*innen dieser Kalenderinstanz.
                Weitere Informationen finden Sie im
                <LinkToDocs
                    path="Terminverwaltung/Organisation/"
                    fragment="organisation-verlassen"
                />
                .
            </p>

            <div class="flex justify-content-center">
                <Button
                    @click="showLeaveGroupConfirm = true"
                    icon-left="logout"
                >
                    Gruppe verlassen
                </Button>
            </div>
        </section>

        <Alert
            v-if="showErrorMessageLeavingGroup"
            class="mt-2"
            title="Gruppe konnte nicht verlassen werden"
            :content="errorMessageLeavingGroup || 'Beim verlassen der Gruppe ist ein Fehler aufgetreten.'"
            severity="danger"
        />
    </form>
</template>
