<script setup lang="ts">
import { defineProps } from 'vue';

type IconSize = 'sm' | 'md' | 'lg' | 'xl';

type IconName =
    | 'none' // as a placeholder
    | 'home'
    | 'arrow-up'
    | 'arrow-down'
    | 'arrow-forward'
    | 'arrow-back'
    | 'info'
    | 'success'
    | 'warning'
    | 'danger'
    | 'open-in-new'
    | 'download'
    | 'logout'
    | 'checklist'
    | 'mail'
    | 'edit'
    | 'sign-language'
    | 'easy-language'
    | 'autorenew'
    | 'add'
    | 'close'
    | 'delete'
    | 'search'
    | 'question-mark'
    | 'more-vert'
    | 'content-copy'
    | 'visibility'
    | 'visibility-off'
    | 'check'
    | 'drive-folder-upload'
    | 'chevron-left'
    | 'chevron-right'
    | 'keyboard-double-arrow-left'
    | 'keyboard-double-arrow-right'
    | 'link'
    | 'link_off'
    | 'person'
    | 'calendar_month'
    | 'notifications'
    | 'list_alt'
    | 'location_on'
    | 'login'
    | 'schedule'
    | 'share'
    | 'language'
    | 'wall_art'
    | 'home_repair_service'
    | 'group'
    | 'settings'
    | 'menu'
    | 'contact';

type ColorName = 'black' | 'white';

interface Props {
    name: IconName | string;
    size?: IconSize;
    color?: ColorName;
}

const isMaterialIcons = (name: string): boolean => {
    return ![
        'link', // https://fonts.google.com/icons?icon.query=link&selected=Material+Symbols+Outlined:link:FILL@0;wght@400;GRAD@0;opsz@24&icon.size=24&icon.color=%232B2C6A
        // "logout", // https://fonts.google.com/icons?icon.query=logout&selected=Material+Symbols+Outlined:logout:FILL@0;wght@400;GRAD@0;opsz@24&icon.size=24&icon.color=%231f1f1f
        'person', // https://fonts.google.com/icons?icon.query=user&selected=Material+Symbols+Outlined:person:FILL@0;wght@400;GRAD@0;opsz@24&icon.size=24&icon.color=%231f1f1f,
        'calendar_month', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:calendar_month:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=calendar&icon.size=24&icon.color=%231f1f1f
        'link_off', // https://fonts.google.com/icons?icon.size=24&icon.color=%231f1f1f&selected=Material+Symbols+Outlined:link_off:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=deactivate
        'notifications', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:notifications:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=notifi&icon.size=24&icon.color=%23e3e3e3
        'list_alt', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:list_alt:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=list&icon.size=24&icon.color=%231f1f1f
        'account_balance', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:account_balance:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=business&icon.size=24&icon.color=%231f1f1f
        'location_on', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:location_on:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=location&icon.size=24&icon.color=%23%231f1f1f
        'login', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:login:FILL@0;wght@400;GRAD@0;opsz@24&icon.size=24&icon.color=%231f1f1f
        'schedule', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:schedule:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=clock&icon.size=24&icon.color=%231f1f1f
        'share', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:share:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=share&icon.size=24&icon.color=%231f1f1f
        'language', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:language:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=language&icon.size=24&icon.color=%231f1f1f
        'wall_art', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:wall_art:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=poster&icon.size=24&icon.color=%231f1f1f
        'home_repair_service', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:home_repair_service:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=kit&icon.size=24&icon.color=%231f1f1f
        'list', //https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=list
        'tune', //https://fonts.google.com/icons?selected=Material+Symbols+Outlined:tune:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=filters&icon.size=24&icon.color=%23000000
        'group', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:group:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=group&icon.size=24&icon.color=%231f1f1f
        'settings', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:settings:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=cog&icon.size=24&icon.color=%231f1f1f
        'menu', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:menu_open:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=menu&icon.size=24&icon.color=%23000000,
        'contact', //https://fonts.google.com/icons?selected=Material+Symbols+Outlined:contact_page:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=about&icon.size=24&icon.color=%23000000
    ].includes(name);
};

withDefaults(defineProps<Props>(), {
    size: 'md',
});
</script>

<template>
    <span
        v-if="isMaterialIcons(name)"
        :class="`kern-icon kern-icon--${name} kern-icon--${size}`"
        :style="`${color === 'white' ? 'filter: brightness(0) invert(1)' : ''}`"
        aria-hidden="true"
    />
    <img
        v-else
        :style="`${color === 'white' ? 'filter: brightness(0) invert(1)' : ''}`"
        :src="`/icons/${name}.svg`"
        :class="`kern-icon kern-icon--${size}`"
        aria-hidden="true"
        alt=""
    />
</template>

<style scoped lang="scss">

img.kern-icon {
  background-color: transparent !important;
}
</style>
