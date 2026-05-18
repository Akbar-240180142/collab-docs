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

const emit = defineEmits(['content-change'])

const isSaving = ref(false)
const isOnline = ref(true)
let typingTimeout = null
const isUserTyping = ref(false)
const typingUserName = ref('')

let isRemoteUpdate = false
let isLocalUpdate = false
let channel = null
let echo = null
let saveTimeout = null

const editor = useEditor({
  extensions: [
    StarterKit.configure({
      heading: { levels: [1, 2, 3] },
      bulletList: true,
      orderedList: true,
      bold: true,
      italic: true,
      history: true
    })
  ],
  content: props.initialContent,
  onCreate: () => { 
    isOnline.value = true
    console.log('✅ Editor created!')
  },
  onUpdate: ({ editor }) => {
    if (isRemoteUpdate) return
    const content = editor.getHTML()
    emit('content-change', content)
    sendTypingEvent()
    
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
      } catch (e) { console.error('Save failed:', e) }
      finally {
        isSaving.value = false
        setTimeout(() => { isLocalUpdate = false }, 100)
      }
    }, 1000)
  }
})

// ✅ HANDLER FUNCTIONS DENGAN CONSOLE.LOG
const handleBold = () => {
  console.log('🔵 Bold clicked')
  if (!editor.value) {
    console.error('❌ Editor not ready!')
    return
  }
  const result = editor.value.chain().focus().toggleBold().run()
  console.log('✅ Bold result:', result)
}

const handleItalic = () => {
  console.log('🔵 Italic clicked')
  if (!editor.value) {
    console.error('❌ Editor not ready!')
    return
  }
  const result = editor.value.chain().focus().toggleItalic().run()
  console.log('✅ Italic result:', result)
}

const handleH1 = () => {
  console.log('🔵 H1 clicked')
  if (!editor.value) return
  editor.value.chain().focus().toggleHeading({ level: 1 }).run()
}

const handleH2 = () => {
  console.log('🔵 H2 clicked')
  if (!editor.value) return
  editor.value.chain().focus().toggleHeading({ level: 2 }).run()
}

const handleH3 = () => {
  console.log('🔵 H3 clicked')
  if (!editor.value) return
  editor.value.chain().focus().toggleHeading({ level: 3 }).run()
}

const handleBullet = () => {
  console.log('🔵 Bullet clicked')
  if (!editor.value) return
  editor.value.chain().focus().toggleBulletList().run()
}

const handleOrdered = () => {
  console.log('🔵 Ordered clicked')
  if (!editor.value) return
  editor.value.chain().focus().toggleOrderedList().run()
}

const handleUndo = () => {
  if (!editor.value) return
  editor.value.chain().focus().undo().run()
}

const handleRedo = () => {
  if (!editor.value) return
  editor.value.chain().focus().redo().run()
}

const sendTypingEvent = async () => {
  if (isRemoteUpdate || isLocalUpdate) return
  try {
    await fetch(`/documents/${props.documentId}/typing`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({ isTyping: true })
    })
  } catch (e) { console.error('Typing event failed:', e) }
}

