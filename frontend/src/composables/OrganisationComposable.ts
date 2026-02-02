import dayjs from "@/lib/dayjs";
import { ref } from "vue";
import { allMobilizionGroups } from "@/lib/dsgClient";
import type { MobilizonGroup } from "@/types/Mobilizon";
import type { Option } from "@/types/General";

export const current_organisation = ref<MobilizonGroup | null>(
    localStorage.getItem("current_organisation") ? JSON.parse(<string>localStorage.getItem("current_organisation")) : null
);

export const user_organisations = ref<MobilizonGroup[] | null>(
    localStorage.getItem("user_organisations") ? JSON.parse(<string>localStorage.getItem("user_organisations")) : null
);

export const last_groups_sync = ref<string>(
    localStorage.getItem("last_groups_sync") ? JSON.parse(<string>localStorage.getItem("last_groups_sync")) : null
);

export const setOrganisationData = async (newUserOrganisation: MobilizonGroup | null = null, forceReload: boolean = false): Promise<void> => {
    const isStale = !last_groups_sync.value || !dayjs(last_groups_sync.value).isValid() || dayjs(last_groups_sync.value).isBefore(dayjs().subtract(10, "minutes"));

    if (!user_organisations.value || forceReload || isStale) {
        user_organisations.value = await allMobilizionGroups();
        last_groups_sync.value = new Date().toISOString();
    }

    if (newUserOrganisation) current_organisation.value = newUserOrganisation;
    else if (!current_organisation.value) {
        current_organisation.value = !user_organisations.value?.length
            ? null
            : user_organisations?.value[0];
    }

    localStorage.setItem("user_organisations", JSON.stringify(user_organisations.value));
    localStorage.setItem("current_organisation", JSON.stringify(current_organisation.value));
    localStorage.setItem("last_groups_sync", JSON.stringify(last_groups_sync.value));
};

export const getCurrentOrganisationOptionId = (options: Option[]): number => {
    return options.find(
        (option) => option.value === current_organisation.value?.id
    )?.value;
}