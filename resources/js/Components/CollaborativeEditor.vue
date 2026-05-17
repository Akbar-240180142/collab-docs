<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import { ref, onMounted, onUnmounted } from 'vue'
import Echo from 'laravel-echo'

const props = defineProps({
  documentId: String,
  initialContent: String,
  userName: String,
  userColor: String
})

const isSaving = ref(false)
const isOnline = ref(true)

// Flags untuk mencegah loop
let isRemoteUpdate = false
let isLocalUpdate = false
let channel = null
let echo = null
let saveTimeout = null

// Setup Editor
const editor = useEditor({
  extensions: [StarterKit],
  content: props.initialContent,
  
  // Saat editor dibuat
  onCreate: () => {
    isOnline.value = true
  },

  // Saat user mengetik
  onUpdate: ({ editor }) => {
    // JANGAN SAVE kalau ini update dari orang lain
    if (isRemoteUpdate) return
    
    const content = editor.getHTML()
    
    // Kirim ke server dengan DEBOUNCE (tunggu 1 detik setelah berhenti ngetik)
    if (saveTimeout) clearTimeout(saveTimeout)
    
    isLocalUpdate = true
    saveTimeout = setTimeout(async () => {
      isSaving.value = true
      try {
        await fetch(`/documents/${props.documentId}`, {
          method: 'PATCH',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
          },
          body: JSON.stringify({ content })
        })
      } catch (error) {
        console.error('Gagal simpan:', error)
      } finally {
        isSaving.value = false
        // Beri jeda sebelum unlock local update
        setTimeout(() => { isLocalUpdate = false }, 100)
      }
    }, 1000) // 1000ms = 1 detik delay
  }
})

// Setup WebSocket
onMounted(() => {
  echo = new Echo({
    broadcaster: 'reverb',
    key: 'qdngafg3kgsf5rbhdu5v',
    wsHost: '127.0.0.1',
    wsPort: 8080,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
  })

  channel = echo.channel(`document.${props.documentId}`)
  
  // Dengerin update dari orang lain
  channel.listen('.document-updated', (data) => {
    if (!editor.value || isLocalUpdate) return

    const currentContent = editor.value.getHTML()
    
    // Cek apakah konten beda? Kalau sama, JANGAN lakukan apa-apa (mencegah cursor jump)
    if (currentContent !== data.content) {
      
      // 1. Simpan posisi cursor User 2 sekarang
      const { from, to } = editor.value.state.selection
      
      // 2. Tandai ini update remote
      isRemoteUpdate = true
      
      // 3. Update konten (false = jangan trigger onUpdate)
      editor.value.commands.setContent(data.content, false)
      
      // 4. Kembalikan cursor ke posisi semula
      setTimeout(() => {
        try {
          // Pastikan posisi cursor masih valid setelah update
          const safeFrom = Math.min(from, data.content.length)
          const safeTo = Math.min(to, data.content.length)
          
          editor.value.commands.setTextSelection({
            from: safeFrom,
            to: safeTo
          })
        } catch (e) {
          // Abaikan error jika cursor tidak valid
        }
        
        // 5. Unlock remote update
        isRemoteUpdate = false
      }, 10)
    }
  })
})

// Cleanup saat leave page
onUnmounted(() => {
  if (channel) echo.leaveChannel(`document.${props.documentId}`)
  if (editor.value) editor.value.destroy()
})
</script>

<template>
  <div class="border rounded-lg overflow-hidden bg-white dark:bg-gray-800">
    <!-- Toolbar -->
    <div class="border-b bg-gray-50 dark:bg-gray-700 p-2 flex gap-2">
      <button @click="editor.chain().focus().toggleBold().run()" class="p-1 hover:bg-gray-200 dark:hover:bg-gray-600 rounded font-bold">B</button>
      <button @click="editor.chain().focus().toggleItalic().run()" class="p-1 hover:bg-gray-200 dark:hover:bg-gray-600 rounded italic">I</button>
      <button @click="editor.chain().focus().toggleHeading({ level: 1 }).run()" class="p-1 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">H1</button>
    </div>

    <!-- Status -->
    <div class="px-3 py-1 bg-gray-50 dark:bg-gray-700 border-b text-xs text-gray-500">
      <span :class="isSaving ? 'text-yellow-500' : 'text-green-500'">●</span> 
      {{ isSaving ? 'Menyimpan...' : 'Tersimpan' }}
    </div>

    <!-- Editor Area -->
    <EditorContent :editor="editor" class="p-4 min-h-[300px] prose dark:prose-invert max-w-none focus:outline-none" />
  </div>
</template>