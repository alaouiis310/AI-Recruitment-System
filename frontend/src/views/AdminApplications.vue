<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Gestion des candidatures" 
    page-subtitle="Gérez toutes les candidatures"
  >
    <template #header-actions>
      <button 
        @click="exportCSV"
        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md"
      >
        📥 Exporter CSV
      </button>
    </template>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
    </div>

    <!-- Erreur -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-2xl p-6 text-center">
      <p class="text-4xl mb-2">⚠️</p>
      <p class="text-red-700 font-medium">{{ error }}</p>
      <button @click="fetchApplications" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700">
        Réessayer
      </button>
    </div>

    <div v-else>
      <!-- Statistiques -->
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 md:gap-4 mb-6">
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
          <p class="text-2xl font-bold text-gray-800">{{ pagination.total || applications.length }}</p>
          <p class="text-xs text-gray-500">Total</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
          <p class="text-2xl font-bold text-blue-600">{{ countByStatus('en_cours') }}</p>
          <p class="text-xs text-gray-500">En cours</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
          <p class="text-2xl font-bold text-purple-600">{{ countByStatus('entretien') }}</p>
          <p class="text-xs text-gray-500">Entretien</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
          <p class="text-2xl font-bold text-green-600">{{ countByStatus('acceptee') }}</p>
          <p class="text-xs text-gray-500">Acceptées</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
          <p class="text-2xl font-bold text-red-600">{{ countByStatus('refusee') }}</p>
          <p class="text-xs text-gray-500">Refusées</p>
        </div>
      </div>

      <!-- Recherche -->
      <div class="flex flex-col sm:flex-row gap-4 mb-6">
        <div class="flex-1 relative">
          <input 
            v-model="searchQuery" 
            @input="debouncedSearch"
            type="text" 
            placeholder="Rechercher une candidature..." 
            class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
          >
          <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
        </div>
        <select 
          v-model="selectedStatus" 
          @change="fetchApplications"
          class="px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white text-gray-700 text-sm"
        >
          <option value="">Tous les statuts</option>
          <option value="en_attente">En attente</option>
          <option value="en_cours">En cours</option>
          <option value="preselectionnee">Présélectionnée</option>
          <option value="entretien">Entretien</option>
          <option value="acceptee">Acceptée</option>
          <option value="refusee">Refusée</option>
        </select>
      </div>

      <!-- Tableau -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-100 text-left">
                <th class="px-6 py-3 font-medium text-gray-500">Candidat</th>
                <th class="px-6 py-3 font-medium text-gray-500">Offre</th>
                <th class="px-6 py-3 font-medium text-gray-500">Score IA</th>
                <th class="px-6 py-3 font-medium text-gray-500">Statut</th>
                <th class="px-6 py-3 font-medium text-gray-500">Date</th>
                <th class="px-6 py-3 font-medium text-gray-500">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="app in applications" 
                :key="app.id" 
                class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors"
              >
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <img 
                      :src="getAvatar(app)" 
                      alt="Avatar" 
                      class="w-9 h-9 rounded-full border-2 border-blue-100 object-cover"
                    >
                    <div>
                      <p class="font-medium text-gray-800">
                        {{ app.candidat?.prenom }} {{ app.candidat?.nom }}
                      </p>
                      <p class="text-xs text-gray-500">{{ app.candidat?.user?.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-gray-600">{{ app.offre?.titre || 'N/A' }}</td>
                <td class="px-6 py-4">
                  <div v-if="app.analyse_ia?.score_matching" class="flex items-center gap-2">
                    <div class="w-16 bg-gray-200 rounded-full h-1.5">
                      <div 
                        class="h-1.5 rounded-full" 
                        :style="{ 
                          width: app.analyse_ia.score_matching + '%', 
                          background: getScoreColor(app.analyse_ia.score_matching) 
                        }"
                      ></div>
                    </div>
                    <span 
                      class="text-xs font-medium" 
                      :class="getScoreTextColor(app.analyse_ia.score_matching)"
                    >
                      {{ Math.round(app.analyse_ia.score_matching) }}%
                    </span>
                  </div>
                  <span v-else class="text-xs text-gray-400">Non analysé</span>
                </td>
                <td class="px-6 py-4">
                  <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', getStatusBadge(app.statut)]">
                    {{ formatStatus(app.statut) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-gray-500">{{ formatDate(app.date_candidature) }}</td>
                <td class="px-6 py-4">
                  <button class="text-blue-600 hover:text-blue-700 text-sm font-medium">Voir</button>
                </td>
              </tr>
              <tr v-if="applications.length === 0">
                <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                  Aucune candidature
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
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()

const userName = computed(() => {
  const user = authStore.user
  return user?.prenom ? `${user.prenom} ${user.nom || ''}`.trim() : 'Admin'
})

const loading = ref(true)
const error = ref('')
const applications = ref([])
const pagination = ref({ total: 0 })
const searchQuery = ref('')
const selectedStatus = ref('')

let searchTimeout = null
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => fetchApplications(), 400)
}

const countByStatus = (status) => {
  return applications.value.filter(a => a.statut === status).length
}

const getAvatar = (app) => {
  const name = `${app.candidat?.prenom || ''}+${app.candidat?.nom || ''}`.trim() || 'User'
  return `https://ui-avatars.com/api/?name=${name}&background=2563eb&color=fff&size=36`
}

const fetchApplications = async () => {
  loading.value = true
  error.value = ''

  try {
    const params = {}
    if (searchQuery.value) params.recherche = searchQuery.value
    if (selectedStatus.value) params.statut = selectedStatus.value

    const response = await api.get('/admin/candidatures', { params })
    applications.value = response.data.data || response.data.candidatures || []
    pagination.value = response.data.pagination || { total: applications.value.length }
  } catch (err) {
    console.error('Erreur candidatures:', err)
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

const exportCSV = async () => {
  try {
    const response = await api.get('/admin/candidatures/export', {
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'candidatures.csv')
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (err) {
    alert('❌ Erreur lors de l\'export.')
  }
}

const formatStatus = (status) => {
  const labels = {
    'en_attente': 'En attente',
    'en_cours': 'En cours',
    'preselectionnee': 'Présélectionnée',
    'entretien': 'Entretien',
    'acceptee': 'Acceptée',
    'refusee': 'Refusée',
  }
  return labels[status] || status || 'N/A'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  try {
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit', month: 'short', year: 'numeric'
    })
  } catch { return date }
}

const getStatusBadge = (status) => {
  const badges = {
    'en_attente': 'bg-blue-100 text-blue-700',
    'en_cours': 'bg-amber-100 text-amber-700',
    'preselectionnee': 'bg-indigo-100 text-indigo-700',
    'entretien': 'bg-purple-100 text-purple-700',
    'acceptee': 'bg-green-100 text-green-700',
    'refusee': 'bg-red-100 text-red-700',
  }
  return badges[status] || 'bg-gray-100 text-gray-700'
}

const getScoreColor = (score) => {
  if (score >= 80) return '#10b981'
  if (score >= 60) return '#f59e0b'
  return '#ef4444'
}

const getScoreTextColor = (score) => {
  if (score >= 80) return 'text-green-600'
  if (score >= 60) return 'text-amber-600'
  return 'text-red-600'
}

onMounted(() => {
  fetchApplications()
})
</script>
