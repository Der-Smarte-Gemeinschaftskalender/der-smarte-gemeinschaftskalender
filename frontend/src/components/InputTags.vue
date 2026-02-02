<script lang="ts" setup>
import { ref, defineModel } from 'vue';
import InputText from './KERN/inputs/InputText.vue';
import Button from './KERN/Button.vue';
import { current_organisation } from '@/composables/OrganisationComposable';
import { dsgApi } from '@/lib/dsgApi';
import Icon from './KERN/cosmetics/Icon.vue';

const newTag = ref<string>('');
const tags = defineModel<string[]>([]);
const notUsedSuggestions = ref<string[]>([]);
const loadedSuggestions = ref<string[]>([]);

interface Props {
    suggestions?: string[];
    showSuggesstions?: boolean;
}

const { suggestions, showSuggesstions } = withDefaults(defineProps<Props>(), {
    suggestions: [],
    showSuggesstions: true,
});

notUsedSuggestions.value = suggestions;

const loadMostUsedTags = async () => {
    if (!showSuggesstions) return;
    const { data } = await dsgApi.get(`/organisations/mostUsedTags/?groupId=${current_organisation.value.id}`);
    loadedSuggestions.value = data;
    notUsedSuggestions.value = [...loadedSuggestions.value];
};
loadMostUsedTags();

const addTag = (text: string | null = null) => {
    if (!text) text = newTag.value;
    if (!text || !text.length) return;
    if (text.trim() !== '') {
        const tagsToAdd = text
            .split(',')
            .map((tag) => tag.trim())
            .filter((tag) => tag !== '')
            .filter((tag) => !tags.value.includes(tag) && tag.length > 2);
        tags.value.push(...tagsToAdd);
        newTag.value = '';
    }
};
const addSuggestion = (suggestion: string) => {
    addTag(suggestion);
    notUsedSuggestions.value = notUsedSuggestions.value.filter((s) => s !== suggestion);
};
const removeTag = (tag: string) => {
    tags.value = tags.value.filter((t) => t !== tag);
    notUsedSuggestions.value = [...loadedSuggestions.value];
};
</script>
<template>
    <div class="gap-2">
        <div class="flex flex-wrap align-items-baseline gap-4">
            <div class="col-12 md:col-6 px-0">
                <InputText
                    v-model="newTag"
                    label="Schlagwörter hinzufügen"
                    name="tags"
                    @keyup.enter="addTag(newTag)"
                />
            </div>

            <div class="">
                <Button
                    class="mt-5"
                    icon-left="add"
                    @click.prevent="addTag(newTag)"
                >
                    Hinzufügen
                </Button>
            </div>
        </div>
        <div class="mt-3 selected-tag-list">
            <template v-if="!tags.length">
                <p class="mb-2 kern-text flex items-center">
                    <Icon
                        name="info"
                        class="pt-2 mr-2"
                    />
                    Du hast noch keine Schlagwörter ausgewählt.
                </p>
            </template>
            <template
                v-for="tag in tags"
                :key="tag"
            >
                <Button
                    variant="secondary"
                    icon-left="delete"
                    class="mr-2 mb-2"
                    @click.capture="removeTag(tag)"
                >
                    {{ tag }}
                </Button>
            </template>
        </div>
        <div v-if="notUsedSuggestions && notUsedSuggestions.length > 0">
            <p class="kern-text mb-2">Vorschläge, basierend auf bisher genutzten Schlagwörtern deiner Organisation:</p>

            <template
                v-for="suggestion in notUsedSuggestions"
                :key="suggestion"
            >
                <Button
                    v-if="!tags.find((tag) => suggestion === tag)?.length"
                    variant="secondary"
                    icon-left="add"
                    :submit="false"
                    class="mr-2 mb-2 tag-suggestion-button"
                    @click="addSuggestion(suggestion)"
                >
                    {{ suggestion }}
                </Button>
            </template>
        </div>
    </div>
</template>
<style>
.tag-suggestion-button {
    border-color: #545454;
    color: #545454;
}
.selected-tag-list {
    min-height: 4rem;
}
</style>
