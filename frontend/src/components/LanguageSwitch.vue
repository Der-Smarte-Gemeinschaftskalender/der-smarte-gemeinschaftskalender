<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { switchDayjsLocale } from '@/lib/dayjs';
import { useI18n } from 'vue-i18n';
import Button from '@/components/KERN/Button.vue';
import Icon from '@/components/KERN/cosmetics/Icon.vue';
import DropdownList from '@/components/DropdownList.vue';

const { locale } = useI18n();
const dropdownRef = ref<HTMLElement | null>(null);
const listRef = ref<InstanceType<typeof DropdownList> | null>(null);

const languages = computed(() => [
    { code: 'de', label: 'Deutsch' },
    { code: 'en', label: 'English' },
]);

const currentLanguage = computed(() => languages.value.find((lang) => lang.code === locale.value));

const languageOptions = computed(() =>
    languages.value.map((lang) => ({
        value: lang.code,
        label: lang.label,
    }))
);

const toggleDropdown = () => {
    listRef.value?.toggleDropdown();
};

const switchLanguage = (langCode: string) => {
    locale.value = langCode;
    localStorage.setItem('locale', langCode);
    document.documentElement.setAttribute('lang', langCode);
    switchDayjsLocale(langCode);
};

const handleClickOutside = (event: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        listRef.value?.closeDropdown();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div
        ref="dropdownRef"
        class="language-switcher relative"
    >
        <Button
            variant="secondary"
            class="language-btn text-sm px-2"
            body-class="flex align-items-center"
            :aria-label="`${currentLanguage?.label} - Change language`"
            :title="currentLanguage?.label"
            @click="toggleDropdown"
        >
            <Icon
                name="language"
                class="mr-2"
            />
            {{ currentLanguage?.code.toUpperCase() }}
        </Button>

        <div class="language-dropdown absolute top-full left-0 z-5">
            <DropdownList
                ref="listRef"
                :options="languageOptions"
                :selected="currentLanguage?.code"
                @select="switchLanguage($event as string)"
            />
        </div>
    </div>
</template>

<style scoped>
.language-dropdown {
    top: calc(100% + 0.5rem);
}
</style>
