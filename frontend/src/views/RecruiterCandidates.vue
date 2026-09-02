<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Recruteur" 
    page-title="Candidats" 
    page-subtitle="Gérez tous vos candidats"
  >
    <!-- Menu -->
    <template #menu>
      <SidebarItem to="/recruiter" icon="🏠" label="Accueil" />
      <SidebarItem to="/recruiter/jobs" icon="💼" label="Mes offres" :badge="myJobsCount" />
      <SidebarItem to="/recruiter/candidates" icon="🧑‍💻" label="Candidats" :badge="candidatesCount" active />
      <SidebarItem to="/recruiter/search" icon="🔍" label="Rechercher" />
      <SidebarItem to="/recruiter/ai-helper" icon="🤖" label="AI Helper" />
      <SidebarItem to="/recruiter/messages" icon="💬" label="Messages" :badge="messagesCount" />
    </template>

    <!-- Header Actions -->
    <template #header-actions>
      <div class="flex items-center gap-3">
        <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-xl transition-all">
          Exporter CSV
        </button>
        <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
          + Ajouter un candidat
        </button>
      </div>
    </template>

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
        <option value="Nouveau">Nouveau</option>
        <option value="En cours">En cours</option>
        <option value="Entretien">Entretien</option>
        <option value="Présélectionné">Présélectionné</option>
        <option value="Accepté">Accepté</option>
        <option value="Refusé">Refusé</option>
      </select>
    </div>

    <!-- Liste des candidats -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-100 text-left">
              <th class="px-6 py-3 font-medium text-gray-500">Candidat</th>
              <th class="px-6 py-3 font-medium text-gray-500">Poste</th>
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
              <td class="px-6 py-4 text-gray-600">{{ candidate.job }}</td>
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
                  <button class="text-gray-500 hover:text-gray-700 text-sm">💬</button>
                  <span class="text-gray-300">|</span>
                  <button class="text-gray-500 hover:text-gray-700 text-sm">⭐</button>
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
import SidebarItem from '../components/SidebarItem.vue'

const userName = ref('Sophie Martin')
const myJobsCount = ref(12)
const candidatesCount = ref(86)
const messagesCount = ref(4)

const searchQuery = ref('')
const selectedStatus = ref('')

const candidates = ref([
  { name: 'Thomas Leroy', email: 'thomas@email.com', job: 'Dev Full Stack', score: 92, status: 'Entretien', date: '20/05/2024', avatar: 'https://ui-avatars.com/api/?name=Thomas+Leroy&background=2563eb&color=fff&size=36' },
  { name: 'Camille Dubois', email: 'camille@email.com', job: 'UX Designer', score: 85, status: 'En cours', date: '18/05/2024', avatar: 'https://ui-avatars.com/api/?name=Camille+Dubois&background=7c3aed&color=fff&size=36' },
  { name: 'Mehdi Amine', email: 'mehdi@email.com', job: 'Data Analyst', score: 78, status: 'Nouveau', date: '15/05/2024', avatar: 'https://ui-avatars.com/api/?name=Mehdi+Amine&background=059669&color=fff&size=36' },
  { name: 'Lucas Bernard', email: 'lucas@email.com', job: 'Dev Full Stack', score: 71, status: 'Présélectionné', date: '12/05/2024', avatar: 'https://ui-avatars.com/api/?name=Lucas+Bernard&background=d97706&color=fff&size=36' },
  { name: 'Marie Petit', email: 'marie@email.com', job: 'Product Owner', score: 88, status: 'Accepté', date: '10/05/2024', avatar: 'https://ui-avatars.com/api/?name=Marie+Petit&background=dc2626&color=fff&size=36' },
])

const filteredCandidates = computed(() => {
  return candidates.value.filter(c => {
    const matchSearch = c.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                        c.job.toLowerCase().includes(searchQuery.value.toLowerCase())
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
    'Nouveau': 'bg-blue-100 text-blue-700',
    'En cours': 'bg-amber-100 text-amber-700',
    'Entretien': 'bg-purple-100 text-purple-700',
    'Présélectionné': 'bg-indigo-100 text-indigo-700',
    'Accepté': 'bg-green-100 text-green-700',
    'Refusé': 'bg-red-100 text-red-700'
  }
  return badges[status] || 'bg-gray-100 text-gray-700'
}
</script>