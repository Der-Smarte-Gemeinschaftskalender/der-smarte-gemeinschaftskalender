<script lang="ts" setup>
import { computed, ref, onMounted } from 'vue';
import { dsgApi } from '@/lib/dsgApi';
import { useRoute } from 'vue-router';
import { useRouter } from 'vue-router';
import { I18nT, useI18n } from 'vue-i18n';
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

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const approvalRequest = ref<any>(null);
const imageUrl = ref<string | null>(null);
const organisationDetails = ref<{ name: string; preferredUsername: string } | null>(null);

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
        <p class="mb-2">${t('app.admin.eventRequests.externalRegistrationRequired')}</p>
        <a href="${url}" target="_blank" rel="noopener noreferrer" class="text-theme-primary hover:underline">${url}</a>
    `;
};

const getEventTypeName = (requestableType: string) => {
    const typeMap: Record<string, string> = {
        CreatedEvent: t('app.admin.approval.types.singleEvent'),
        SingleEvent: t('app.admin.approval.types.singleEvent'),
        //SeriesEvent: 'Serientermin',
        //UploadedEvent: 'Kalenderdatei',
        //ImportedEvent: 'Kalenderintegration',
    };
    return typeMap[requestableType] || requestableType;
};

const getActionName = (actionType: string) => {
    const actionMap: Record<string, string> = {
        create: t('app.admin.approval.actions.create'),
        store: t('app.admin.approval.actions.create'),
        update: t('app.admin.approval.actions.update'),
        delete: t('app.admin.approval.actions.delete'),
        //'accept_upload': 'Hochladen',
    };
    return actionMap[actionType] || actionType;
};

// computed so the labels follow a language switch.
// Missing data gets a fallback instead of throwing: an error here would leave the page blank,
// without the approve/reject buttons.
const descriptionListData = computed<Array<any>>(() => {
    const request = approvalRequest.value;
    if (!request) return [];

    const organisation = organisationDetails.value;
    const fields = request.payload?.mobilizon_fields;

    return [
        {
            name: t('app.admin.approval.columns.action'),
            value: getActionName(request.request_type),
        },
        {
            name: t('app.admin.approval.columns.title'),
            value: request.payload.name,
        },
        {
            name: t('app.admin.eventRequests.fields.organisation'),
            value: organisation?.name || request.payload.organisation_name || t('app.admin.approval.notSpecified'),
            type: organisation ? 'link' : undefined,
            local_url: organisation ? `/organisation/${organisation.preferredUsername}` : undefined,
        },
        {
            name: 'ID',
            value: request.requestable_id ?? request.payload.id ?? t('app.admin.approval.notAssignedYet'),
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
            name: t('app.admin.eventRequests.fields.start'),
            value: t('app.admin.eventRequests.startAt', {
                date: formatDateTime(request.payload.start),
                time: request.payload.time,
            }),
        },
        {
            name: t('app.admin.eventRequests.fields.duration'),
            value: formatHourMinute(request.payload.duration),
        },
        {
            name: t('app.admin.eventRequests.fields.category'),
            value: fields?.category
                ? (mobilizon_category_options.value.find((option) => option.value === fields.category)?.text ??
                  fields.category)
                : t('app.admin.eventRequests.noCategory'),
        },
        {
            name: t('app.admin.eventRequests.fields.tags'),
            value: fields?.tags ? fields.tags : t('app.admin.eventRequests.noTags'),
            type: fields?.tags ? 'tags' : undefined,
        },
        {
            name: t('app.admin.eventRequests.fields.joinOptions'),
            value:
                fields?.joinOptions === 'EXTERNAL'
                    ? buildExternalLinkHtml(fields.externalParticipationUrl)
                    : t('app.admin.eventRequests.noJoinOptions'),
            formatAsHtml: fields?.externalParticipationUrl ? true : false,
        },
        {
            name: t('app.admin.eventRequests.fields.language'),
            value: fields?.language
                ? (mobilizon_event_language_options.value.find((option) => option.value === fields.language)?.text ??
                  fields.language)
                : mobilizon_event_language_options.value[0]?.text,
        },
        {
            name: t('app.admin.eventRequests.fields.website'),
            value: fields?.onlineAddress || t('app.admin.approval.notSpecified'),
            type: fields?.onlineAddress ? 'link' : undefined,
        },
        {
            name: t('app.admin.eventRequests.fields.status'),
            value:
                mobilizon_event_status.value.find((status) => status.value === fields?.status)?.text ||
                t('app.admin.shared.unknown'),
        },
    ];
});

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

        organisationDetails.value = await fetchOrganisationDetails(approvalRequest.value.payload.mobilizon_group_id);
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
        <h1 class="kern-heading text-theme-primary mb-4">{{ t('app.admin.eventRequests.showTitle') }}</h1>
        <p class="mb-3">
            <b>{{ t('app.admin.shared.note') }}</b>
            {{ t('app.admin.eventRequests.showHint') }}
        </p>
        <p class="mb-6">
            <I18nT
                keypath="app.admin.shared.moreInfo"
                scope="global"
            >
                <template #link>
                    <LinkToDocs
                        path="Terminverwaltung/Instanz/"
                        fragment="terminfreigabe-–-strict-mode"
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
                    <p
                        class="kern-text prose"
                        v-html="
                            stripHtml(approvalRequest.payload.mobilizon_fields?.description)
                                ? approvalRequest.payload.mobilizon_fields.description
                                : `<p>${t('app.admin.approval.noDescription')}</p>`
                        "
                    />
                </div>
                <div>
                    <h5 class="kern-heading mt-4 mb-2">{{ t('app.admin.approval.address') }}</h5>
                    <div v-if="approvalRequest.payload.mobilizon_fields?.physicalAddress?.geom">
                        <p v-if="approvalRequest.payload.mobilizon_fields.physicalAddress?.street">
                            {{ normalizeStreet(approvalRequest.payload.mobilizon_fields.physicalAddress?.street) }},
                        </p>
                        <p>
                            {{ approvalRequest.payload.mobilizon_fields.physicalAddress?.postalCode }}
                            {{ approvalRequest.payload.mobilizon_fields.physicalAddress?.locality }}
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
                            :disabled="loadingAction"
                            :icon-left="'close'"
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
                <p>{{ t('app.admin.eventRequests.notFound') }}</p>
            </div>
        </div>
    </div>
</template>