const forceSave = () => {
  return new Promise((resolve) => {
    if (saveTimeout) clearTimeout(saveTimeout)
    isLocalUpdate = true
    isSaving.value = true
    const content = editor.value.getHTML()
    fetch(`/documents/${props.documentId}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ content })
    }).then(() => {
      isSaving.value = false
      isLocalUpdate = false
      resolve(true)
    }).catch(() => {
      isSaving.value = false
      isLocalUpdate = false
      resolve(false)
    })
  })
}

const getContent = () => editor.value?.getHTML() || ''

onMounted(() => {
  echo = new Echo({
    broadcaster: 'reverb', key: 'qdngafg3kgsf5rbhdu5v',
    wsHost: '127.0.0.1', wsPort: 8080, forceTLS: false, enabledTransports: ['ws', 'wss']
  })
  channel = echo.channel(`document.${props.documentId}`)
  
  channel.listen('.document-updated', (data) => {
    if (!editor.value || isLocalUpdate) return
    if (editor.value.getHTML() !== data.content) {
      isRemoteUpdate = true
      const { from, to } = editor.value.state.selection
      editor.value.commands.setContent(data.content, false)
      setTimeout(() => {
        try {
          const max = editor.value.state.doc.content.size
          editor.value.commands.setTextSelection({ from: Math.min(from, max), to: Math.min(to, max) })
        } catch(e){}
        isRemoteUpdate = false
      }, 10)
    }
  })
  
  channel.listen('.user-typing', (data) => {
    if (data.userName !== props.userName) {
      isUserTyping.value = true
      typingUserName.value = data.userName
      if (typingTimeout) clearTimeout(typingTimeout)
      typingTimeout = setTimeout(() => { isUserTyping.value = false; typingUserName.value = '' }, 1500)
    }
  })
})

onUnmounted(() => {
  if (channel) echo.leaveChannel(`document.${props.documentId}`)
  if (editor.value) editor.value.destroy()
  if (typingTimeout) clearTimeout(typingTimeout)
})

defineExpose({ forceSave, getContent })
</script>

<template>
  <div class="border rounded-lg overflow-hidden bg-white dark:bg-gray-800 transition-colors">
    <!-- Toolbar -->
    <div class="border-b bg-gray-50 dark:bg-gray-700 p-2 flex gap-1 flex-wrap items-center">
      <button @click="handleUndo" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded" title="Undo">↶</button>
      <button @click="handleRedo" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded" title="Redo">↷</button>
      <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
      
      <button 
        @click="handleBold" 
        class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded font-bold transition"
        :class="editor?.isActive('bold') ? 'bg-blue-500 text-white' : ''"
        title="Bold">B</button>
      
      <button 
        @click="handleItalic" 
        class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded italic transition"
        :class="editor?.isActive('italic') ? 'bg-blue-500 text-white' : ''"
        title="Italic">I</button>
      
      <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
      
      <button 
        @click="handleH1" 
        class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition"
        :class="editor?.isActive('heading', { level: 1 }) ? 'bg-blue-500 text-white' : ''"
        title="H1">H1</button>
      
      <button 
        @click="handleH2" 
        class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition"
        :class="editor?.isActive('heading', { level: 2 }) ? 'bg-blue-500 text-white' : ''"
        title="H2">H2</button>
      
      <button 
        @click="handleH3" 
        class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition"
        :class="editor?.isActive('heading', { level: 3 }) ? 'bg-blue-500 text-white' : ''"
        title="H3">H3</button>
      
      <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
      
      <button 
        @click="handleBullet" 
        class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition"
        :class="editor?.isActive('bulletList') ? 'bg-blue-500 text-white' : ''"
        title="Bullet">•</button>
      
      <button 
        @click="handleOrdered" 
        class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition"
        :class="editor?.isActive('orderedList') ? 'bg-blue-500 text-white' : ''"
        title="Numbered">1.</button>
    </div>

    <!-- Status -->
    <div class="px-3 py-1 bg-gray-50 dark:bg-gray-700 border-b text-xs text-gray-500 flex justify-between items-center">
      <span><span :class="isSaving ? 'text-yellow-500' : 'text-green-500'">●</span> {{ isSaving ? 'Menyimpan...' : 'Tersimpan' }}</span>
      <span v-if="isOnline" class="text-green-500">● Online</span>
    </div>

    <!-- Typing Indicator -->
    <div v-if="isUserTyping" class="px-3 py-1 bg-blue-50 dark:bg-blue-900/30 border-b text-xs text-blue-600 dark:text-blue-400 animate-pulse flex items-center gap-1">
      <span class="flex gap-0.5">
        <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
        <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
        <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
      </span>
      <strong>{{ typingUserName }}</strong> sedang mengetik...
    </div>

    <!-- Editor -->
    <EditorContent :editor="editor" class="p-4 min-h-[400px] prose dark:prose-invert max-w-none focus:outline-none" />
  </div>
</template>

<style scoped>
.ProseMirror { min-height: 400px; outline: none; }
.animate-bounce { animation: bounce 1s infinite; }
@keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
</style>