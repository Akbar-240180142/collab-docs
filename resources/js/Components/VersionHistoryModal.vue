<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  documentId: String,
  currentContent: String
});

const emit = defineEmits(['close', 'restore']);

const versions = ref([]);
const loading = ref(false);
const selectedVersion = ref(null);
const previewContent = ref('');

const fetchVersions = async () => {
  loading.value = true;
  try {
    const res = await axios.get(`/api/documents/${props.documentId}/versions`);
    versions.value = res.data;
  } catch (e) { console.error('Fetch versions failed:', e); }
  finally { loading.value = false; }
};

const previewVersion = async (version) => {
  selectedVersion.value = version;
  try {
    const res = await axios.get(`/api/documents/${props.documentId}/versions/${version.id}`);
    previewContent.value = res.data.content;
  } catch (e) { console.error('Preview failed:', e); }
};

const restoreVersion = async (versionId) => {
  if (!confirm('Restore dokumen ke versi ini? Perubahan saat ini akan ditimpa.')) return;
  try {
    await axios.post(`/api/documents/${props.documentId}/versions/${versionId}/restore`);
    emit('restore');
    emit('close');
  } catch (e) {
    console.error('Restore failed:', e);
    alert('❌ Gagal restore: ' + (e.response?.data?.message || e.message));
  }
};

const saveCurrentVersion = async () => {
  try {
    // ✅ Simpan versi dari konten TERBARU yang di-pass parent
    await axios.post(`/api/documents/${props.documentId}/versions`, {
      content: props.currentContent || '',
      version_name: 'Auto-saved ' + new Date().toLocaleString('id-ID'),
      word_count: props.currentContent ? props.currentContent.replace(/<[^>]*>/g, '').split(/\s+/).filter(w => w).length : 0
    });
  } catch (e) { console.error('Save version failed:', e); }
  await fetchVersions();
};

onMounted(() => { saveCurrentVersion(); });
</script>

<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
      <div class="flex justify-between items-center p-4 border-b dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">📜 Riwayat Versi Dokumen</h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="flex flex-1 overflow-hidden">
        <div class="w-1/3 border-r dark:border-gray-700 overflow-y-auto p-4">
          <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Versi Tersimpan ({{ versions.length }})</h4>
          <div v-if="loading" class="text-center py-8 text-gray-500">Memuat riwayat...</div>
          <div v-else class="space-y-2">
            <button v-for="version in versions" :key="version.id" @click="previewVersion(version)"
              class="w-full text-left p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
              :class="selectedVersion?.id === version.id ? 'bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500' : ''">
              <div class="flex justify-between items-start">
                <div>
                  <p class="text-sm font-medium text-gray-900 dark:text-white">{{ version.version_name }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ version.created_at }} • {{ version.user_name }}</p>
                </div>
                <span class="text-xs bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded">{{ version.word_count }} kata</span>
              </div>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 line-clamp-2">{{ version.content_preview }}</p>
            </button>
            <div v-if="versions.length === 0" class="text-center py-4 text-gray-500 text-sm">Belum ada versi tersimpan</div>
          </div>
        </div>

        <div class="w-2/3 flex flex-col">
          <div class="p-4 border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Preview: {{ selectedVersion?.version_name || 'Pilih versi untuk preview' }}</h4>
          </div>
          <div class="flex-1 overflow-y-auto p-4">
            <div v-if="!selectedVersion" class="text-center py-12 text-gray-400"> Pilih versi dari daftar untuk melihat preview</div>
            <div v-else class="prose dark:prose-invert max-w-none">
              <div v-html="previewContent" class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg min-h-[300px]"></div>
            </div>
          </div>
          <div v-if="selectedVersion" class="p-4 border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 flex justify-end gap-3">
            <button @click="$emit('close')" class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">Batal</button>
            <button @click="restoreVersion(selectedVersion.id)" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
              Restore Versi Ini
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>