<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Recruteur" 
    page-title="Mes offres" 
    page-subtitle="Gérez toutes vos offres d'emploi"
  >
    <template #menu>
      <SidebarItem to="/recruiter" icon="🏠" label="Accueil" />
      <SidebarItem to="/recruiter/jobs" icon="💼" label="Mes offres" :badge="myJobsCount" />
      <SidebarItem to="/recruiter/candidates" icon="🧑‍💻" label="Candidats" :badge="candidatesCount" />
      <SidebarItem to="/recruiter/search" icon="🔍" label="Rechercher" />
      <SidebarItem to="/recruiter/ai-helper" icon="🤖" label="AI Helper" />
      <SidebarItem to="/recruiter/messages" icon="💬" label="Messages" :badge="messagesCount" />
    </template>

    <template #header-actions>
      <button 
        @click="openCreateModal"
        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md"
      >
        + Publier une offre
      </button>
    </template>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="text-center">
        <div class="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p class="text-gray-500 mt-4 text-sm">Chargement des offres...</p>
      </div>
    </div>

    <!-- Erreur -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-2xl p-6 text-center">
      <p class="text-4xl mb-2">⚠️</p>
      <p class="text-red-700 font-medium">{{ error }}</p>
      <button @click="fetchJobs" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700">
        Réessayer
      </button>
    </div>

    <div v-else>
      <!-- Statistiques -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 md:gap-4 mb-6">
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
          <p class="text-2xl font-bold text-gray-800">{{ jobs.length }}</p>
          <p class="text-xs text-gray-500">Total</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
          <p class="text-2xl font-bold text-green-600">{{ countByStatus('ouverte') }}</p>
          <p class="text-xs text-gray-500">Ouvertes</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
          <p class="text-2xl font-bold text-amber-600">{{ countByStatus('suspendue') }}</p>
          <p class="text-xs text-gray-500">Suspendues</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
          <p class="text-2xl font-bold text-red-600">{{ countByStatus('fermee') }}</p>
          <p class="text-xs text-gray-500">Fermées</p>
        </div>
      </div>

      <!-- Liste des offres -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-100 text-left">
                <th class="px-6 py-3 font-medium text-gray-500">Offre</th>
                <th class="px-6 py-3 font-medium text-gray-500">Candidatures</th>
                <th class="px-6 py-3 font-medium text-gray-500">Date</th>
                <th class="px-6 py-3 font-medium text-gray-500">Statut</th>
                <th class="px-6 py-3 font-medium text-gray-500">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="job in jobs" 
                :key="job.id" 
                class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors"
              >
                <td class="px-6 py-4">
                  <div>
                    <p class="font-medium text-gray-800">{{ job.titre }}</p>
                    <p class="text-xs text-gray-500">
                      {{ job.localisation }} • {{ formatContract(job.type_contrat) }}
                    </p>
                  </div>
                </td>
                <td class="px-6 py-4 text-gray-600">
                  {{ job.candidatures_count || 0 }}
                </td>
                <td class="px-6 py-4 text-gray-500">
                  {{ formatDate(job.date_publication) }}
                </td>
                <td class="px-6 py-4">
                  <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', getStatusBadge(job.statut)]">
                    {{ formatStatus(job.statut) }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <button class="text-blue-600 hover:text-blue-700 text-sm font-medium">Voir</button>
                    <span class="text-gray-300">|</span>
                    <button class="text-gray-500 hover:text-gray-700 text-sm">✎</button>
                    <span class="text-gray-300">|</span>
                    <button 
                      @click="deleteJob(job)"
                      class="text-red-500 hover:text-red-700 text-sm"
                    >
                      🗑️
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="jobs.length === 0">
                <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                  Aucune offre publiée
                </td>
              </tr>
            </tbody>
          </table>
        </div>
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

const loading = ref(true)
const error = ref('')
const jobs = ref([])

const myJobsCount = computed(() => jobs.value.length)
const candidatesCount = ref(0)
const messagesCount = ref(0)

const countByStatus = (status) => {
  return jobs.value.filter(j => j.statut === status).length
}

const fetchJobs = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/recruteur/offres')
    jobs.value = response.data.data || response.data.offres || response.data || []
  } catch (err) {
    console.error('Erreur offres:', err)
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

const deleteJob = async (job) => {
  if (!confirm(`Supprimer l'offre "${job.titre}" ?`)) return
  try {
    await api.delete(`/recruteur/offres/${job.id}`)
    jobs.value = jobs.value.filter(j => j.id !== job.id)
  } catch (err) {
    alert('❌ Erreur lors de la suppression.')
  }
}

const openCreateModal = () => {
  alert('Fonctionnalité à venir : création d\'offre')
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  try {
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    })
  } catch { return date }
}

const formatContract = (type) => {
  const types = {
    'cdi': 'CDI', 'cdd': 'CDD', 'stage': 'Stage', 'freelance': 'Freelance', 'alternance': 'Alternance'
  }
  return types[type] || type || 'N/A'
}

const formatStatus = (status) => {
  const labels = { 'ouverte': 'Ouverte', 'fermee': 'Fermée', 'suspendue': 'Suspendue' }
  return labels[status] || status || 'N/A'
}

const getStatusBadge = (status) => {
  const badges = {
    'ouverte': 'bg-green-100 text-green-700',
    'suspendue': 'bg-amber-100 text-amber-700',
    'fermee': 'bg-red-100 text-red-700'
  }
  return badges[status] || 'bg-gray-100 text-gray-700'
}

onMounted(() => {
  fetchJobs()
})
</script>