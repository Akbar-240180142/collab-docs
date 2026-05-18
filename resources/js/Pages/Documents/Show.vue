<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CollaborativeEditor from '@/Components/CollaborativeEditor.vue';
import ShareModal from '@/Components/ShareModal.vue';
import VersionHistoryModal from '@/Components/VersionHistoryModal.vue';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({ document: Object, userName: String, userColor: String });

const editorRef = ref(null);
const currentContent = ref(props.document.content);
const showShareModal = ref(false);
const showVersionModal = ref(false);
const sharedUsers = ref([]);
const isDark = ref(false);

const openVersionHistory = async () => {
  if (editorRef.value?.forceSave) await editorRef.value.forceSave();
  showVersionModal.value = true;
};

// ✅ FIX DISINI: Gunakan window.location.reload()
const handleRestore = (data) => {
  window.location.reload(); 
};

const toggleDarkMode = () => {
  isDark.value = !isDark.value;
  document.documentElement.classList.toggle('dark', isDark.value);
  localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
};

onMounted(() => {
  if (localStorage.getItem('theme') === 'dark') {
    isDark.value = true;
    document.documentElement.classList.add('dark');
  }
});

const openShareModal = async () => {
  showShareModal.value = true;
  try {
    const res = await axios.get(`/api/documents/${props.document.id}/users`);
    sharedUsers.value = res.data;
  } catch (e) { console.error(e); }
};
</script>

<template>
  <Head :title="document.title" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white">{{ document.title }}</h2>
        <div class="flex items-center gap-3">
          <button @click="toggleDarkMode" class="p-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-yellow-400 hover:bg-gray-300 dark:hover:bg-gray-600 transition-all" title="Toggle Dark Mode">
            <svg v-if="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
          </button>
          <a :href="route('documents.export.pdf', document.id)" class="hidden sm:flex px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition items-center gap-2 text-sm" target="_blank">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Export PDF
          </a>
          <button @click="openShareModal" class="text-sm bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition">🔗 Share</button>
          <button @click="openVersionHistory" class="text-sm bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> History
          </button>
          <Link :href="route('documents.index')" class="hidden sm:flex text-sm text-blue-500 hover:underline">← Kembali</Link>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-1 transition-colors duration-300">
          <CollaborativeEditor 
            ref="editorRef"
            :document-id="document.id.toString()" 
            :initial-content="document.content"
            :user-name="userName" 
            :user-color="userColor"
            @content-change="(c) => currentContent = c" 
          />
        </div>
      </div>
    </div>
  </AuthenticatedLayout>

  <ShareModal v-if="showShareModal" :document-id="document.id.toString()" @close="showShareModal = false" />
  
  <VersionHistoryModal 
    v-if="showVersionModal"
    :document-id="document.id.toString()"
    :current-content="currentContent"
    @close="showVersionModal = false"
    @restore="handleRestore"
  />
</template>