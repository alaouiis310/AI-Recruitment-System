<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Recruteur" 
    page-title="Mon profil" 
    page-subtitle="Gérez vos informations personnelles et professionnelles"
  >
    <template #menu>
      <SidebarItem to="/recruiter" icon="🏠" label="Accueil" />
      <SidebarItem to="/recruiter/jobs" icon="💼" label="Mes offres" :badge="myJobsCount" />
      <SidebarItem to="/recruiter/candidates" icon="🧑‍💻" label="Candidats" :badge="candidatesCount" />
      <SidebarItem to="/recruiter/search" icon="🔍" label="Rechercher" />
      <SidebarItem to="/recruiter/ai-helper" icon="🤖" label="AI Helper" />
      <SidebarItem to="/recruiter/messages" icon="💬" label="Messages" :badge="messagesCount" />
      <div class="mt-4 border-t border-gray-100 pt-4">
        <SidebarItem to="/recruiter/profile" icon="👤" label="Mon profil" />
      </div>
    </template>

    <template #header-actions>
      <button 
        @click="saveProfile"
        :disabled="saving"
        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md"
      >
        {{ saving ? 'Enregistrement...' : '💾 Enregistrer' }}
      </button>
    </template>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="text-center">
        <div class="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p class="text-gray-500 mt-4 text-sm">Chargement du profil...</p>
      </div>
    </div>

    <!-- Erreur -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-2xl p-6 text-center">
      <p class="text-4xl mb-2">⚠️</p>
      <p class="text-red-700 font-medium">{{ error }}</p>
      <button @click="fetchProfile" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700">
        Réessayer
      </button>
    </div>

    <!-- Contenu -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Photo de profil -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 text-center">
        <div class="relative inline-block">
          <img 
            :src="avatarUrl" 
            alt="Avatar" 
            class="w-28 h-28 rounded-full border-4 border-blue-100 object-cover mx-auto"
          >
          <button class="absolute bottom-0 right-0 bg-blue-600 text-white p-2 rounded-full shadow-lg hover:bg-blue-700 transition-colors text-sm">
            📷
          </button>
        </div>
        <h3 class="font-semibold text-gray-800 mt-4">{{ profile.prenom }} {{ profile.nom }}</h3>
        <p class="text-sm text-blue-600 font-medium">Recruteur</p>
        <p v-if="profile.entreprise" class="text-sm text-gray-500 mt-1">{{ profile.entreprise }}</p>

        <div class="mt-4 p-3 bg-gray-50 rounded-xl text-sm text-gray-600">
          <p>📧 {{ profile.email }}</p>
          <p v-if="profile.telephone" class="mt-1">📱 {{ profile.telephone }}</p>
        </div>
      </div>

      <!-- Informations -->
      <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <form @submit.prevent="saveProfile" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Prénom</label>
              <input 
                type="text" 
                v-model="profile.prenom"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Nom</label>
              <input 
                type="text" 
                v-model="profile.nom"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
              >
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Email professionnel</label>
            <input 
              type="email" 
              v-model="profile.email"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Téléphone</label>
            <input 
              type="tel" 
              v-model="profile.telephone"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Poste</label>
            <input 
              type="text" 
              v-model="profile.poste"
              placeholder="Responsable Recrutement" 
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
            >
          </div>

          <!-- Message succès -->
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
import SidebarItem from '../components/SidebarItem.vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()

const userName = computed(() => {
  const user = authStore.user
  return user?.prenom ? `${user.prenom} ${user.nom || ''}`.trim() : 'Recruteur'
})

const avatarUrl = computed(() => {
  const name = `${profile.value.prenom || ''}+${profile.value.nom || ''}`.trim() || 'Recruteur'
  return `https://ui-avatars.com/api/?name=${name}&background=2563eb&color=fff&size=120`
})

const loading = ref(true)
const saving = ref(false)
const error = ref('')
const successMessage = ref('')

const myJobsCount = ref(0)
const candidatesCount = ref(0)
const messagesCount = ref(0)

const profile = ref({
  prenom: '',
  nom: '',
  email: '',
  telephone: '',
  poste: '',
  entreprise: '',
})

const fetchProfile = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/auth/moi')
    const data = response.data.user || response.data

    profile.value = {
      prenom: data.prenom || '',
      nom: data.nom || '',
      email: data.email || '',
      telephone: data.recruteur?.telephone || '',
      poste: data.recruteur?.poste || '',
      entreprise: data.recruteur?.entreprise?.nom || '',
    }
  } catch (err) {
    console.error('Erreur profil:', err)
    if (err.response?.status === 401) {
      error.value = 'Session expirée.'
    } else if (err.code === 'ERR_NETWORK') {
      error.value = 'Impossible de contacter le serveur.'
    } else {
      error.value = err.response?.data?.message || 'Erreur lors du chargement.'
    }
  } finally {
    loading.value = false
  }
}

const saveProfile = async () => {
  saving.value = true
  successMessage.value = ''
  error.value = ''

  try {
    await api.patch('/auth/profil', profile.value)
    successMessage.value = 'Profil mis à jour avec succès !'

    if (authStore.user) {
      authStore.user.prenom = profile.value.prenom
      authStore.user.nom = profile.value.nom
      localStorage.setItem('user', JSON.stringify(authStore.user))
    }

    setTimeout(() => { successMessage.value = '' }, 3000)
  } catch (err) {
    console.error('Erreur sauvegarde:', err)
    if (err.response?.status === 422) {
      error.value = 'Veuillez vérifier les informations saisies.'
    } else {
      error.value = err.response?.data?.message || 'Erreur lors de l\'enregistrement.'
    }
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchProfile()
})
</script>