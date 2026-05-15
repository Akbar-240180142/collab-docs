<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import { router } from '@inertiajs/vue3'
import { onMounted, onUnmounted, ref } from 'vue'
import Echo from 'laravel-echo'

const props = defineProps({
  documentId: String,
  initialContent: String
})

// State UI
const saveStatus = ref('saved')
const lastEditor = ref('')
let saveTimeout = null
let isRemoteUpdate = false
let echo = null

// 1. Setup Editor (Dengan StarterKit Lengkap)
const editor = useEditor({
  extensions: [
    StarterKit, // Sudah include Bold, Italic, Heading, List, Undo, Redo
  ],
  content: props.initialContent || '<p>Mulai mengetik di sini...</p>',
  autofocus: true,
  immediatelyRender: false,
  
  onUpdate: ({ editor }) => {
    if (isRemoteUpdate) {
      isRemoteUpdate = false
      return
    }

    saveStatus.value = 'saving'
    
    if (saveTimeout) clearTimeout(saveTimeout)
    
    saveTimeout = setTimeout(() => {
      router.patch(route('documents.update', props.documentId), {
        content: editor.getHTML()
      }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          saveStatus.value = 'saved'
          setTimeout(() => { if (saveStatus.value === 'saved') saveStatus.value = 'idle' }, 2000)
        }
      })
    }, 1000)
  },
})

// --- FUNGSI TOMBOL ---

// Formatting Text
const toggleBold = () => editor.value?.chain().focus().toggleBold().run()
const toggleItalic = () => editor.value?.chain().focus().toggleItalic().run()

// Heading (H1, H2, H3)
const setHeading = (level) => editor.value?.chain().focus().toggleHeading({ level }).run()

// Lists
const toggleBulletList = () => editor.value?.chain().focus().toggleBulletList().run()
const toggleOrderedList = () => editor.value?.chain().focus().toggleOrderedList().run()

// History (Undo/Redo)
const undo = () => editor.value?.chain().focus().undo().run()
const redo = () => editor.value?.chain().focus().redo().run()

// --- CEK STATUS AKTIF ---
const isBold = () => editor.value?.isActive('bold')
const isItalic = () => editor.value?.isActive('italic')
const isHeading = (level) => editor.value?.isActive('heading', { level })
const isBulletList = () => editor.value?.isActive('bulletList')
const isOrderedList = () => editor.value?.isActive('orderedList')

// --- REAL-TIME LISTENER ---
onMounted(() => {
  echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || '',
    wsHost: window.location.hostname,
    wsPort: 8080,
    forceTLS: false,
    enabledTransports: ['ws'],
  })

  echo.channel(`document.${props.documentId}`)
    .listen('.updated', (e) => {
      if (editor.value && e.content !== editor.value.getHTML()) {
        isRemoteUpdate = true
        editor.value.commands.setContent(e.content, false)
      }
      lastEditor.value = e.userName
      saveStatus.value = 'saved'
    })
})

onUnmounted(() => {
  if (echo) echo.leave(`document.${props.documentId}`)
})
</script>

<template>
  <div class="border rounded-lg bg-white shadow-sm flex flex-col h-[600px]">
    
    <!-- TOOLBAR -->
    <div class="border-b bg-gray-50 p-2 flex flex-wrap gap-1 items-center">
      
      <!-- Group: History -->
      <button @click="undo" class="p-2 rounded hover:bg-gray-200 text-gray-600" title="Undo (Ctrl+Z)">↩</button>
      <button @click="redo" class="p-2 rounded hover:bg-gray-200 text-gray-600" title="Redo (Ctrl+Y)">↪</button>
      
      <div class="w-px h-6 bg-gray-300 mx-2"></div>

      <!-- Group: Formatting -->
      <button @click="toggleBold" :class="{'bg-blue-500 text-white hover:bg-blue-600': isBold()}" class="p-2 rounded hover:bg-gray-200 font-bold w-8">B</button>
      <button @click="toggleItalic" :class="{'bg-blue-500 text-white hover:bg-blue-600': isItalic()}" class="p-2 rounded hover:bg-gray-200 italic w-8">I</button>

      <div class="w-px h-6 bg-gray-300 mx-2"></div>

      <!-- Group: Headings -->
      <button @click="setHeading(1)" :class="{'bg-blue-500 text-white hover:bg-blue-600': isHeading(1)}" class="p-2 rounded hover:bg-gray-200 font-bold w-8 text-lg">H1</button>
      <button @click="setHeading(2)" :class="{'bg-blue-500 text-white hover:bg-blue-600': isHeading(2)}" class="p-2 rounded hover:bg-gray-200 font-bold w-8 text-base">H2</button>
      <button @click="setHeading(3)" :class="{'bg-blue-500 text-white hover:bg-blue-600': isHeading(3)}" class="p-2 rounded hover:bg-gray-200 font-bold w-8 text-sm">H3</button>

      <div class="w-px h-6 bg-gray-300 mx-2"></div>

      <!-- Group: Lists -->
      <button @click="toggleBulletList" :class="{'bg-blue-500 text-white hover:bg-blue-600': isBulletList()}" class="p-2 rounded hover:bg-gray-200 w-8" title="Bullet List">•</button>
      <button @click="toggleOrderedList" :class="{'bg-blue-500 text-white hover:bg-blue-600': isOrderedList()}" class="p-2 rounded hover:bg-gray-200 w-8 font-mono" title="Numbered List">1.</button>
      
      <!-- Status Area -->
      <div class="ml-auto flex items-center gap-3 pr-2">
         <span v-if="saveStatus === 'saving'" class="text-xs text-gray-500 animate-pulse">Menyimpan...</span>
         <span v-else-if="saveStatus === 'saved'" class="text-xs text-green-600 font-medium">✅ Tersimpan</span>
         
         <span v-if="lastEditor" class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
           ✏️ oleh {{ lastEditor }}
         </span>
         
         <div class="flex items-center gap-1">
           <span class="relative flex h-2.5 w-2.5">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
           </span>
           <span class="text-xs text-gray-500">Online</span>
         </div>
      </div>
    </div>

    <!-- AREA EDITOR -->
    <div class="flex-1 overflow-y-auto p-6 bg-white">
      <EditorContent :editor="editor" class="prose max-w-none focus:outline-none min-h-full" />
    </div>

  </div>
</template>

<style>
/* Style Tiptap agar rapi */
.tiptap {
  outline: none;
  min-height: 100%;
  color: #1f2937; /* Text dark gray */
}
.tiptap p { margin-bottom: 0.75rem; line-height: 1.6; }

/* Style untuk Heading di editor */
.tiptap h1 { font-size: 2.25rem; font-weight: 800; margin-bottom: 0.5rem; line-height: 1.2; color: #111827; }
.tiptap h2 { font-size: 1.875rem; font-weight: 700; margin-bottom: 0.5rem; line-height: 1.3; color: #1f2937; }
.tiptap h3 { font-size: 1.5rem; font-weight: 600; margin-bottom: 0.5rem; line-height: 1.4; color: #374151; }

/* Style List */
.tiptap ul { list-style-type: disc; padding-left: 1.5rem; margin-bottom: 0.75rem; }
.tiptap ol { list-style-type: decimal; padding-left: 1.5rem; margin-bottom: 0.75rem; }
.tiptap li { margin-bottom: 0.25rem; }
</style>