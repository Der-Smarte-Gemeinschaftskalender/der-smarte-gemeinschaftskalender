<script lang="ts" setup>
import { ref, onMounted } from 'vue';
import { dsgApi } from '@/lib/dsgApi';
import { useRoute } from 'vue-router';
import { useRouter } from 'vue-router';
import { formatDateTime, normalizeStreet, stripHtml } from '@/lib/helper';

import Loader from '@/components/KERN/cosmetics/Loader.vue';
import DescriptionList from '@/components/KERN/DescriptionList.vue';
import Button from '@/components/KERN/Button.vue';
import InputTextarea from '@/components/KERN/inputs/InputTextarea.vue';
import Alert from '@/components/KERN/Alert.vue';
import EventStatusBadge from '@/components/EventStatusBadge.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';

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

const getEventTypeName = (requestableType: string) => {
    const typeMap: Record<string, string> = {
        Organisation: 'Organisation',
    };
    return typeMap[requestableType] || requestableType;
};

const getActionName = (actionType: string) => {
    const actionMap: Record<string, string> = {
        changeOrganisationStatus: 'Erstellen',
        updateGroup: 'Bearbeiten',
    };
    return actionMap[actionType] || actionType;
};

const fetchApprovalRequest = async () => {
    loading.value = true;
    try {
        const response = await dsgApi.get(`/approval-requests/${route.params.id}`);
        approvalRequest.value = response.data;

        if (approvalRequest.value.payload._file_path) {
            try {
                const imageResponse = await dsgApi.get(`/approval-requests/${route.params.id}/file`, {
                    responseType: 'blob',
                });
                const blob = imageResponse.data;
                imageUrl.value = URL.createObjectURL(blob);
            } catch (error) {
                console.error('Error fetching approval request image:', error);
            }
        }

        descriptionListData.value = [
            {
                name: 'Aktion',
                value: getActionName(approvalRequest.value.request_type),
            },
            {
                name: 'Organisationsname',
                value: approvalRequest.value.payload.name,
            },
            {
                name: 'Benutzername der Organisation',
                value: approvalRequest.value.payload.preferredUsername || approvalRequest.value.payload.name,
            },
            {
                name: 'ID',
                value: approvalRequest.value.payload.id ?? 'Noch nicht vergeben',
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
                name: `${approvalRequest.value.handled_by ? 'Bearbeitet von' : ''}`,
                value: approvalRequest.value.handled_by?.mobilizon_name,
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
        <h1 class="kern-heading text-theme-primary mb-4">Organisationsfreigabe – Detailansicht</h1>
        <p class="kern-text mb-6">
            <b>Hinweis:</b>
            Als Administrator*in der Instanz können Sie hier angefragte Organisationen bzw. Änderungen genehmigen oder
            ablehnen. Organisationen und geänderte Informationen erscheinen erst nach Freigabe in der öffentlichen
            Kalenderansicht.
            <br />
            <br />
            Weitere Informationen finden Sie im
            <LinkToDocs
                path="Terminverwaltung/Instanz/"
                fragment="organisationsfreigabe-–-strict-mode"
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
                    <div v-if="approvalRequest.payload.summary">
                        <p
                            class="kern-text prose"
                            v-html="
                                stripHtml(approvalRequest.payload.summary)
                                    ? approvalRequest.payload.summary
                                    : '<p>Keine Beschreibung angegeben.</p>'
                            "
                        />
                    </div>
                    <p v-else>Keine Beschreibung angegeben.</p>
                </div>
                <div>
                    <h5 class="kern-heading mt-4 mb-2">Adresse</h5>
                    <div v-if="approvalRequest.payload.physicalAddress?.geom">
                        <p v-if="approvalRequest.payload.physicalAddress?.street">
                            {{ normalizeStreet(approvalRequest.payload.physicalAddress?.street) }},
                        </p>
                        <p>
                            {{ approvalRequest.payload.physicalAddress?.postalCode }}
                            {{ approvalRequest.payload.physicalAddress?.locality }}
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
                            :icon-left="'close'"
                            :disabled="loadingAction"
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
                <p>Die Organisationsanfrage nicht gefunden.</p>
            </div>
        </div>
    </div>
</template>
