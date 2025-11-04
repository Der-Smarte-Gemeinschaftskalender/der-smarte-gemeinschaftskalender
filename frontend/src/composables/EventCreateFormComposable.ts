import type { AddressForm } from "@/types/Mobilizon";
import { getMobilizionGroupOptions } from "@/lib/dsgClient";
import { normalizeStreet } from "@/lib/helper";
import { current_organisation, user_organisations } from "@/composables/OrganisationComposable";

import type { Ref } from "vue";
import type { Option } from "@/types/General";

export const buildSuggestions = (suggestions: AddressForm[], postalCodeOnly: boolean = false): string[] => {
    if (postalCodeOnly) {
        const postalCodes = suggestions.map((suggestion: AddressForm) => suggestion.postalCode || '').filter((item: string) => item.trim());
        return [...new Set(postalCodes)];
    }

    const built =  suggestions.map((suggestion: AddressForm) => {

        if (!suggestion.street?.trim() && !suggestion.locality?.trim()) {
            return [suggestion.description, suggestion.region].filter(part => part?.trim()).join(', ');
        }

        const street = suggestion.street ? normalizeStreet(suggestion.street) : '';
        const region = suggestion.region || suggestion.country || '';
        const locality = suggestion.postalCode && suggestion.locality
            ? `${ suggestion.postalCode } ${ suggestion.locality }`
            : suggestion.locality || suggestion.postalCode || '';

        return !street && !locality
            ? ''
            : [street, locality, region].filter(part => part.trim()).join(', ');
    });

    return  [...new Set(built)].filter((item: string) => item.trim());
};


export const loadMobilizionGroups = async (mobilizion_group_id: Ref<number | null>, mobilizionGroupOptions: Ref<Option[]>): Promise<void> => {
    try {
        mobilizionGroupOptions.value = await getMobilizionGroupOptions(user_organisations.value);
        mobilizion_group_id.value = current_organisation.value?.id || null;

    } catch (error) {
        console.error(error);
    }
};