<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { dsgApi } from '@/lib/dsgApi';
import { formatDateTime } from '@/lib/helper';

import Table from '@/components/KERN/Table.vue';
import Button from '@/components/KERN/Button.vue';
import Loader from '@/components/KERN/cosmetics/Loader.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';
import Alert from '@/components/KERN/Alert.vue';

import type { Column } from '@/types/General';

const loading = ref(false);
const showErrorMessage = ref(false);
const errorMessageContent = ref('');

const organisationRequests = ref<any[]>([]);

const getActionName = (actionType: string) => {
    const actionMap: Record<string, string> = {
        'updateGroup': 'Bearbeiten',
        'changeOrganisationStatus': 'Erstellen',
    };
    return actionMap[actionType] || actionType;
};

const columns: Array<Column> = [
    {
        key: 'id',
        name: 'ID',
        align: 'center',
    },
    {
        key: 'request_type',
        name: 'Aktion',
        format: (col: string) => getActionName(col),
        align: 'left',
    },
    {
        key: 'payload',
        name: 'Organisationsname',
        format: (col: any) => {
            return col?.name || 'Unbekannt';
        },
        align: 'left',
    },
    {
        key: 'created_by',
        name: 'Erstellt von',
    },
    {
        key: 'created_at',
        name: 'Erstellt am',
        format: (col: string) => formatDateTime(col),
        align: 'left',
    },
    {
        key: 'aktionen',
        name: '',
        align: 'right',
    },
];

const fetchOrganisationRequests = async () => {
    loading.value = true;
    showErrorMessage.value = false;
    try {
        const response = await dsgApi.get<any[]>('/approval-requests/organisations', {
            params: { status: 'pending' }
        });
        
        organisationRequests.value = response.data || [];
        console.log('Fetched organisation requests:', organisationRequests.value);

    } catch (error: any) {
        console.error('Error fetching organisation requests:', error);
        showErrorMessage.value = true;
        errorMessageContent.value =
            error?.response?.data?.error || 'Beim Laden der Anfragen ist ein Fehler aufgetreten.';
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchOrganisationRequests();
});
</script>

<template>
    <div class="flex align-items-center justify-content-between mb-1">
        <h1 class="kern-heading text-theme-primary">Ausstehende Organisationsanfragen</h1>
    </div>
    
    <Alert
        v-if="showErrorMessage"
        title="Fehler"
        severity="danger"
        class="mb-4" 
        >
        {{ errorMessageContent }}
    </Alert>

    <p class="mb-4">
        <span class="block mb-3">
            <b>Hinweis:</b>
            Werden Organisationen erstellt oder Organisationsinformationen bearbeitet, 
            ist vor der Veröffentlichung eine Freigabe durch die Instanzadministration erforderlich. 
            Über den Button „Ansehen“ in der Tabelle können Sie die angegebenen Informationen zur Organisation prüfen 
            und die Organisationserstellung bzw. Änderungen genehmigen oder ablehnen. Erst nach erfolgter Freigabe sind Organisationen und Änderungen öffentlich sichtbar.
            <br /><br />
            Weitere Informationen finden Sie im <LinkToDocs
                path="Terminverwaltung/Instanz/"
                fragment="organisationsfreigabe-–-strict-mode"
            />. 
        </span>
    </p>

    <Loader v-if="loading" class="mt-6" />
    
    <div v-else>
        <Table v-if="organisationRequests.length > 0" :columns="columns" :data="organisationRequests" class="mb-2">
            <template #created_by="{ row }">
                <div>
                    {{ row.created_by.mobilizon_name }} (@{{ row.created_by.mobilizon_preferred_username }})
                </div>
                <p class="text-sm text-theme-secondary">
                    {{ row.created_by.mobilizon_email }}
                    
                </p>
                <p 
                    v-if="!row.created_by.email_verified_at"
                    class="text-sm text-theme-secondary text-red-600"
                >
                    (E-Mail nicht bestätigt)
                </p>
            </template>
            <template #aktionen="{ row }">
                <RouterLink
                    :to="{ name: 'admin.organisations.requests.show', params: { id: row.id } }"
                >
                    <Button
                        :icon-left="'visibility'"
                        title="Ansehen"
                        aria-label="Ansehen"
                        label="Ansehen"
                    />
                </RouterLink>
            </template>
        </Table>
        <Alert 
            v-else
            title="Keine ausstehenden Organisationsanfragen" 
            type="info"
        >
            Keine ausstehenden Organisationsanfragen vorhanden.
        </Alert>
    </div>
</template>