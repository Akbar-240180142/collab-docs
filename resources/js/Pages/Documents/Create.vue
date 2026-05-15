<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const form = useForm({
  title: ''
});

const submit = () => {
  form.post(route('documents.store'));
};
</script>

<template>
  <Head title="Create Document" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Create New Document
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
          <form @submit.prevent="submit">
            <div class="mb-4">
              <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">
                Document Title
              </label>
              <input v-model="form.title" 
                     type="text" 
                     class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                     placeholder="Enter document title" />
              <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">
                {{ form.errors.title }}
              </p>
            </div>

            <button type="submit" 
                    :disabled="form.processing"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50">
              Create Document
            </button>
          </form>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>