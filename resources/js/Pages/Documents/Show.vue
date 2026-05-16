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

// Fetch shared users when modal opens
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
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          {{ document.title }}
        </h2>
        <div class="flex gap-3">
          <!-- Tombol Share -->
          <button 
            @click="openShareModal"
            class="text-sm bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"
          >
            🔗 Share
          </button>
          <Link :href="route('documents.index')" class="text-sm text-blue-500 hover:underline">
            ← Kembali
          </Link>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-1">
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

  <!-- Share Modal -->
  <ShareModal 
    v-if="showShareModal"
    :document-id="document.id.toString()"
    :shared-users="sharedUsers"
    @close="showShareModal = false"
  />
</template>