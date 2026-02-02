<script lang="ts" setup>
import { ref, onMounted } from 'vue';
import { dsgApi } from '@/lib/dsgApi';
import { useRoute } from 'vue-router';
import { useRouter } from 'vue-router';
import { formatDateTime, formatHourMinute, normalizeStreet, stripHtml } from '@/lib/helper';

import Loader from '@/components/KERN/cosmetics/Loader.vue';
import DescriptionList from '@/components/KERN/DescriptionList.vue';
import Button from '@/components/KERN/Button.vue';
import InputTextarea from '@/components/KERN/inputs/InputTextarea.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';
import Alert from '@/components/KERN/Alert.vue';
import EventStatusBadge from '@/components/EventStatusBadge.vue';
import { mobilizon_category_options, mobilizon_event_language_options, mobilizon_event_status } from '@/lib/const';

interface Response {
    type: 'danger' | 'success' | '';
    message: string;
}

const route = useRoute();
const router = useRouter();

const approvalRequest = ref<any>(null);
const imageUrl = ref<string | null>(null);
const descriptionListData = ref<Array<any>>([]);

const loading = ref(false);
const loadingAction = ref(false);

const showResponseMessage = ref(false);
const response = ref<Response>({
    type: '',
    message: '',
});

const adminComment = ref('');

const buildExternalLinkHtml = (url: string) => {
    return `
        <p class="mb-2">Anmeldung über externen Link erforderlich</p>
        <a href="${url}" target="_blank" rel="noopener noreferrer" class="text-theme-primary hover:underline">${url}</a>
    `;
};

const getEventTypeName = (requestableType: string) => {
    const typeMap: Record<string, string> = {
        CreatedEvent: 'Einzeltermin',
        SingleEvent: 'Einzeltermin',
        //'SeriesEvent': 'Serientermin',
        //'UploadedEvent': 'Kalenderdatei',
        //'ImportedEvent': 'Importierter Termin',
    };
    return typeMap[requestableType] || requestableType;
};

const getActionName = (actionType: string) => {
    const actionMap: Record<string, string> = {
        create: 'Erstellen',
        store: 'Erstellen',
        update: 'Bearbeiten',
        updateGroup: 'Bearbeiten',
        delete: 'Löschen',
        //'accept_upload': 'Hochladen',
    };
    return actionMap[actionType] || actionType;
};

const fetchApprovalRequest = async () => {
    loading.value = true;
    try {
        const response = await dsgApi.get(`/approval-requests/${route.params.id}`);
        approvalRequest.value = response.data;

        if (
            approvalRequest.value.payload._file_path ||
            (approvalRequest.value.request_type === 'delete' &&
                approvalRequest.value.payload.mobilizon_fields?.picture?.url)
        ) {
            try {
                const fileRoute =
                    approvalRequest.value.request_type === 'delete'
                        ? `/approval-requests/${route.params.id}/external-file`
                        : `/approval-requests/${route.params.id}/file`;

                const imageResponse = await dsgApi.get<Blob>(fileRoute, {
                    responseType: 'blob',
                });

                const blob: Blob = imageResponse.data;
                imageUrl.value = URL.createObjectURL(blob);
            } catch (error) {
                console.error('Error fetching approval request image:', error);
            }
        }

        const organisationDetails = await fetchOrganisationDetails(approvalRequest.value.payload.mobilizon_group_id);

        descriptionListData.value = [
            {
                name: 'Aktion',
                value: getActionName(approvalRequest.value.request_type),
            },
            {
                name: 'Titel',
                value: approvalRequest.value.payload.name,
            },
            {
                name: 'Organisation',
                value: organisationDetails?.name || approvalRequest.value.payload.organisation_name || 'Keine Angabe',
                type: organisationDetails ? 'link' : undefined,
                local_url: organisationDetails ? `/organisation/${organisationDetails.preferredUsername}` : undefined,
            },
            {
                name: 'ID',
                value:
                    approvalRequest.value.requestable_id ?? approvalRequest.value.payload.id ?? 'Noch nicht vergeben',
            },
            {
                name: 'Erstellt von',
                value: approvalRequest.value.created_by.mobilizon_name,
            },
            {
                name: 'Erstellt am',
                value: formatDateTime(approvalRequest.value.created_at),
            },
            {
                name: 'Start',
                value: `${formatDateTime(approvalRequest.value.payload.start)} um ${approvalRequest.value.payload.time} Uhr`,
            },
            {
                name: 'Dauer',
                value: formatHourMinute(approvalRequest.value.payload.duration),
            },
            {
                name: 'Kategorie',
                value: approvalRequest.value.payload?.mobilizon_fields?.category
                    ? mobilizon_category_options.find(
                          (option) => option.value === approvalRequest.value.payload.mobilizon_fields.category
                      )!.text
                    : 'Keine Kategorie',
            },
            {
                name: 'Schlagwörter (optional)',
                value: approvalRequest.value.payload?.mobilizon_fields?.tags
                    ? approvalRequest.value.payload.mobilizon_fields.tags
                    : 'Keine Schlagwörter',
                type: approvalRequest.value.payload?.mobilizon_fields?.tags ? 'tags' : undefined,
            },
            {
                name: 'Anmeldeoptionen',
                value:
                    approvalRequest.value.payload?.mobilizon_fields?.joinOptions === 'EXTERNAL'
                        ? buildExternalLinkHtml(approvalRequest.value.payload.mobilizon_fields.externalParticipationUrl)
                        : 'Keine Anmeldeoptionen',
                formatAsHtml: approvalRequest.value.payload?.mobilizon_fields?.externalParticipationUrl ? true : false,
            },
            {
                name: 'Sprache',
                value: approvalRequest.value.payload?.mobilizon_fields?.language
                    ? mobilizon_event_language_options.find(
                          (option) => option.value === approvalRequest.value.payload.mobilizon_fields.language
                      )?.text
                    : mobilizon_event_language_options[0].text,
            },
            {
                name: 'Webseite',
                value: approvalRequest.value.payload?.mobilizon_fields?.onlineAddress || 'Keine Angabe',
                type: approvalRequest.value.payload?.mobilizon_fields?.onlineAddress ? 'link' : undefined,
            },
            {
                name: 'Status',
                value:
                    mobilizon_event_status.find(
                        (status) => status.value === approvalRequest.value.payload?.mobilizon_fields?.status
                    )?.text || 'Unbekannt',
            },
        ];

        descriptionListData.value = descriptionListData.value.filter((item) => item !== null);
    } catch (error) {
        console.error('Error fetching approval request:', error);
        await router.push({ name: 'notFound' });
    } finally {
        loading.value = false;
    }
};

