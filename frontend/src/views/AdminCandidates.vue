<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Gestion des candidats" 
    page-subtitle="Gérez tous les candidats de la plateforme"
  >
    <template #header-actions>
      <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
        + Ajouter un candidat
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
      <button @click="fetchCandidates" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700">
        Réessayer
      </button>
    </div>

    <div v-else>
      <!-- Statistiques -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 md:gap-4 mb-6">
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
          <p class="text-2xl font-bold text-gray-800">{{ pagination.total || candidates.length }}</p>
          <p class="text-xs text-gray-500">Total</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
          <p class="text-2xl font-bold text-green-600">{{ countByStatus('actif') }}</p>
          <p class="text-xs text-gray-500">Actifs</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
          <p class="text-2xl font-bold text-amber-600">{{ countByStatus('en_recherche') }}</p>
          <p class="text-xs text-gray-500">En recherche</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
          <p class="text-2xl font-bold text-red-600">{{ countByStatus('desactive') }}</p>
          <p class="text-xs text-gray-500">Désactivés</p>
        </div>
      </div>

      <!-- Barre de recherche -->
      <div class="flex flex-col sm:flex-row gap-4 mb-6">
        <div class="flex-1 relative">
          <input 
            v-model="searchQuery" 
            @input="debouncedSearch"
            type="text" 
            placeholder="Rechercher un candidat..." 
            class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
          >
          <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
        </div>
        <select 
          v-model="selectedStatus" 
          @change="fetchCandidates"
          class="px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white text-gray-700 text-sm"
        >
          <option value="">Tous les statuts</option>
          <option value="actif">Actif</option>
          <option value="suspendu">Suspendu</option>
          <option value="desactive">Désactivé</option>
        </select>
      </div>

      <!-- Tableau -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-100 text-left">
                <th class="px-6 py-3 font-medium text-gray-500">Candidat</th>
                <th class="px-6 py-3 font-medium text-gray-500">Expérience</th>
                <th class="px-6 py-3 font-medium text-gray-500">Candidatures</th>
                <th class="px-6 py-3 font-medium text-gray-500">Statut</th>
                <th class="px-6 py-3 font-medium text-gray-500">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="c in candidates" 
                :key="c.id" 
                class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors"
              >
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <img 
                      :src="getAvatar(c)" 
                      alt="Avatar" 
                      class="w-9 h-9 rounded-full border-2 border-blue-100 object-cover"
                    >
                    <div>
                      <p class="font-medium text-gray-800">{{ c.prenom }} {{ c.nom }}</p>
                      <p class="text-xs text-gray-500">{{ c.user?.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-gray-600">{{ c.experience_totale || 0 }} ans</td>
                <td class="px-6 py-4 text-gray-600">{{ c.candidatures_count || 0 }}</td>
                <td class="px-6 py-4">
                  <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', getStatusBadge(c.user?.etat_compte)]">
                    {{ formatStatus(c.user?.etat_compte) }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <button class="text-blue-600 hover:text-blue-700 text-sm font-medium">Voir</button>
                    <span class="text-gray-300">|</span>
                    <button 
                      @click="toggleStatus(c)"
                      class="text-amber-600 hover:text-amber-700 text-sm"
                    >
                      {{ c.user?.etat_compte === 'actif' ? 'Suspendre' : 'Activer' }}
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="candidates.length === 0">
                <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                  Aucun candidat
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
const candidates = ref([])
const pagination = ref({ total: 0 })
const searchQuery = ref('')
const selectedStatus = ref('')

let searchTimeout = null
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => fetchCandidates(), 400)
}

const countByStatus = (status) => {
  return candidates.value.filter(c => c.user?.etat_compte === status).length
}

const getAvatar = (c) => {
  const name = `${c.prenom || ''}+${c.nom || ''}`.trim() || 'User'
  return `https://ui-avatars.com/api/?name=${name}&background=2563eb&color=fff&size=36`
}

const fetchCandidates = async () => {
  loading.value = true
  error.value = ''

  try {
    const params = {}
    if (searchQuery.value) params.recherche = searchQuery.value
    if (selectedStatus.value) params.etat_compte = selectedStatus.value

    const response = await api.get('/admin/candidats', { params })
    candidates.value = response.data.data || response.data.candidats || []
    pagination.value = response.data.pagination || { total: candidates.value.length }
  } catch (err) {
    console.error('Erreur candidats:', err)
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

const toggleStatus = async (c) => {
  const newStatus = c.user?.etat_compte === 'actif' ? 'suspendu' : 'actif'
  if (!confirm(`Changer le statut de ${c.prenom} à "${formatStatus(newStatus)}" ?`)) return

  try {
    await api.patch(`/admin/utilisateurs/${c.user.id}/etat`, { etat_compte: newStatus })
    c.user.etat_compte = newStatus
  } catch (err) {
    alert('❌ Erreur lors de la mise à jour.')
  }
}

const formatStatus = (status) => {
  const labels = { 'actif': 'Actif', 'suspendu': 'Suspendu', 'desactive': 'Désactivé', 'inactif': 'Inactif' }
  return labels[status] || status || 'N/A'
}

const getStatusBadge = (status) => {
  const badges = {
    'actif': 'bg-green-100 text-green-700',
    'suspendu': 'bg-amber-100 text-amber-700',
    'desactive': 'bg-red-100 text-red-700',
  }
  return badges[status] || 'bg-gray-100 text-gray-700'
}

onMounted(() => {
  fetchCandidates()
})
</script>