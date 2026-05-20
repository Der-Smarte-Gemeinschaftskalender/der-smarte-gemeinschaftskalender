import { ref } from 'vue';
import type { MgTemplate } from './materialGeneratorConstants';
import { dsgApi } from '@/lib/dsgApi';
import { current_organisation } from '@/composables/OrganisationComposable';

export function useMgTemplates() {
    const templates = ref<MgTemplate[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    async function fetchTemplates() {
        if (!current_organisation.value?.id) return;
        loading.value = true;
        error.value = null;
        try {
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            const { data } = await dsgApi.get<any>('/material-generator-templates', {
                params: { mobilizon_group_id: current_organisation.value.id },
            });
            templates.value = data.data as MgTemplate[];
        } catch {
            error.value = 'Vorlagen konnten nicht geladen werden.';
        } finally {
            loading.value = false;
        }
    }

    async function saveTemplate(payload: {
        name: string;
        material_type: 'event' | 'eventList';
        dimension: string;
        global_settings: MgTemplate['global_settings'];
        objects_data: MgTemplate['objects_data'];
    }): Promise<MgTemplate | null> {
        if (!current_organisation.value?.id) return null;
        error.value = null;
        try {
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            const { data } = await dsgApi.post<any>('/material-generator-templates', {
                mobilizon_group_id: current_organisation.value.id,
                ...payload,
            });
            await fetchTemplates();
            return data.data as MgTemplate;
        } catch {
            error.value = 'Vorlage konnte nicht gespeichert werden.';
            return null;
        }
    }

    async function deleteTemplate(id: number) {
        if (!current_organisation.value?.id) return null;
        error.value = null;
        try {
            await dsgApi.delete(`/material-generator-templates/${id}`, {
                params: { mobilizon_group_id: current_organisation.value.id }
            });
            await fetchTemplates();
        } catch {
            error.value = 'Vorlage konnte nicht gelöscht werden.';
        }
    }

    function getTemplate(id: number): MgTemplate | undefined {
        return templates.value.find((t) => t.id === id);
    }

    function listForContext(materialType: 'event' | 'eventList', dimension: string): MgTemplate[] {
        return templates.value.filter(
            (t) => t.material_type === materialType && t.dimension === dimension,
        );
    }

    return {
        templates,
        loading,
        error,
        fetchTemplates,
        saveTemplate,
        deleteTemplate,
        getTemplate,
        listForContext,
    };
}
