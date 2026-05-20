<script lang="ts" setup>
import { ref, computed } from 'vue';
import { findOrganisations } from '@/lib/mobilizonClient';
import { getFeaturedOrganisations } from '@/lib/dsgClient';

import OrganisationCard from '@/components/OrganisationCard.vue';
import Button from '@/components/KERN/Button.vue';
import InputText from '@/components/KERN/inputs/InputText.vue';
import Icon from '@/components/KERN/cosmetics/Icon.vue';

import type { IOrganisation } from '@/types/General';

const loadedOrganisations = ref<IOrganisation[]>([]);
const organisationsById = ref<Map<string, IOrganisation>>(new Map());

const possibleFirstCharakters = [...'ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÜ'.split(''), '#', '0-9'];

const selectedFirstCharakterLabels: Record<string, string> = {
    '#': 'Sonderzeichen',
    '0-9': 'Zahlen',
};

const foundFirstCharakters = ref<string[]>([]);
const selectedFirstCharakter = ref<string>('All');
const searchFilteredIds = ref<Set<string>>(new Set());
const charFilteredIds = ref<Set<string>>(new Set());
const searchTerm = ref<string>('');
const activeSearchTerm = ref<string>('');
const sortOrder = ref<'asc' | 'desc' | 'none'>('asc');

const displayedOrganisations = computed<IOrganisation[]>(() => {
    let idsToDisplay: Set<string>;

    if (activeSearchTerm.value.trim() !== '') {
        idsToDisplay =
            selectedFirstCharakter.value !== 'All'
                ? new Set([...searchFilteredIds.value].filter((id) => charFilteredIds.value.has(id)))
                : searchFilteredIds.value;
    } else if (selectedFirstCharakter.value !== 'All') {
        idsToDisplay = charFilteredIds.value;
    } else {
        idsToDisplay = new Set(organisationsById.value.keys());
    }

    let orgs = Array.from(idsToDisplay)
        .map((id) => organisationsById.value.get(id))
        .filter((org): org is IOrganisation => org !== undefined);

    if (sortOrder.value !== 'none') {
        // Copy the array before sorting for better performance!
        orgs = [...orgs].sort((a, b) => {
            if (a.is_featured && !b.is_featured) return -1;
            if (!a.is_featured && b.is_featured) return 1;

            const nameA = a.name?.toLowerCase() || '';
            const nameB = b.name?.toLowerCase() || '';
            return sortOrder.value === 'asc' ? nameA.localeCompare(nameB) : nameB.localeCompare(nameA);
        });
    } else {
        orgs = [...orgs].sort((a, b) => {
            if (a.is_featured && !b.is_featured) return -1;
            if (!a.is_featured && b.is_featured) return 1;
            return 0;
        });
    }

    return orgs;
});

const loadOrganisations = async () => {
    try {
        const result = await findOrganisations();

        const featuredIds = await getFeaturedOrganisations();
        const organisationsWithFeaturedStatus = result.elements.map((org) => ({
            ...org,
            is_featured: featuredIds.has(org.id),
        }));

        loadedOrganisations.value = organisationsWithFeaturedStatus;

        organisationsById.value = new Map(organisationsWithFeaturedStatus.map((org) => [org.id, org]));

        foundFirstCharakters.value = extractFirstLetters(organisationsWithFeaturedStatus);
    } catch (error) {
        console.error('Error loading events:', error);
    }
};

const extractFirstLetters = (organisations: IOrganisation[]) => {
    const letters = new Set<string>();

    organisations.forEach((org) => {
        if (org.name && typeof org.name === 'string') {
            const firstChar = org.name.charAt(0).toUpperCase();

            if (!/^[A-ZÄÖÜ0-9]/.test(firstChar)) {
                letters.add('#');
            }
            if (/[0-9]/.test(firstChar)) {
                letters.add('0-9');
            } else {
                letters.add(firstChar);
            }
        }
    });

    return Array.from(letters).sort();
};

const filterByFirstCharakter = (char: string, resetOnEmpty: boolean = false) => {
    selectedFirstCharakter.value = selectedFirstCharakter.value === char && !resetOnEmpty ? 'All' : char;

    if (char === 'All') {
        charFilteredIds.value = new Set();
        return;
    }

    const filteredIds = new Set<string>();

    for (const [id, org] of organisationsById.value) {
        if (!org.name || typeof org.name !== 'string') continue;

        const firstChar = org.name.charAt(0).toUpperCase();

        let matches = false;
        switch (char) {
            case '#':
                matches = !/^[A-ZÄÖÜ0-9]/.test(firstChar);
                break;
            case '0-9':
                matches = /^[0-9]/.test(firstChar);
                break;
            default:
                matches = firstChar === char;
        }

        if (matches) {
            filteredIds.add(id);
        }
    }

    charFilteredIds.value = filteredIds;

    if (resetOnEmpty && filteredIds.size === 0) {
        selectedFirstCharakter.value = 'All';
        charFilteredIds.value = new Set();
    }
};

