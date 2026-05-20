<script setup lang="ts">

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
    | 'contact'
    | 'bold'
    | 'italic'
    | 'strike'
    | 'h1'
    | 'h2'
    | 'h3'
    | 'h4'
    | 'h5'
    | 'bullet-list'
    | 'ordered-list'
    | 'blockquote'
    | 'undo'
    | 'redo'
    | 'underline'
    | 'event_available'
    | 'sync'
    | 'image'
    | 'hexagon'
    | 'line'
    | 'triangle'
    | 'ellipse'
    | 'stacked_email'
    | 'align_horizontal_left'
    | 'align_horizontal_center'
    | 'align_horizontal_right'
    | 'sort_by_alpha'
    | 'arrow_upward'
    | 'arrow_downward'
    | 'star'
    | 'star_outline'
    | 'stamp'
    | 'text_fields'
    | 'keyboard_arrow_up'
    | 'keyboard_arrow_down'
    | 'keyboard_double_arrow_up'
    | 'keyboard_double_arrow_down';

type ColorName = 'black' | 'white' | 'none';

interface Props {
    name: IconName | string;
    size?: IconSize;
    color?: ColorName;
    disabled?: boolean;
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
        'bold', //https://fonts.google.com/icons?selected=Material+Symbols+Outlined:format_bold:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=bold&icon.size=24&icon.color=%23000000
        'italic', //https://fonts.google.com/icons?selected=Material+Symbols+Outlined:format_italic:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=italic&icon.size=24&icon.color=%23000000
        'strike', //https://fonts.google.com/icons?selected=Material+Symbols+Outlined:format_strikethrough:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=stri&icon.size=24&icon.color=%23000000
        'h1', //https://fonts.google.com/icons?selected=Material+Symbols+Outlined:format_h1:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=h1&icon.size=24&icon.color=%23000000
        'h2', //https://fonts.google.com/icons?selected=Material+Symbols+Outlined:format_h2:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=h2&icon.size=24&icon.color=%23000000
        'h3', //https://fonts.google.com/icons?selected=Material+Symbols+Outlined:format_h3:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=h3&icon.size=24&icon.color=%23000000
        'h4', //https://fonts.google.com/icons?selected=Material+Symbols+Outlined:format_h4:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=h4&icon.size=24&icon.color=%23000000
        'h5', //https://fonts.google.com/icons?selected=Material+Symbols+Outlined:format_h5:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=h5&icon.size=24&icon.color=%23000000
        'bullet_list', //https://fonts.google.com/icons?selected=Material+Symbols+Outlined:format_list_bulleted:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=bullet&icon.size=24&icon.color=%23000000
        'ordered_list', //https://fonts.google.com/icons?selected=Material+Symbols+Outlined:format_list_numbered:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=ordere&icon.size=24&icon.color=%23000000
        'blockquote', //https://fonts.google.com/icons?selected=Material+Symbols+Outlined:format_quote:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=quote&icon.size=24&icon.color=%23000000
        'undo', //https://fonts.google.com/icons?selected=Material+Symbols+Outlined:undo:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=undo&icon.size=24&icon.color=%23000000
        'redo', //https://fonts.google.com/icons?selected=Material+Symbols+Outlined:redo:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=redo&icon.size=24&icon.color=%23000000
        'underline', //https://fonts.google.com/icons?selected=Material+Symbols+Outlined:format_underlined:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=underline&icon.size=24&icon.color=%23000000
        'event_available', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:event_available:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=event&icon.size=24&icon.color=%231f1f1f
        'sync', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:sync:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=sync&icon.size=24&icon.color=%23000000
        'image', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:image:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=image&icon.size=24&icon.color=%23000000
        'hexagon', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:hexagon:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=hexagon&icon.size=24&icon.color=%23000000
        'line', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:check_indeterminate_small:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=dash&icon.size=24&icon.color=%23000000
        'triangle', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:change_history:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=triangle&icon.size=24&icon.color=%23000000
        'circle', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:radio_button_unchecked:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=circle&icon.size=24&icon.color=%23000000
        'square', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:check_box_outline_blank:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=square&icon.size=24&icon.color=%23000000
        'ellipse', // custom
        'stacked_email', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:stacked_email:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=email&icon.size=24&icon.color=%231f1f1f
        'align_horizontal_left', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:align_horizontal_left:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=left&icon.size=24&icon.color=%23000000
        'align_horizontal_center', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:align_horizontal_center:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=center&icon.size=24&icon.color=%23000000
        'align_horizontal_right', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:align_horizontal_right:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=right&icon.size=24&icon.color=%23000000
        'sort_by_alpha', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:sort_by_alpha:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=sort&icon.size=24&icon.color=%23000000
        'arrow_upward', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:north:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=arrow+up&icon.size=24&icon.color=%23000000
        'arrow_downward', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:south:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=arrow+d&icon.size=24&icon.color=%23000000
        'star_outline', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:star:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=star&icon.size=24&icon.color=%23000000
        'star', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:star:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=star&icon.size=24&icon.color=%23000000
        'stamp', //  https://fonts.google.com/icons?selected=Material+Symbols+Outlined:approval:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=stamp&icon.size=24&icon.color=%23000000
        'text_fields', // https://fonts.google.com/icons?selected=Material+Symbols+Outlined:text_fields:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=text&icon.size=24&icon.color=%23000000
        'keyboard_arrow_up',
        'keyboard_arrow_down',
        'keyboard_double_arrow_up',
        'keyboard_double_arrow_down',
    ].includes(name);
};

const getIconStyle = (color?: ColorName, disabled?: boolean) => {
    const styles: Record<string, string> = {};

    switch (color) {
        case 'white':
            styles.filter = 'brightness(0) invert(1)';
            break;
        case 'black':
            styles.filter = 'brightness(0)';
            break;
        case 'none':
        default:
            styles.filter = 'none';
    }
  
    if (disabled) {
        styles.opacity = '0.5';
        styles.pointerEvents = 'none';
    }

    return styles;
};

withDefaults(defineProps<Props>(), {
    size: 'md',
    color: 'black',
});
</script>

<template>
    <span
        v-if="isMaterialIcons(name)"
        v-bind="$attrs"
        :class="`kern-icon kern-icon--${name} kern-icon--${size}`"
        :style="getIconStyle(color, disabled)"
        aria-hidden="true"
    />
    <img
        v-else
        v-bind="$attrs"
        :style="getIconStyle(color, disabled)"
        :src="`/icons/${name}.svg`"
        :class="`kern-icon kern-icon--${name} kern-icon--${size}`"
        aria-hidden="true"
        alt=""
    />
</template>

<style scoped lang="scss">
img.kern-icon {
    user-select: none;
    background-color: transparent;
}
</style>
