<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CollaborativeEditor from '@/Components/CollaborativeEditor.vue';

defineProps({
  document: Object,
  userName: String,
  userColor: String
});
</script>

<template>
  <Head :title="document.title" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          {{ document.title }}
        </h2>
        <Link :href="route('documents.index')" class="text-sm text-blue-500 hover:underline">
          ← Kembali
        </Link>
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
</template>