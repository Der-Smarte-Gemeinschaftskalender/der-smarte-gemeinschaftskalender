<script lang="ts" setup>
import { computed, ref, onMounted } from 'vue';
import { dsgApi } from '@/lib/dsgApi';
import { useRoute } from 'vue-router';
import { useRouter } from 'vue-router';
import { I18nT, useI18n } from 'vue-i18n';
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

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const approvalRequest = ref<any>(null);
const imageUrl = ref<string | null>(null);

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
        Organisation: t('app.admin.organisationRequests.typeOrganisation'),
    };
    return typeMap[requestableType] || requestableType;
};

const getActionName = (actionType: string) => {
    const actionMap: Record<string, string> = {
        changeOrganisationStatus: t('app.admin.approval.actions.create'),
        updateGroup: t('app.admin.approval.actions.update'),
    };
    return actionMap[actionType] || actionType;
};

// computed so the labels follow a language switch.
// Missing data gets a fallback instead of throwing: an error here would leave the page blank,
// without the approve/reject buttons.
const descriptionListData = computed<Array<any>>(() => {
    const request = approvalRequest.value;
    if (!request) return [];

    return [
        {
            name: t('app.admin.approval.columns.action'),
            value: getActionName(request.request_type),
        },
        {
            name: t('app.admin.organisationRequests.organisationName'),
            value: request.payload.name,
        },
        {
            name: t('app.admin.organisationRequests.organisationUsername'),
            value: request.payload.preferredUsername || request.payload.name,
        },
        {
            name: 'ID',
            value: request.payload.id ?? t('app.admin.approval.notAssignedYet'),
        },
        {
            name: t('app.admin.approval.columns.createdBy'),
            value: request.created_by?.mobilizon_name || t('app.admin.approval.notSpecified'),
        },
        {
            name: t('app.admin.approval.columns.createdAt'),
            value: formatDateTime(request.created_at),
        },
        {
            name: `${request.handled_by ? t('app.admin.organisationRequests.handledBy') : ''}`,
            value: request.handled_by?.mobilizon_name,
        },
    ];
});

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
            message: t('app.admin.approval.approveSuccess'),
        };

        approvalRequest.value = null; // Reset to trigger reactivity
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error: any) {
        console.error('Error approving request:', error);

        response.value = {
            type: 'danger',
            message: error?.response?.data?.error || t('app.admin.approval.approveError'),
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
            message: t('app.admin.approval.rejectSuccess'),
        };

        approvalRequest.value = null; // Reset to trigger reactivity
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error: any) {
        console.error('Error rejecting request:', error);
        response.value = {
            type: 'danger',
            message: error?.response?.data?.error || t('app.admin.approval.rejectError'),
        };
    } finally {
        showResponseMessage.value = true;
        loadingAction.value = false;
        fetchApprovalRequest();
    }
};

const statusMap: Record<string, string> = {
    pending: t('app.admin.approval.status.pending'),
    approved: t('app.admin.approval.status.approved'),
    rejected: t('app.admin.approval.status.rejected'),
};

onMounted(async () => {
    await fetchApprovalRequest();
});
</script>
<template>
    <div>
        <h1 class="kern-heading text-theme-primary mb-4">{{ t('app.admin.organisationRequests.showTitle') }}</h1>
        <p class="mb-3">
            <b>{{ t('app.admin.shared.note') }}</b>
            {{ t('app.admin.organisationRequests.showHint') }}
        </p>
        <p class="mb-6">
            <I18nT
                keypath="app.admin.shared.moreInfo"
                scope="global"
            >
                <template #link>
                    <LinkToDocs
                        path="Terminverwaltung/Instanz/"
                        fragment="organisationsfreigabe-–-strict-mode"
                    />
                </template>
            </I18nT>
        </p>
        <div
            v-if="approvalRequest?.status"
            class="mb-4"
        >
            <EventStatusBadge
                :text="
                    approvalRequest.status === 'approved'
                        ? t('app.admin.approval.status.approved')
                        : approvalRequest.status === 'rejected'
                          ? t('app.admin.approval.status.rejected')
                          : t('app.admin.approval.status.pending')
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
            :title="response.type === 'success' ? t('common.success') : t('common.error')"
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
                    <h5 class="kern-heading mt-4 mb-2">{{ t('app.admin.approval.description') }}</h5>
                    <div v-if="approvalRequest.payload.summary">
                        <p
                            class="kern-text prose"
                            v-html="
                                stripHtml(approvalRequest.payload.summary)
                                    ? approvalRequest.payload.summary
                                    : `<p>${t('app.admin.approval.noDescription')}</p>`
                            "
                        />
                    </div>
                    <p v-else>{{ t('app.admin.approval.noDescription') }}</p>
                </div>
                <div>
                    <h5 class="kern-heading mt-4 mb-2">{{ t('app.admin.approval.address') }}</h5>
                    <div v-if="approvalRequest.payload.physicalAddress?.geom">
                        <p v-if="approvalRequest.payload.physicalAddress?.street">
                            {{ normalizeStreet(approvalRequest.payload.physicalAddress?.street) }},
                        </p>
                        <p>
                            {{ approvalRequest.payload.physicalAddress?.postalCode }}
                            {{ approvalRequest.payload.physicalAddress?.locality }}
                        </p>
                    </div>
                    <p v-else>{{ t('app.admin.approval.noAddress') }}</p>
                </div>
                <div>
                    <h5 class="kern-heading mt-4 mb-2">{{ t('app.admin.approval.image') }}</h5>
                    <div v-if="imageUrl">
                        <img
                            :src="imageUrl"
                            :alt="t('app.admin.approval.imageAlt')"
                            class="col-12 sm:col-10 md:col-8 lg:col-6 h-auto border p-0"
                        />
                    </div>
                    <p v-else-if="approvalRequest.status !== 'pending' && approvalRequest.payload._file_path">
                        {{ t('app.admin.approval.imageUnavailable') }}
                    </p>
                    <p v-else>{{ t('app.admin.approval.noImage') }}</p>
                </div>
                <h5 class="kern-heading mb-2 mt-6">{{ t('app.admin.approval.adminComment') }}</h5>
                <div v-if="approvalRequest?.status === 'pending'">
                    <div class="flex flex-column md:flex-row gap-6">
                        <div class="w-full flex">
                            <InputTextarea
                                id="adminComment"
                                v-model="adminComment"
                                name="adminComment"
                                class="kern-input w-full mb-4 h-full"
                                rows="4"
                                :placeholder="t('app.admin.approval.adminCommentPlaceholder')"
                            />
                        </div>
                        <div class="md:col-6 p-0">
                            <Alert
                                :title="t('app.admin.shared.information')"
                                variant="info"
                                class="mb-4"
                            >
                                {{ t('app.admin.approval.adminCommentInfo') }}
                            </Alert>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <Button
                            :icon-left="'check'"
                            :disabled="loadingAction"
                            @click="approveRequest"
                        >
                            {{ t('app.admin.approval.approve') }}
                        </Button>
                        <Button
                            :icon-left="'close'"
                            :disabled="loadingAction"
                            variant="secondary"
                            @click="rejectRequest"
                        >
                            {{ t('app.admin.approval.reject') }}
                        </Button>
                    </div>
                </div>
                <div v-else>
                    <p>
                        {{ approvalRequest.admin_comment ?? t('app.admin.approval.noComment') }}
                    </p>
                </div>
            </div>
            <div v-else>
                <p>{{ t('app.admin.organisationRequests.notFound') }}</p>
            </div>
        </div>
    </div>
</template>
