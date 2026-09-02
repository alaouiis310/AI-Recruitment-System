<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Gestion des candidats" 
    page-subtitle="Gérez tous les candidats de la plateforme"
  >
    <!-- Header Actions -->
    <template #header-actions>
      <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
        + Ajouter un candidat
      </button>
    </template>

    <!-- Statistiques -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 md:gap-4 mb-6">
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
        <p class="text-2xl font-bold text-gray-800">1,248</p>
        <p class="text-xs text-gray-500">Total</p>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
        <p class="text-2xl font-bold text-green-600">892</p>
        <p class="text-xs text-gray-500">Actifs</p>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
        <p class="text-2xl font-bold text-amber-600">234</p>
        <p class="text-xs text-gray-500">En recherche</p>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
        <p class="text-2xl font-bold text-red-600">122</p>
        <p class="text-xs text-gray-500">Inactifs</p>
      </div>
    </div>

    <!-- Barre de recherche -->
    <div class="flex flex-col sm:flex-row gap-4 mb-6">
      <div class="flex-1 relative">
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Rechercher un candidat..." 
          class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
        >
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
      </div>
      <select v-model="selectedStatus" class="px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white text-gray-700 text-sm">
        <option value="">Tous les statuts</option>
        <option value="Actif">Actif</option>
        <option value="En recherche">En recherche</option>
        <option value="Inactif">Inactif</option>
      </select>
    </div>

    <!-- Liste des candidats -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-100 text-left">
              <th class="px-6 py-3 font-medium text-gray-500">Candidat</th>
              <th class="px-6 py-3 font-medium text-gray-500">Candidatures</th>
              <th class="px-6 py-3 font-medium text-gray-500">Score IA</th>
              <th class="px-6 py-3 font-medium text-gray-500">Statut</th>
              <th class="px-6 py-3 font-medium text-gray-500">Date</th>
              <th class="px-6 py-3 font-medium text-gray-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="candidate in filteredCandidates" :key="candidate.name" class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <img :src="candidate.avatar" alt="Avatar" class="w-9 h-9 rounded-full border-2 border-blue-100 object-cover">
                  <div>
                    <p class="font-medium text-gray-800">{{ candidate.name }}</p>
                    <p class="text-xs text-gray-500">{{ candidate.email }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-gray-600">{{ candidate.applications }}</td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <div class="w-16 bg-gray-200 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full" :style="{ width: candidate.score + '%', background: getScoreColor(candidate.score) }"></div>
                  </div>
                  <span class="text-xs font-medium" :class="getScoreTextColor(candidate.score)">{{ candidate.score }}%</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', getStatusBadge(candidate.status)]">
                  {{ candidate.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-gray-500">{{ candidate.date }}</td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <button class="text-blue-600 hover:text-blue-700 text-sm font-medium">Voir</button>
                  <span class="text-gray-300">|</span>
                  <button class="text-gray-500 hover:text-gray-700 text-sm">✎</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const userName = ref('Admin')
const searchQuery = ref('')
const selectedStatus = ref('')

const candidates = ref([
  { name: 'Thomas Leroy', email: 'thomas@email.com', applications: 5, score: 92, status: 'Actif', date: '20/05/2024', avatar: 'https://ui-avatars.com/api/?name=Thomas+Leroy&background=2563eb&color=fff&size=36' },
  { name: 'Camille Dubois', email: 'camille@email.com', applications: 3, score: 85, status: 'En recherche', date: '18/05/2024', avatar: 'https://ui-avatars.com/api/?name=Camille+Dubois&background=7c3aed&color=fff&size=36' },
  { name: 'Mehdi Amine', email: 'mehdi@email.com', applications: 7, score: 78, status: 'Actif', date: '15/05/2024', avatar: 'https://ui-avatars.com/api/?name=Mehdi+Amine&background=059669&color=fff&size=36' },
  { name: 'Lucas Bernard', email: 'lucas@email.com', applications: 2, score: 71, status: 'Inactif', date: '12/05/2024', avatar: 'https://ui-avatars.com/api/?name=Lucas+Bernard&background=d97706&color=fff&size=36' },
])

const filteredCandidates = computed(() => {
  return candidates.value.filter(c => {
    const matchSearch = c.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchStatus = !selectedStatus.value || c.status === selectedStatus.value
    return matchSearch && matchStatus
  })
})

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

const getStatusBadge = (status) => {
  const badges = {
    'Actif': 'bg-green-100 text-green-700',
    'En recherche': 'bg-blue-100 text-blue-700',
    'Inactif': 'bg-gray-100 text-gray-700'
  }
  return badges[status] || 'bg-gray-100 text-gray-700'
}
</script>