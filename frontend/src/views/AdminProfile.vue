<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Mon profil" 
    page-subtitle="Gérez vos informations personnelles"
  >
    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
    </div>

    <!-- Erreur -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-2xl p-6 text-center">
      <p class="text-4xl mb-2">⚠️</p>
      <p class="text-red-700 font-medium">{{ error }}</p>
      <button @click="fetchProfile" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700">
        Réessayer
      </button>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Photo de profil — utilise "profile" (affichage officiel) -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 text-center">
        <div class="relative inline-block">
          <img 
            :src="avatarUrl" 
            alt="Avatar" 
            class="w-28 h-28 rounded-full border-4 border-blue-100 object-cover mx-auto"
          >
        </div>
        <h3 class="font-semibold text-gray-800 mt-4">
          {{ profile.prenom || '—' }} {{ profile.nom || '' }}
        </h3>
        <p class="text-sm text-blue-600 font-medium">Administrateur</p>
        <div class="mt-4 p-3 bg-gray-50 rounded-xl text-sm text-gray-600">
          <p>📧 {{ profile.email || 'Non renseigné' }}</p>
          <p v-if="profile.telephone" class="mt-1">📱 {{ profile.telephone }}</p>
        </div>
      </div>

      <!-- Informations — utilise "form" (brouillon modifiable) -->
      <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <form @submit.prevent="saveProfile" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Prénom</label>
              <input 
                type="text" 
                v-model="form.prenom"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Nom</label>
              <input 
                type="text" 
                v-model="form.nom"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
              >
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
            <input 
              type="email" 
              v-model="form.email"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Téléphone</label>
            <input 
              type="tel" 
              v-model="form.telephone"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
            >
          </div>

          <div v-if="successMessage" class="p-3 bg-green-50 border border-green-200 rounded-xl">
            <p class="text-sm text-green-700">✅ {{ successMessage }}</p>
          </div>

          <button 
            type="submit" 
            :disabled="saving"
            class="w-full py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-semibold rounded-xl transition-all shadow-sm hover:shadow-lg hover:shadow-blue-200"
          >
            {{ saving ? 'Enregistrement...' : 'Enregistrer les modifications' }}
          </button>
        </form>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()

const userName = computed(() => {
  const user = authStore.user
  return user?.prenom ? `${user.prenom} ${user.nom || ''}`.trim() : 'Admin'
})

const avatarUrl = computed(() => {
  const name = `${profile.value.prenom || ''}+${profile.value.nom || ''}`.trim() || 'Admin'
  return `https://ui-avatars.com/api/?name=${name}&background=2563eb&color=fff&size=120`
})

const loading = ref(true)
const saving = ref(false)
const error = ref('')
const successMessage = ref('')

// 🔒 Profil RÉEL — affiché en haut. Modifié seulement après clic sur Enregistrer.
const profile = ref({
  prenom: '',
  nom: '',
  email: '',
  telephone: '',
})

// ✏️ Formulaire BROUILLON — ce que l'utilisateur tape dans les champs.
// Ne modifie PAS l'affichage tant qu'on n'a pas cliqué sur Enregistrer.
const form = ref({
  prenom: '',
  nom: '',
  email: '',
  telephone: '',
})

const fetchProfile = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/auth/moi')
    // L'API renvoie l'utilisateur sous la clé « utilisateur ».
    const data = response.data.utilisateur || {}

    // Remplir le profil RÉEL (affiché en haut)
    profile.value = {
      prenom: data.prenom || '',
      nom: data.nom || '',
      email: data.email || '',
      telephone: data.telephone || '',
    }

    // Remplir le FORMULAIRE avec les mêmes infos
    // → l'utilisateur voit ses infos actuelles dans les champs
    form.value = { ...profile.value }

  } catch (err) {
    console.warn('API /auth/moi indisponible :', err)
    // Les champs restent vides, pas d'erreur bloquante
  } finally {
    loading.value = false
  }
}

const saveProfile = async () => {
  saving.value = true
  successMessage.value = ''
  error.value = ''

  try {
    // 1. Envoyer les données du FORMULAIRE (brouillon) au backend
    await api.patch('/auth/profil', form.value)

    // 2.  SEULEMENT APRÈS succès → on applique les changements à l'affichage
    profile.value = { ...form.value }

    // 3. Mettre à jour le store global (sidebar, etc.)
    if (authStore.user) {
      authStore.user.prenom = form.value.prenom
      authStore.user.nom = form.value.nom
      authStore.user.email = form.value.email
      authStore.user.telephone = form.value.telephone
      localStorage.setItem('user', JSON.stringify(authStore.user))
    }

    successMessage.value = 'Profil mis à jour avec succès !'
    setTimeout(() => { successMessage.value = '' }, 3000)
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors de l\'enregistrement.'
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchProfile()
})
</script>