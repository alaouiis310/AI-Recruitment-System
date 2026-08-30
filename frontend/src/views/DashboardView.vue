<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center">
    <div class="text-center">
      <h1 class="text-4xl font-bold text-gray-800 mb-4">Dashboard</h1>
      <p class="text-gray-600">Bienvenue !</p>
      <button 
        @click="logout" 
        class="mt-6 px-6 py-3 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors"
      >
        Se déconnecter
      </button>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

const logout = () => {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  router.push('/')
}
</script>