const approveRequest = async () => {
    if (!approvalRequest.value) return;
    loadingAction.value = true;
    response.value = {
        type: '',
        message: '',
    };

    try {
        await dsgApi.post(`/approval-requests/${approvalRequest.value.id}/approve`, {
            comment: adminComment.value,
        });
        response.value = {
            type: 'success',
            message: 'Anfrage erfolgreich freigegeben.',
        };

        approvalRequest.value = null; // Reset to trigger reactivity
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error: any) {
        console.error('Error approving request:', error);

        response.value = {
            type: 'danger',
            message: error?.response?.data?.error || 'Beim Freigeben der Anfrage ist ein Fehler aufgetreten.',
        };
    } finally {
        showResponseMessage.value = true;
        loadingAction.value = false;
        fetchApprovalRequest();
    }
};

const rejectRequest = async () => {
    if (!approvalRequest.value) return;

    loadingAction.value = true;
    response.value = {
        type: '',
        message: '',
    };

    try {
        await dsgApi.post(`/approval-requests/${approvalRequest.value.id}/reject`, {
            comment: adminComment.value,
        });
        response.value = {
            type: 'success',
            message: 'Anfrage erfolgreich abgelehnt.',
        };

        approvalRequest.value = null; // Reset to trigger reactivity
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error: any) {
        console.error('Error rejecting request:', error);
        response.value = {
            type: 'danger',
            message: error?.response?.data?.error || 'Beim Ablehnen der Anfrage ist ein Fehler aufgetreten.',
        };
    } finally {
        showResponseMessage.value = true;
        loadingAction.value = false;
        fetchApprovalRequest();
    }
};

const fetchOrganisationDetails = async (mobilizon_group_id: number): Promise<any> => {
    try {
        const response = await dsgApi.get(`/organisations/group/${mobilizon_group_id}`);
        return response.data;
    } catch (error) {
        console.error('Error fetching organisation details:', error);
        return null;
    }
};

const statusMap: Record<string, string> = {
    pending: 'Ausstehend',
    approved: 'Genehmigt',
    rejected: 'Abgelehnt',
};

