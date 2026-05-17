<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CollaborativeEditor from '@/Components/CollaborativeEditor.vue';
import ShareModal from '@/Components/ShareModal.vue';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  document: Object,
  userName: String,
  userColor: String
});

const showShareModal = ref(false);
const sharedUsers = ref([]);
const isDark = ref(false); // State untuk icon

// Fungsi Toggle Dark Mode
const toggleDarkMode = () => {
  isDark.value = !isDark.value;
  if (isDark.value) {
    document.documentElement.classList.add('dark');
    localStorage.setItem('theme', 'dark');
  } else {
    document.documentElement.classList.remove('dark');
    localStorage.setItem('theme', 'light');
  }
};

// Cek tema saat halaman dibuka
onMounted(() => {
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme === 'dark') {
    isDark.value = true;
    document.documentElement.classList.add('dark');
  }
});

const openShareModal = async () => {
  showShareModal.value = true;
  try {
    const response = await axios.get(`/api/documents/${props.document.id}/users`);
    sharedUsers.value = response.data;
  } catch (e) {
    console.error('Failed to fetch shared users');
  }
};
</script>

<template>
  <Head :title="document.title" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex justify-between items-center">
        
        <!-- JUDUL DOKUMEN -->
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
          {{ document.title }}
        </h2>

        <!-- TOMBOL-TOMBOL (Rapi di kanan) -->
        <div class="flex items-center gap-3">
          
         <!-- ✅ TOMBOL DARK MODE (Sleek Icon) -->
<button 
  @click="toggleDarkMode"
  class="p-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-yellow-400 hover:bg-gray-300 dark:hover:bg-gray-600 transition-all"
  title="Toggle Dark Mode"
>
  <!-- Icon Bulan (Dark) -->
  <svg v-if="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
  <!-- Icon Matahari (Light) -->
  <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
</button>

          <!-- Tombol Export PDF -->
          <a 
            :href="route('documents.export.pdf', document.id)"
            class="hidden sm:flex px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition items-center gap-2 text-sm"
            target="_blank"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Export PDF
          </a>
          
          <!-- Tombol Share -->
          <button 
            @click="openShareModal"
            class="text-sm bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition"
          >
            🔗 Share
          </button>
          
          <!-- Link Kembali -->
          <Link :href="route('documents.index')" class="hidden sm:flex text-sm text-blue-500 hover:underline">
            ← Kembali
          </Link>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-1 transition-colors duration-300">
          <CollaborativeEditor 
            :document-id="document.id.toString()" 
            :initial-content="document.content"
            :user-name="userName" 
            :user-color="userColor" 
          />
        </div>
      </div>
    </div>
  </AuthenticatedLayout>

 <ShareModal 
    v-if="showShareModal"
    :document-id="document.id.toString()"
    :shared-users="sharedUsers"   <-- HAPUS BARIS INI
    @close="showShareModal = false"
/>
</template>