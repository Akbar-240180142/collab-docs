<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import { ref, onMounted, onUnmounted } from 'vue'
import Echo from 'laravel-echo'
import LiveCursorsOverlay from './LiveCursorsOverlay.vue'

const props = defineProps({
  documentId: String,
  initialContent: String,
  userName: String,
  userColor: String
})

const emit = defineEmits(['content-change'])
const wrapperRef = ref(null)
const isSaving = ref(false)
const isOnline = ref(true)
let typingTimeout = null, isRemoteUpdate = false, isLocalUpdate = false
let channel = null, echo = null, saveTimeout = null, mouseTimeout = null
const isUserTyping = ref(false), typingUserName = ref('')

const editor = useEditor({
  extensions: [StarterKit.configure({ heading: { levels: [1, 2, 3] }, bulletList: true, orderedList: true, bold: true, italic: true, history: true })],
  content: props.initialContent,
  onCreate: () => { isOnline.value = true },
  onUpdate: ({ editor }) => {
    if (isRemoteUpdate) return
    emit('content-change', editor.getHTML())
    if (saveTimeout) clearTimeout(saveTimeout)
    isLocalUpdate = true
    saveTimeout = setTimeout(async () => {
      isSaving.value = true
      try {
        await fetch(`/documents/${props.documentId}`, {
          method: 'PATCH',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
          body: JSON.stringify({ content: editor.getHTML() })
        })
      } catch (e) { console.error(e) }
      finally { isSaving.value = false; setTimeout(() => { isLocalUpdate = false }, 100) }
    }, 1000)
  }
})

// ✅ MOUSE TRACKING
const handleMouseMove = (e) => {
  if (!wrapperRef.value) return
  // Hapus setTimeout, langsung kirim tapi batasi dengan flag sederhana
  if (handleMouseMove.sending) return
  handleMouseMove.sending = true
  
  requestAnimationFrame(async () => {
    const rect = wrapperRef.value.getBoundingClientRect()
    try {
      await fetch(`/documents/${props.documentId}/cursor`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
        body: JSON.stringify({ 
          mousePos: { x: e.clientX - rect.left, y: e.clientY - rect.top }, 
          userColor: props.userColor || '#3B82F6' 
        })
      })
    } catch (err) {}
    finally { handleMouseMove.sending = false }
  })
}

const handleBold = () => editor.value?.chain().focus().toggleBold().run()
const handleItalic = () => editor.value?.chain().focus().toggleItalic().run()
const handleH1 = () => editor.value?.chain().focus().toggleHeading({ level: 1 }).run()
const handleH2 = () => editor.value?.chain().focus().toggleHeading({ level: 2 }).run()
const handleH3 = () => editor.value?.chain().focus().toggleHeading({ level: 3 }).run()
const handleBullet = () => editor.value?.chain().focus().toggleBulletList().run()
const handleOrdered = () => editor.value?.chain().focus().toggleOrderedList().run()
const handleUndo = () => editor.value?.chain().focus().undo().run()
const handleRedo = () => editor.value?.chain().focus().redo().run()

const sendTypingEvent = async () => {
  if (isRemoteUpdate || isLocalUpdate) return
  try {
    await fetch(`/documents/${props.documentId}/typing`, {
      method: 'PATCH', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
      body: JSON.stringify({ isTyping: true })
    })
  } catch (e) {}
}

const forceSave = () => new Promise((resolve) => {
  if (saveTimeout) clearTimeout(saveTimeout)
  isLocalUpdate = true; isSaving.value = true
  fetch(`/documents/${props.documentId}`, {
    method: 'PATCH', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
    body: JSON.stringify({ content: editor.value.getHTML() })
  }).then(() => { isSaving.value = false; isLocalUpdate = false; resolve(true) })
    .catch(() => { isSaving.value = false; isLocalUpdate = false; resolve(false) })
})

onMounted(() => {
  echo = new Echo({ broadcaster: 'reverb', key: 'qdngafg3kgsf5rbhdu5v', wsHost: '127.0.0.1', wsPort: 8080, forceTLS: false, enabledTransports: ['ws', 'wss'] })
  channel = echo.channel(`document.${props.documentId}`)
  channel.listen('.document-updated', (data) => {
    if (!editor.value || isLocalUpdate) return
    if (editor.value.getHTML() !== data.content) {
      isRemoteUpdate = true
      editor.value.commands.setContent(data.content, false)
      setTimeout(() => { isRemoteUpdate = false }, 10)
    }
  })
  channel.listen('.user-typing', (data) => {
    if (data.userName !== props.userName) {
      isUserTyping.value = true; typingUserName.value = data.userName
      if (typingTimeout) clearTimeout(typingTimeout)
      typingTimeout = setTimeout(() => { isUserTyping.value = false }, 1500)
    }
  })
})

onUnmounted(() => {
  if (channel) echo.leaveChannel(`document.${props.documentId}`)
  if (editor.value) editor.value.destroy()
  if (typingTimeout) clearTimeout(typingTimeout)
  if (mouseTimeout) clearTimeout(mouseTimeout)
})

defineExpose({ forceSave, getContent: () => editor.value?.getHTML() || '' })
</script>

<template>
  <div class="border rounded-lg overflow-hidden bg-white dark:bg-gray-800">
    <div class="border-b bg-gray-50 dark:bg-gray-700 p-2 flex gap-1 flex-wrap">
      <button @click="handleUndo" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">↶</button>
      <button @click="handleRedo" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">↷</button>
      <div class="w-px h-6 bg-gray-300 mx-1"></div>
      <button @click="handleBold" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded font-bold">B</button>
      <button @click="handleItalic" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded italic">I</button>
      <div class="w-px h-6 bg-gray-300 mx-1"></div>
      <button @click="handleH1" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">H1</button>
      <button @click="handleH2" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">H2</button>
      <button @click="handleH3" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">H3</button>
      <div class="w-px h-6 bg-gray-300 mx-1"></div>
      <button @click="handleBullet" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">•</button>
      <button @click="handleOrdered" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">1.</button>
    </div>
    <div class="px-3 py-1 bg-gray-50 dark:bg-gray-700 border-b text-xs flex justify-between">
      <span><span :class="isSaving ? 'text-yellow-500' : 'text-green-500'">●</span> {{ isSaving ? 'Menyimpan...' : 'Tersimpan' }}</span>
      <span v-if="isOnline" class="text-green-500">● Online</span>
    </div>
    <div v-if="isUserTyping" class="px-3 py-1 bg-blue-50 text-xs text-blue-600 animate-pulse">
      {{ typingUserName }} sedang mengetik...
    </div>
    <div ref="wrapperRef" class="relative" @mousemove="handleMouseMove" style="min-height: 400px;">
      <EditorContent :editor="editor" class="p-4 min-h-[400px] prose focus:outline-none" />
      <LiveCursorsOverlay :document-id="documentId" :user-id="userName" />
    </div>
  </div>
</template>

<style scoped>
.ProseMirror { min-height: 400px; outline: none; }
.animate-pulse { animation: pulse 2s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
</style>