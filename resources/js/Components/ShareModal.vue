    <script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  documentId: String,
  sharedUsers: Array
});

const emit = defineEmits(['close']);

const form = useForm({
  email: '',
  role: 'editor'
});

const shareDocument = () => {
  form.post(route('documents.share', props.documentId), {
    onSuccess: () => {
      form.reset();
      emit('close');
    }
  });
};

const revokeAccess = (userId) => {
  if (confirm('Revoke access for this user?')) {
    axios.delete(route('documents.unshare', [props.documentId, userId]))
      .then(() => window.location.reload());
  }
};
</script>

<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click="emit('close')">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4" @click.stop>
      
      <h3 class="text-lg font-semibold mb-4">Share Document</h3>
      
      <!-- Form Invite -->
      <form @submit.prevent="shareDocument" class="space-y-4 mb-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email User</label>
          <input 
            v-model="form.email" 
            type="email" 
            class="w-full border rounded px-3 py-2"
            placeholder="user@example.com"
            required
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
          <select v-model="form.role" class="w-full border rounded px-3 py-2">
            <option value="editor">Editor (can edit)</option>
            <option value="viewer">Viewer (read only)</option>
          </select>
        </div>
        
        <button 
          type="submit" 
          :disabled="form.processing"
          class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600 disabled:opacity-50"
        >
          {{ form.processing ? 'Sharing...' : 'Share Document' }}
        </button>
      </form>
      
      <!-- List Shared Users -->
      <div v-if="sharedUsers?.length">
        <h4 class="text-sm font-medium text-gray-700 mb-2">People with access:</h4>
        <div class="space-y-2">
          <div v-for="user in sharedUsers" :key="user.id" class="flex items-center justify-between p-2 bg-gray-50 rounded">
            <div>
              <span class="font-medium">{{ user.name }}</span>
              <span class="text-xs text-gray-500 ml-2">({{ user.pivot.role }})</span>
            </div>
            <button 
              @click="revokeAccess(user.id)"
              class="text-red-500 text-sm hover:underline"
            >
              Remove
            </button>
          </div>
        </div>
      </div>
      
      <button @click="emit('close')" class="mt-4 w-full text-gray-500 hover:text-gray-700">
        Close
      </button>
    </div>
  </div>
</template>