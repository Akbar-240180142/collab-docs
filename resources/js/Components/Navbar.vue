<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import ApplicationLogo from './ApplicationLogo.vue'

const page = usePage()
const authUser = page.props.auth?.user

// Fungsi untuk cek apakah link sedang aktif
const isActive = (routeName) => {
  return page.url.startsWith(route(routeName))
}
</script>

<template>
  <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <!-- Logo & Menu Kiri -->
        <div class="flex items-center">
          <!-- Logo -->
          <div class="flex-shrink-0 flex items-center gap-3">
            <ApplicationLogo class="block h-9 w-auto" />
            <span class="text-xl font-bold text-gray-800 dark:text-white">Collab Docs</span>
          </div>

          <!-- Menu Navigasi -->
          <div class="hidden sm:ml-8 sm:flex sm:space-x-8">
            <Link
              :href="route('dashboard')"
              :class="[
                isActive('dashboard')
                  ? 'border-indigo-500 text-gray-900 dark:text-white'
                  : 'border-transparent text-gray-500 dark:text-gray-300 hover:border-gray-300 hover:text-gray-700 dark:hover:text-white',
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors'
              ]"
            >
              Dashboard
            </Link>

            <Link
              :href="route('documents.index')"
              :class="[
                isActive('documents.index')
                  ? 'border-indigo-500 text-gray-900 dark:text-white'
                  : 'border-transparent text-gray-500 dark:text-gray-300 hover:border-gray-300 hover:text-gray-700 dark:hover:text-white',
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors'
              ]"
            >
              My Documents
            </Link>
          </div>
        </div>

        <!-- Menu Kanan (User & Dark Mode) -->
        <div class="flex items-center gap-4">
          <!-- Dark Mode Toggle -->
          <button
            @click="toggleDarkMode()"
            class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            title="Toggle Dark Mode"
          >
            <!-- Sun Icon -->
            <svg
              v-if="isDark"
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
              />
            </svg>
            <!-- Moon Icon -->
            <svg
              v-else
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
              />
            </svg>
          </button>

          <!-- User Dropdown -->
          <div class="relative ml-3">
            <div class="flex items-center gap-2">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                {{ authUser?.name }}
              </span>
              <img
                class="h-8 w-8 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600"
                :src="authUser?.avatar || 'https://ui-avatars.com/api/?name=' + authUser?.name"
                alt="User Avatar"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script>
export default {
  data() {
    return {
      isDark: false
    }
  },
  mounted() {
    // Cek local storage saat component mount
    const savedTheme = localStorage.getItem('theme')
    this.isDark = savedTheme === 'dark'
    if (this.isDark) {
      document.documentElement.classList.add('dark')
    }
  },
  methods: {
    toggleDarkMode() {
      this.isDark = !this.isDark
      if (this.isDark) {
        document.documentElement.classList.add('dark')
        localStorage.setItem('theme', 'dark')
      } else {
        document.documentElement.classList.remove('dark')
        localStorage.setItem('theme', 'light')
      }
    }
  }
}
</script>