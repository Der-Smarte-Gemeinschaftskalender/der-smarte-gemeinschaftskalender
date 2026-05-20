<script setup lang="ts">
import { watch, onBeforeUnmount } from 'vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import TextAlign from '@tiptap/extension-text-align';

import FormInputLabel from './FormInputLabel.vue';
import Icon from '@/components/KERN/cosmetics/Icon.vue';

interface Props {
    label?: string;
    name: string;
    errors?: string;
    placeholder?: string;
    extended?: boolean;
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
        TextAlign.configure({
            types: ['heading', 'paragraph'],
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
        v-if="editor"
        :id="name"
        :label="label"
        :errors="errors"
        class="rich-text-editor-container"
    >
        <div class="w-full rich-text-wrapper">
            <section
                class="toolbar flex flex-wrap gap-2 align-items-center border-top-1 border-left-1 border-right-1 border-gray-300 p-2"
            >
                <Icon
                    name="bold"
                    color="none"
                    :disabled="!editor.can().chain().focus().toggleBold().run()"
                    :class="{ 'is-active': editor.isActive('bold') }"
                    class="cursor-pointer border-r-1"
                    @click="editor.chain().focus().toggleBold().run()"
                />
                <Icon
                    name="italic"
                    color="none"
                    :disabled="!editor.can().chain().focus().toggleItalic().run()"
                    :class="{ 'is-active': editor.isActive('italic') }"
                    @click="editor.chain().focus().toggleItalic().run()"
                />
                <Icon
                    name="underline"
                    color="none"
                    :disabled="!editor.can().chain().focus().toggleUnderline().run()"
                    :class="{ 'is-active': editor.isActive('underline') }"
                    @click="editor.chain().focus().toggleUnderline().run()"
                />
                <Icon
                    name="strike"
                    color="none"
                    :disabled="!editor.can().chain().focus().toggleStrike().run()"
                    :class="{ 'is-active': editor.isActive('strike') }"
                    @click="editor.chain().focus().toggleStrike().run()"
                />
                <Icon
                    name="h1"
                    color="none"
                    :class="{ 'is-active': editor.isActive('heading', { level: 1 }) }"
                    @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
                />
                <Icon
                    name="h2"
                    color="none"
                    :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }"
                    @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                />
                <Icon
                    :name="'h3'"
                    color="none"
                    :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }"
                    @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                />
                <template v-if="extended">
                    <Icon
                        :name="'h4'"
                        color="none"
                        :class="{ 'is-active': editor.isActive('heading', { level: 4 }) }"
                        @click="editor.chain().focus().toggleHeading({ level: 4 }).run()"
                    />
                    <Icon
                        :name="'h5'"
                        color="none"
                        :class="{ 'is-active': editor.isActive('heading', { level: 5 }) }"
                        @click="editor.chain().focus().toggleHeading({ level: 5 }).run()"
                    />
                </template>
                <Icon
                    name="bullet_list"
                    color="none"
                    :class="{ 'is-active': editor.isActive('bulletList') }"
                    @click="editor.chain().focus().toggleBulletList().run()"
                />
                <Icon
                    name="ordered_list"
                    color="none"
                    :class="{ 'is-active': editor.isActive('orderedList') }"
                    @click="editor.chain().focus().toggleOrderedList().run()"
                />
                <Icon
                    name="blockquote"
                    color="none"
                    :class="{ 'is-active': editor.isActive('blockquote') }"
                    @click="editor.chain().focus().toggleBlockquote().run()"
                />
                <Icon
                    name="link"
                    color="none"
                    :class="{ 'is-active': editor.isActive('link') }"
                    @click="addLink()"
                />
                <Icon
                    name="link_off"
                    color="none"
                    :disabled="!editor.isActive('link')"
                    @click="removeLink()"
                />
                <template v-if="extended">
                    <Icon
                        v-if="editor.isActive({ textAlign: 'right' })"
                        name="align_horizontal_left"
                        color="none"
                        :class="{ 'is-active': editor.isActive({ textAlign: 'left' }) }"
                        @click="editor.chain().focus().setTextAlign('left').run()"
                    />
                    <Icon
                        v-if="
                            editor.isActive({ textAlign: 'left' }) ||
                            (!editor.isActive({ textAlign: 'right' }) && !editor.isActive({ textAlign: 'center' }))
                        "
                        name="align_horizontal_center"
                        color="none"
                        :class="{ 'is-active': editor.isActive({ textAlign: 'center' }) }"
                        @click="editor.chain().focus().setTextAlign('center').run()"
                    />
                    <Icon
                        v-if="editor.isActive({ textAlign: 'center' })"
                        name="align_horizontal_right"
                        color="none"
                        :class="{ 'is-active': editor.isActive({ textAlign: 'right' }) }"
                        @click="editor.chain().focus().setTextAlign('right').run()"
                    />
                </template>

                <Icon
                    name="undo"
                    color="none"
                    :disabled="!editor.can().chain().focus().undo().run()"
                    @click="editor.chain().focus().undo().run()"
                />
                <Icon
                    name="redo"
                    color="none"
                    :disabled="!editor.can().chain().focus().redo().run()"
                    @click="editor.chain().focus().redo().run()"
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

<style>
.kern-form-input--error .prose {
    border-color: #bd0f09 !important;
    background-color: #feece8 !important;
}

.rich-text-wrapper {
    ::-webkit-scrollbar {
        display: none;
    }

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
}
</style>
