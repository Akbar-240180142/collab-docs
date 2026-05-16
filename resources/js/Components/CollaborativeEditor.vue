<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import { router } from '@inertiajs/vue3'
import { onMounted, ref } from 'vue'
import Echo from 'laravel-echo'

const props = defineProps({
  documentId: String,
  initialContent: String
})

// State
const saveStatus = ref('saved')
const lastEditor = ref('') // Ini buat ilangin warning di console
let saveTimeout = null
let isRemoteUpdate = false
let echo = null

// 1. Setup Editor
const editor = useEditor({
  extensions: [StarterKit],
  content: props.initialContent || '<p>Mulai mengetik...</p>',
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
        onSuccess: () => { saveStatus.value = 'saved' }
      })
    }, 1000)
  },
})

// 2. Fungsi Toolbar
const toggleBold = () => editor.value?.chain().focus().toggleBold().run()
const toggleItalic = () => editor.value?.chain().focus().toggleItalic().run()
const setHeading = (level) => editor.value?.chain().focus().toggleHeading({ level }).run()
const toggleBulletList = () => editor.value?.chain().focus().toggleBulletList().run()
const toggleOrderedList = () => editor.value?.chain().focus().toggleOrderedList().run()
const undo = () => editor.value?.chain().focus().undo().run()
const redo = () => editor.value?.chain().focus().redo().run()

// Fungsi Cek Aktif
const isBold = () => editor.value?.isActive('bold')
const isItalic = () => editor.value?.isActive('italic')
const isHeading = (level) => editor.value?.isActive('heading', { level })
const isBulletList = () => editor.value?.isActive('bulletList')
const isOrderedList = () => editor.value?.isActive('orderedList')

// 3. Setup WebSocket (PASTIKAN ADA DI DALAM onMounted)
onMounted(() => {
  console.log('🚀 onMounted berjalan!')
  
  echo = new Echo({
    broadcaster: 'reverb',
    key: 'qdngafg3kgsf5rbhdu5v',
    wsHost: '127.0.0.1',
    wsPort: 8080,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    cluster: 'mt1',
  })

  console.log('📡 Joining channel: document.' + props.documentId)
  
  echo.channel(`document.${props.documentId}`)
    .listen('.updated', (e) => {
      console.log(' EVENT DITERIMA!', e)
      
      if (editor.value && e.content) {
        isRemoteUpdate = true
        editor.value.commands.setContent(e.content, false)
        console.log('✅ Teks diupdate realtime!')
      }
    })
})
</script>

<template>
  <div class="border rounded-lg bg-white shadow-sm flex flex-col h-[600px]">
    
    <!-- TOOLBAR -->
    <div class="border-b bg-gray-50 p-2 flex flex-wrap gap-1 items-center">
      <button @click="undo" class="p-2 rounded hover:bg-gray-200">↩</button>
      <button @click="redo" class="p-2 rounded hover:bg-gray-200">↪</button>
      <div class="w-px h-6 bg-gray-300 mx-2"></div>
      
      <button @click="toggleBold" :class="{'bg-blue-500 text-white': isBold()}" class="p-2 rounded hover:bg-gray-200 font-bold">B</button>
      <button @click="toggleItalic" :class="{'bg-blue-500 text-white': isItalic()}" class="p-2 rounded hover:bg-gray-200 italic">I</button>
      <div class="w-px h-6 bg-gray-300 mx-2"></div>
      
      <button @click="setHeading(1)" :class="{'bg-blue-500 text-white': isHeading(1)}" class="p-2 rounded hover:bg-gray-200 font-bold">H1</button>
      <button @click="setHeading(2)" :class="{'bg-blue-500 text-white': isHeading(2)}" class="p-2 rounded hover:bg-gray-200 font-bold">H2</button>
      <button @click="setHeading(3)" :class="{'bg-blue-500 text-white': isHeading(3)}" class="p-2 rounded hover:bg-gray-200 font-bold">H3</button>
      <div class="w-px h-6 bg-gray-300 mx-2"></div>
      
      <button @click="toggleBulletList" :class="{'bg-blue-500 text-white': isBulletList()}" class="p-2 rounded hover:bg-gray-200">•</button>
      <button @click="toggleOrderedList" :class="{'bg-blue-500 text-white': isOrderedList()}" class="p-2 rounded hover:bg-gray-200">1.</button>
      
      <div class="ml-auto flex items-center gap-3 pr-2">
         <span v-if="saveStatus === 'saving'" class="text-xs text-gray-500 animate-pulse">Menyimpan...</span>
         <span v-else-if="saveStatus === 'saved'" class="text-xs text-green-600 font-medium">✅ Tersimpan</span>
         
         <div class="flex items-center gap-1">
           <span class="relative flex h-2.5 w-2.5">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
           </span>
           <span class="text-xs text-gray-500">Online</span>
         </div>
      </div>
    </div>

    <!-- EDITOR -->
    <div class="flex-1 overflow-y-auto p-6 bg-white">
      <EditorContent :editor="editor" class="prose max-w-none focus:outline-none min-h-full" />
    </div>
  </div>
</template>

<style>
.tiptap { outline: none; }
.tiptap h1 { font-size: 2.25rem; font-weight: 800; margin-bottom: 0.5rem; }
.tiptap h2 { font-size: 1.875rem; font-weight: 700; margin-bottom: 0.5rem; }
.tiptap h3 { font-size: 1.5rem; font-weight: 600; margin-bottom: 0.5rem; }
.tiptap ul { list-style-type: disc; padding-left: 1.5rem; }
.tiptap ol { list-style-type: decimal; padding-left: 1.5rem; }
</style>