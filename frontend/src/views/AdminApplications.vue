<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Gestion des candidatures" 
    page-subtitle="Gérez toutes les candidatures"
  >
    <!-- Header Actions -->
    <template #header-actions>
      <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
        + Exporter
      </button>
    </template>

    <!-- Statistiques -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 md:gap-4 mb-6">
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
        <p class="text-2xl font-bold text-gray-800">5,426</p>
        <p class="text-xs text-gray-500">Total</p>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
        <p class="text-2xl font-bold text-blue-600">2,134</p>
        <p class="text-xs text-gray-500">En cours</p>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
        <p class="text-2xl font-bold text-purple-600">856</p>
        <p class="text-xs text-gray-500">Entretien</p>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
        <p class="text-2xl font-bold text-green-600">324</p>
        <p class="text-xs text-gray-500">Acceptées</p>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
        <p class="text-2xl font-bold text-red-600">2,112</p>
        <p class="text-xs text-gray-500">Refusées</p>
      </div>
    </div>

    <!-- Barre de recherche -->
    <div class="flex flex-col sm:flex-row gap-4 mb-6">
      <div class="flex-1 relative">
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Rechercher une candidature..." 
          class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
        >
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
      </div>
      <select v-model="selectedStatus" class="px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white text-gray-700 text-sm">
        <option value="">Tous les statuts</option>
        <option value="En cours">En cours</option>
        <option value="Entretien">Entretien</option>
        <option value="Acceptée">Acceptée</option>
        <option value="Refusée">Refusée</option>
      </select>
    </div>

    <!-- Liste des candidatures -->
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
            <tr v-for="app in filteredApplications" :key="app.candidate" class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <img :src="app.avatar" alt="Avatar" class="w-9 h-9 rounded-full border-2 border-blue-100 object-cover">
                  <div>
                    <p class="font-medium text-gray-800">{{ app.candidate }}</p>
                    <p class="text-xs text-gray-500">{{ app.email }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-gray-600">{{ app.job }}</td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <div class="w-16 bg-gray-200 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full" :style="{ width: app.score + '%', background: getScoreColor(app.score) }"></div>
                  </div>
                  <span class="text-xs font-medium" :class="getScoreTextColor(app.score)">{{ app.score }}%</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', getStatusBadge(app.status)]">
                  {{ app.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-gray-500">{{ app.date }}</td>
              <td class="px-6 py-4">
                <button class="text-blue-600 hover:text-blue-700 text-sm font-medium">Voir</button>
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

const applications = ref([
  { candidate: 'Thomas Leroy', email: 'thomas@email.com', job: 'Dev Full Stack', score: 92, status: 'Entretien', date: '20/05/2024', avatar: 'https://ui-avatars.com/api/?name=Thomas+Leroy&background=2563eb&color=fff&size=36' },
  { candidate: 'Camille Dubois', email: 'camille@email.com', job: 'UX Designer', score: 85, status: 'En cours', date: '18/05/2024', avatar: 'https://ui-avatars.com/api/?name=Camille+Dubois&background=7c3aed&color=fff&size=36' },
  { candidate: 'Mehdi Amine', email: 'mehdi@email.com', job: 'Data Analyst', score: 78, status: 'En cours', date: '15/05/2024', avatar: 'https://ui-avatars.com/api/?name=Mehdi+Amine&background=059669&color=fff&size=36' },
  { candidate: 'Lucas Bernard', email: 'lucas@email.com', job: 'Dev Full Stack', score: 71, status: 'Refusée', date: '12/05/2024', avatar: 'https://ui-avatars.com/api/?name=Lucas+Bernard&background=d97706&color=fff&size=36' },
  { candidate: 'Marie Petit', email: 'marie@email.com', job: 'Product Owner', score: 88, status: 'Acceptée', date: '10/05/2024', avatar: 'https://ui-avatars.com/api/?name=Marie+Petit&background=dc2626&color=fff&size=36' },
])

const filteredApplications = computed(() => {
  return applications.value.filter(a => {
    const matchSearch = a.candidate.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                        a.job.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchStatus = !selectedStatus.value || a.status === selectedStatus.value
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
    'En cours': 'bg-blue-100 text-blue-700',
    'Entretien': 'bg-purple-100 text-purple-700',
    'Acceptée': 'bg-green-100 text-green-700',
    'Refusée': 'bg-red-100 text-red-700'
  }
  return badges[status] || 'bg-gray-100 text-gray-700'
}
</script>
