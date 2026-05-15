<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
  documents: Array
});
</script>

<template>
  <Head title="My Documents" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        My Documents
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
          <div class="mb-4">
            <Link :href="route('documents.create')" 
                  class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
              + Create New Document
            </Link>
          </div>

          <div v-if="documents.length === 0" class="text-gray-500">
            Belum ada dokumen. Buat dokumen pertamamu!
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="doc in documents" :key="doc.id" 
                 class="border rounded-lg p-4 hover:shadow-md transition cursor-pointer bg-gray-50 dark:bg-gray-700">
              <Link :href="route('documents.show', doc)" class="block">
                <h3 class="font-semibold text-lg mb-2">{{ doc.title }}</h3>
                <p class="text-sm text-gray-500">
                  Terakhir diedit: {{ doc.last_edited_at ? new Date(doc.last_edited_at).toLocaleString() : 'Baru' }}
                </p>
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>