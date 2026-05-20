<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import Echo from 'laravel-echo'

const props = defineProps({
  documentId: String,
  userId: String
})

const remoteCursors = ref({})
let echo = null
let channel = null

onMounted(() => {
  echo = new Echo({
    broadcaster: 'reverb',
    key: 'qdngafg3kgsf5rbhdu5v',
    wsHost: '127.0.0.1',
    wsPort: 8080,
    forceTLS: false,
    enabledTransports: ['ws', 'wss']
  })

  channel = echo.channel(`document.${props.documentId}`)
  
  channel.listen('.cursor-moved', (data) => {
    if (data.userId !== props.userId && data.mousePos) {
      remoteCursors.value[data.userId] = data
    }
  })
})

onUnmounted(() => {
  if (channel) echo.leaveChannel(`document.${props.documentId}`)
})
</script>

<template>
  <div class="absolute inset-0 pointer-events-none overflow-hidden z-50">
    <div 
      v-for="c in remoteCursors" 
      :key="c.userId"
      class="absolute"
      :style="{ top: c.mousePos.y + 'px', left: c.mousePos.x + 'px' }"
    >
      <div class="w-0.5 h-5" :style="{ backgroundColor: c.userColor || '#3B82F6' }"></div>
      <div class="absolute -top-6 left-0 px-2 py-1 text-[10px] text-white rounded font-medium"
           :style="{ backgroundColor: c.userColor || '#3B82F6' }">
        {{ c.userName }}
      </div>
    </div>
  </div>
</template>