<template>
  <div id="app">
    <router-view />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'

onMounted(() => {
  // Vérifier si l'utilisateur est connecté
  const token = localStorage.getItem('token')
  if (token) {
    // Ajouter le token aux headers axios par défaut
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
  }
})
</script>

<style>
@import './style.css';
</style>