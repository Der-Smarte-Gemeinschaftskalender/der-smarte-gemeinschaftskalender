<script setup lang="ts">
import { ref, onMounted, toDisplayString } from 'vue';
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

const eventRequests = ref<any[]>([]);

const getEventTypeName = (requestableType: string) => {
    const typeMap: Record<string, string> = {
        'CreatedEvent': 'Einzeltermin',
        'SingleEvent': 'Einzeltermin',
        //'SeriesEvent': 'Serientermin',
        //'UploadedEvent': 'Kalenderdatei',
        //'ImportedEvent': 'Importierter Termin',
    };
    return typeMap[requestableType] || requestableType;
};

const getActionName = (actionType: string) => {
    const actionMap: Record<string, string> = {
        'create': 'Erstellen',
        'store': 'Erstellen',
        'update': 'Bearbeiten',
        'delete': 'Löschen',
        //'accept_upload': 'Hochladen',
    };
    return actionMap[actionType] || actionType;
};

const columns: Array<Column> = [
    {
        key: 'id',
        name: 'ID',
        align: 'center',
    },
    /**
    {
        key: 'requestable_type',
        name: 'Typ',
        format: (col: string) => `${getEventTypeName(col)}`,
        align: 'left',
    },
     */
    {
        key: 'request_type',
        name: 'Aktion',
        format: (col: string) => `${getActionName(col)}`,
        align: 'left',
    },
    {
        key: 'payload',
        name: 'Titel',
        format: (col: any) => {
            return col?.name || 'Unbekannt';
        },
        align: 'left',
    },
    {
        key: 'created_by',
        name: 'Erstellt von',
        align: 'left',
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

const fetchEventRequests = async () => {
    loading.value = true;
    showErrorMessage.value = false;
    try {
        const response = await dsgApi.get<any[]>('/approval-requests/events', {
            params: { status: 'pending' }
        });

        eventRequests.value = response.data || [];
        console.log('Fetched event requests:', eventRequests.value);
        

    } catch (error: any) {
        console.error('Error fetching event requests:', error);
        showErrorMessage.value = true;
        errorMessageContent.value =
            error?.response?.data?.error || 'Beim Laden der Anfragen ist ein Fehler aufgetreten.';
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchEventRequests();
});
</script>

<template>
    <div class="flex align-items-center justify-content-between mb-1">
        <h1 class="kern-heading text-theme-primary">Ausstehende Terminanfragen</h1>
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
            Werden Termine von Organisationen erstellt oder bearbeitet, ist vor der Veröffentlichung eine Freigabe durch die Instanzadministration erforderlich. 
            Über den Button „Ansehen” in der Tabelle können Sie die von der Organisation angegebenen Informationen zum Termin prüfen und den Termin bzw. die Terminänderung genehmigen oder ablehnen. 
            Nach erfolgter Freigabe erscheinen Termine und Änderungen in der öffentlichen Kalenderansicht.
            <br /><br />
            Weitere Informationen finden Sie im <LinkToDocs
                path="Terminverwaltung/Instanz/"
                fragment="terminfreigabe-–-strict-mode"
            />. 
        </span>
    </p>

    <Loader v-if="loading" class="mt-6" />
    
    <div v-else>
        <Table v-if="eventRequests.length > 0" :columns="columns" :data="eventRequests" class="mb-2">
            <template #created_by="{ row }">
                <div>
                    {{ row.created_by.mobilizon_name }} ({{ row.created_by.mobilizon_preferred_username }})
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
                    :to="{ name: 'admin.events.requests.show', params: { id: row.id } }"
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
            title="Keine ausstehenden Freigabeanfragen" 
            type="info"
        >
            Keine ausstehenden Freigabeanfragen vorhanden.
        </Alert>
    </div>
</template>
<style scoped>
.approval-request-thumbnail {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid #e0e0e0;
}

.approval-request-preview-image {
    max-width: 100%;
    max-height: 300px;
    object-fit: contain;
    border-radius: 8px;
    border: 1px solid #ddd;
}
</style>