const searchByQuery = () => {
    activeSearchTerm.value = searchTerm.value;

    if (searchTerm.value.trim() === '') {
        searchFilteredIds.value = new Set();
        foundFirstCharakters.value = extractFirstLetters(loadedOrganisations.value);

        if (
            selectedFirstCharakter.value !== 'All' &&
            !foundFirstCharakters.value.includes(selectedFirstCharakter.value)
        ) {
            selectedFirstCharakter.value = 'All';
            charFilteredIds.value = new Set();
        } else {
            filterByFirstCharakter(selectedFirstCharakter.value, true);
        }
        return;
    }

    const searchLower = searchTerm.value.toLowerCase();
    const filteredIds = new Set<string>();
    const matchedOrgs: IOrganisation[] = [];

    for (const [id, org] of organisationsById.value) {
        const matches =
            org.name?.toLowerCase().includes(searchLower) ||
            org.preferredUsername?.toLowerCase().includes(searchLower) ||
            org.summary?.toLowerCase().includes(searchLower);

        if (matches) {
            filteredIds.add(id);
            matchedOrgs.push(org);
        }
    }

    searchFilteredIds.value = filteredIds;
    foundFirstCharakters.value = extractFirstLetters(matchedOrgs);

    if (selectedFirstCharakter.value !== 'All' && !foundFirstCharakters.value.includes(selectedFirstCharakter.value)) {
        selectedFirstCharakter.value = 'All';
        charFilteredIds.value = new Set();
    } else {
        filterByFirstCharakter(selectedFirstCharakter.value, true);
    }
};

const sortOrganisations = (order: 'asc' | 'desc' | 'none') => {
    sortOrder.value = order;
};

loadOrganisations();
</script>
<template>
    <Teleport to="#headerslot">
        <div class="mb-3 mt-4 sm:mb-4 sm:mt-5 md:my-6">
            <h1 class="kern-heading text-theme-primary">{{ $t('public.header.organisations') }}</h1>
        </div>
    </Teleport>
    <div class="w-full">
        <form
            class="flex w-full flex-row gap-4 mb-5"
            @submit.prevent="searchByQuery()"
        >
            <InputText
                v-model="searchTerm"
                aria-label="Suchbegriff"
                name="searchTerm"
                class="w-full"
                :placeholder="$t('public.search.organisationSearchPlaceholder')"
            />
            <div class="flex items-center gap-2">
                <Button
                    :label="$t('public.search.search')"
                    type="submit"
                    icon-left="search"
                    :hide-text-on-mobile="true"
                    @click.prevent="searchByQuery()"
                />
                <Button
                    :variant="sortOrder !== 'none' ? 'primary' : 'secondary'"
                    aria-label="Alphabetisch sortieren"
                    title="Alphabetisch sortieren"
                    class="min-h-1rem w-fit flex items-center"
                    @click.prevent="
                        sortOrganisations(sortOrder === 'asc' ? 'desc' : sortOrder === 'desc' ? 'none' : 'asc')
                    "
                >
                    <div class="relative">
                        <Icon
                            name="sort_by_alpha"
                            :class="sortOrder !== 'none' ? 'mr-2' : ''"
                            :color="sortOrder !== 'none' ? 'white' : 'black'"
                        />

                        <Icon
                            v-if="sortOrder === 'asc'"
                            class="absolute h-full"
                            size="sm"
                            style="left: 1.375rem"
                            name="arrow_upward"
                            color="white"
                        />
                        <Icon
                            v-else-if="sortOrder === 'desc'"
                            class="absolute h-full"
                            size="sm"
                            style="left: 1.375rem"
                            name="arrow_downward"
                            color="white"
                        />
                    </div>
                </Button>
            </div>
        </form>

        <div class="flex flex-row flex-wrap gap-2 mb-6">
            <Button
                v-for="char in possibleFirstCharakters"
                :key="char"
                :variant="char === selectedFirstCharakter ? 'primary' : 'secondary'"
                :label="char"
                :aria-label="`Nach Organisationen filtern, die mit ${char} beginnen`"
                class="h-2rem min-h-2rem p-0"
                :class="char !== '0-9' ? 'w-2rem ' : 'w-fit'"
                :disabled="!foundFirstCharakters.includes(char)"
                @click="filterByFirstCharakter(char)"
            />
        </div>

        <h4 class="kern-heading font-medium text-theme-primary">
            {{
                selectedFirstCharakter === 'All'
                    ? $t('public.search.allOrganisations')
                    : $t('public.search.organisationsStartingWith', { letter: selectedFirstCharakterLabels[selectedFirstCharakter] || selectedFirstCharakter })
            }}
        </h4>
        <h5 class="kern-heading font-semilight text-theme-primary mb-4">
            {{ displayedOrganisations.length }} {{ $t('public.search.orgnisationsFound') }}
        </h5>
        <div class="cards-template">
            <template
                v-for="organisation in displayedOrganisations"
                :key="organisation.id"
            >
                <OrganisationCard
                    :organisation="organisation"
                />
            </template>
        </div>
    </div>
</template>
<style scoped lang="scss">
.kern-row {
    .kern-card {
        width: 100%;
        max-width: 350px;
    }
}
</style>