onMounted(async () => {
    await fetchApprovalRequest();
});
</script>
<template>
    <div>
        <h1 class="kern-heading text-theme-primary mb-4">Terminfreigabe – Detailansicht</h1>
        <p class="kern-text mb-6">
            <b>Hinweis:</b>
            Als Administrator*in der Instanz können Sie hier Terminanfragen von Organisationen genehmigen oder ablehnen.
            Termine erscheinen erst nach Freigabe in der öffentlichen Kalenderansicht.
            <br />
            <br />
            Weitere Informationen finden Sie im
            <LinkToDocs
                path="Terminverwaltung/Instanz/"
                fragment="terminfreigabe-–-strict-mode"
            />.
        </p>
        <div
            v-if="approvalRequest?.status"
            class="mb-4"
        >
            <EventStatusBadge
                :text="
                    approvalRequest.status === 'approved'
                        ? 'Genehmigt'
                        : approvalRequest.status === 'rejected'
                          ? 'Abgelehnt'
                          : 'Ausstehend'
                "
                :status="
                    approvalRequest.status === 'approved'
                        ? 'CONFIRMED'
                        : approvalRequest.status === 'rejected'
                          ? 'CANCELLED'
                          : 'TENTATIVE'
                "
            />
        </div>
        <Alert
            v-if="showResponseMessage"
            :title="response.type === 'success' ? 'Erfolg' : 'Fehler'"
            :severity="response.type === 'success' ? 'success' : 'danger'"
            class="mb-4"
            @close="showResponseMessage = false"
        >
            {{ response.message }}
        </Alert>
        <Loader v-if="loading" />
        <div v-else>
            <div v-if="approvalRequest">
                <DescriptionList :data="descriptionListData" />
                <div>
                    <h5 class="kern-heading mt-4 mb-2">Beschreibung</h5>
                    <p
                        class="kern-text prose"
                        v-html="
                            stripHtml(approvalRequest.payload.mobilizon_fields.description)
                                ? approvalRequest.payload.mobilizon_fields.description
                                : '<p>Keine Beschreibung angegeben.</p>'
                        "
                    />
                </div>
                <div>
                    <h5 class="kern-heading mt-4 mb-2">Adresse</h5>
                    <div v-if="approvalRequest.payload.mobilizon_fields.physicalAddress?.geom">
                        <p v-if="approvalRequest.payload.mobilizon_fields.physicalAddress?.street">
                            {{ normalizeStreet(approvalRequest.payload.mobilizon_fields.physicalAddress?.street) }},
                        </p>
                        <p>
                            {{ approvalRequest.payload.mobilizon_fields.physicalAddress?.postalCode }}
                            {{ approvalRequest.payload.mobilizon_fields.physicalAddress?.locality }}
                        </p>
                    </div>
                    <p v-else>Keine Adresse angegeben.</p>
                </div>
                <div>
                    <h5 class="kern-heading mt-4 mb-2">Bild</h5>
                    <div v-if="imageUrl">
                        <img
                            :src="imageUrl"
                            alt="Angehängtes Bild"
                            class="col-12 sm:col-10 md:col-8 lg:col-6 h-auto border p-0"
                        />
                    </div>
                    <p v-else-if="approvalRequest.status !== 'pending' && approvalRequest.payload._file_path">
                        Nachdem die Anfrage bearbeitet wurde, ist das angehängte Bild nicht mehr verfügbar.
                    </p>
                    <p v-else>Kein Bild angehängt.</p>
                </div>
                <h5 class="kern-heading mb-2 mt-6">Kommentar der Administration (optional)</h5>
                <div v-if="approvalRequest?.status === 'pending'">
                    <div class="flex flex-column md:flex-row gap-6">
                        <div class="w-full flex">
                            <InputTextarea
                                id="adminComment"
                                v-model="adminComment"
                                name="adminComment"
                                class="kern-input w-full mb-4 h-full"
                                rows="4"
                                placeholder="Fügen Sie hier einen Kommentar hinzu..."
                            />
                        </div>
                        <div class="md:col-6 p-0">
                            <Alert
                                title="Information"
                                variant="info"
                                class="mb-4"
                            >
                                Der hier eingegebene Kommentar wird der Organisation nach Annahme oder Ablehnung der
                                Termin- oder Bearbeitungsanfrage übermittelt. Sie können den Kommentar nutzen, um
                                beispielsweise einen Ablehnungsgrund zu erläutern oder gewünschte Änderungen zu
                                benennen, auch im Fall einer Annahme.
                            </Alert>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <Button
                            :icon-left="'check'"
                            :disabled="loadingAction"
                            @click="approveRequest"
                        >
                            Anfrage genehmigen
                        </Button>
                        <Button
                            :disabled="loadingAction"
                            :icon-left="'close'"
                            variant="secondary"
                            @click="rejectRequest"
                        >
                            Anfrage ablehnen
                        </Button>
                    </div>
                </div>
                <div v-else>
                    <p>
                        {{ approvalRequest.admin_comment ?? 'Kein Kommentar hinterlegt.' }}
                    </p>
                </div>
            </div>
            <div v-else>
                <p>Die Terminanfrage nicht gefunden.</p>
            </div>
        </div>
    </div>
</template>
