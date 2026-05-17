<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  documentId: String,
  sharedUsers: Array
});

const emit = defineEmits(['close']);

const email = ref('');
const role = ref('viewer');
const users = ref([]);
const loading = ref(false);
const error = ref('');
const successMessage = ref('');

// Fetch shared users
const fetchSharedUsers = async () => {
  try {
    const response = await axios.get(`/api/documents/${props.documentId}/users`);
    users.value = response.data;
  } catch (err) {
    console.error('Failed to fetch shared users:', err);
  }
};

// Add user to document
const addUser = async () => {
  error.value = '';
  successMessage.value = '';
  loading.value = true;
  
  try {
    const response = await axios.post(`/api/documents/${props.documentId}/users`, {
      email: email.value,
      role: role.value
    });
    
    successMessage.value = 'User berhasil ditambahkan!';
    email.value = '';
    role.value = 'viewer';
    await fetchSharedUsers();
    
    setTimeout(() => {
      successMessage.value = '';
    }, 3000);
  } catch (err) {
    if (err.response?.status === 422) {
      error.value = 'User sudah memiliki akses ke dokumen ini';
    } else if (err.response?.status === 404) {
      error.value = 'Email tidak ditemukan';
    } else {
      error.value = 'Terjadi kesalahan';
    }
  } finally {
    loading.value = false;
  }
};

// Update user role
const updateUserRole = async (documentUserId, newRole) => {
  try {
    await axios.patch(`/api/documents/${props.documentId}/users/${documentUserId}`, {
      role: newRole
    });
    await fetchSharedUsers();
  } catch (err) {
    console.error('Failed to update role:', err);
  }
};

// Remove user access
const removeUser = async (documentUserId) => {
  if (!confirm('Hapus akses user ini?')) return;
  
  try {
    await axios.delete(`/api/documents/${props.documentId}/users/${documentUserId}`);
    await fetchSharedUsers();
  } catch (err) {
    console.error('Failed to remove user:', err);
  }
};

// Fetch on mount
onMounted(() => {
  fetchSharedUsers();
});
</script>

<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full max-h-[80vh] overflow-y-auto">
      
      <!-- Header -->
      <div class="flex justify-between items-center p-4 border-b dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Share Document
        </h3>
        <button 
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="p-4">
        
        <!-- Add User Form -->
        <div class="mb-6">
          <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            Tambah User
          </h4>
          
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Email
              </label>
              <input 
                v-model="email"
                type="email"
                placeholder="user@example.com"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Role
              </label>
              <select 
                v-model="role"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
              >
                <option value="viewer">Viewer (Baca saja)</option>
                <option value="editor">Editor (Bisa edit)</option>
              </select>
            </div>
            
            <button
              @click="addUser"
              :disabled="loading || !email"
              class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition"
            >
              {{ loading ? 'Menambahkan...' : 'Tambah User' }}
            </button>
          </div>
          
          <!-- Error/Success Messages -->
          <div v-if="error" class="mt-3 p-3 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-200 rounded-md text-sm">
            {{ error }}
          </div>
          <div v-if="successMessage" class="mt-3 p-3 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200 rounded-md text-sm">
            {{ successMessage }}
          </div>
        </div>

        <!-- Shared Users List -->
        <div>
          <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            User dengan Akses ({{ users.length }})
          </h4>
          
          <div class="space-y-2">
            <div 
              v-for="userDoc in users" 
              :key="userDoc.id"
              class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-md"
            >
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-bold">
                  {{ userDoc.user?.name?.charAt(0).toUpperCase() || 'U' }}
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ userDoc.user?.name || 'Unknown' }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ userDoc.user?.email || '' }}
                  </p>
                </div>
              </div>
              
              <div class="flex items-center gap-2">
                <select
                  :value="userDoc.role"
                  @change="updateUserRole(userDoc.id, $event.target.value)"
                  class="text-xs px-2 py-1 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-600 dark:text-white"
                >
                  <option value="viewer">Viewer</option>
                  <option value="editor">Editor</option>
                </select>
                
                <button
                  @click="removeUser(userDoc.id)"
                  class="text-red-500 hover:text-red-700 dark:hover:text-red-400"
                  title="Hapus akses"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>
            </div>
            
            <div v-if="users.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
              Belum ada user yang diajak berkolaborasi
            </div>
          </div>
        </div>
        
      </div>
    </div>
  </div>
</template>