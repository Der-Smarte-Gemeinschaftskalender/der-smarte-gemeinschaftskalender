<script setup lang="ts">
import { watch, onBeforeUnmount } from 'vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';

import FormInputLabel from './FormInputLabel.vue';
import Icon from '@/components/KERN/cosmetics/Icon.vue';

interface Props {
    label?: string;
    name: string;
    errors?: string;
    placeholder?: string;
}

const model = defineModel<string>();
const props = defineProps<Props>();

const editor = useEditor({
    editorProps: {
        attributes: {
            class: 'prose prose-sm sm:prose lg:prose-lg xl:prose-xl border-1 outline-none shadow-none rounded-md p-2 pl-4 w-full min-h-8rem max-h-12rem overflow-scroll',
        },
    },
    content: model.value || '',
    extensions: [
        StarterKit,
        Link.configure({
            shouldAutoLink: (url) => url.startsWith('https://'),
            HTMLAttributes: {
                rel: 'noopener noreferrer',
                target: '_blank',
            },
        }),
    ],
});

function addLink() {
    if (!editor.value) return;

    const previousUrl = editor.value.getAttributes('link').href;
    const url = window.prompt('Eingabe URL', previousUrl || 'https://');

    if (url === null) return;
    if (url === '') {
        editor.value.chain().focus().unsetLink().run();
        return;
    }

    editor.value.chain().focus().setLink({ href: url }).run();
}

function removeLink() {
    if (!editor.value) return;
    editor.value.chain().focus().unsetLink().run();
}

watch(
    () => model.value,
    (value) => {
        if (editor.value && value !== editor.value.getHTML()) {
            editor.value.commands.setContent(value || '');
        }
    }
);

watch(
    () => editor.value?.getHTML(),
    (html) => {
        if (html && html !== model.value) model.value = html;
    }
);

onBeforeUnmount(() => {
    editor.value?.destroy();
});
</script>

<template>
    <FormInputLabel
        :id="name"
        :label="label"
        :errors="errors"
        class="rich-text-editor-container"
    >
        <div class="w-full">
            <section
                class="toolbar flex flex-wrap gap-2 align-items-center border-top-1 border-left-1 border-right-1 border-gray-300 p-2"
            >
                <Icon
                    name="bold"
                    @click="editor?.chain().focus().toggleBold().run()"
                    :disabled="!editor?.can().chain().focus().toggleBold().run()"
                    :class="{ 'is-active': editor?.isActive('bold') }"
                    class="cursor-pointer border-r-1"
                />
                <Icon
                    name="italic"
                    @click="editor?.chain().focus().toggleItalic().run()"
                    :disabled="!editor?.can().chain().focus().toggleItalic().run()"
                    :class="{ 'is-active': editor?.isActive('italic') }"
                />
                <Icon
                    name="strike"
                    @click="editor?.chain().focus().toggleStrike().run()"
                    :disabled="!editor?.can().chain().focus().toggleStrike().run()"
                    :class="{ 'is-active': editor?.isActive('strike') }"
                />
                <Icon
                    name="h1"
                    @click="editor?.chain().focus().toggleHeading({ level: 1 }).run()"
                    :class="{ 'is-active': editor?.isActive('heading', { level: 1 }) }"
                />
                <Icon
                    name="h2"
                    @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()"
                    :class="{ 'is-active': editor?.isActive('heading', { level: 2 }) }"
                />
                <Icon
                    :name="'h3'"
                    @click="editor?.chain().focus().toggleHeading({ level: 3 }).run()"
                    :class="{ 'is-active': editor?.isActive('heading', { level: 3 }) }"
                />
                <Icon
                    name="bullet_list"
                    @click="editor?.chain().focus().toggleBulletList().run()"
                    :class="{ 'is-active': editor?.isActive('bulletList') }"
                />
                <Icon
                    name="ordered_list"
                    @click="editor?.chain().focus().toggleOrderedList().run()"
                    :class="{ 'is-active': editor?.isActive('orderedList') }"
                />
                <Icon
                    name="blockquote"
                    @click="editor?.chain().focus().toggleBlockquote().run()"
                    :class="{ 'is-active': editor?.isActive('blockquote') }"
                />
                <Icon
                    name="link"
                    @click="addLink()"
                    :class="{ 'is-active': editor?.isActive('link') }"
                />
                <Icon
                    name="link_off"
                    @click="removeLink()"
                    :disabled="!editor?.isActive('link')"
                />

                <Icon
                    name="undo"
                    @click="editor?.chain().focus().undo().run()"
                    :disabled="!editor?.can().chain().focus().undo().run()"
                />
                <Icon
                    name="redo"
                    @click="editor?.chain().focus().redo().run()"
                    :disabled="!editor?.can().chain().focus().redo().run()"
                />
            </section>
            <EditorContent
                v-if="editor"
                :editor="editor"
                :aria-describedby="errors ? `${props.name}-error` : undefined"
                class="rich-text-editor"
            />
        </div>
    </FormInputLabel>
</template>

<style scoped>
.toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;

    .kern-icon {
        border-radius: 6px;
        padding: 4px;
        transition: all 0.15s ease;
        cursor: pointer;
        scale: 1.2;

        &:hover {
            opacity: 1;
            filter: brightness(1);
            background-color: #f3f3f3;
        }

        &.is-active {
            opacity: 1;
            filter: none;
            background-color: rgba(43, 44, 106, 0.2);
        }

        &[disabled] {
            opacity: 0.4;
            pointer-events: none;
            filter: grayscale(1);
        }
    }
}
</style>
