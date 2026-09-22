<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Recruteur" 
    page-title="Rechercher" 
    page-subtitle="Trouvez les meilleurs talents"
  >
    <template #menu>
      <SidebarItem to="/recruiter" icon="🏠" label="Accueil" />
      <SidebarItem to="/recruiter/jobs" icon="💼" label="Mes offres" :badge="myJobsCount" />
      <SidebarItem to="/recruiter/candidates" icon="🧑‍💻" label="Candidats" :badge="candidatesCount" />
      <SidebarItem to="/recruiter/search" icon="🔍" label="Rechercher" />
      <SidebarItem to="/recruiter/ai-helper" icon="🤖" label="AI Helper" />
      <SidebarItem to="/recruiter/messages" icon="💬" label="Messages" :badge="messagesCount" />
    </template>

    <!-- Barre de recherche -->
    <div class="flex flex-col sm:flex-row gap-4 mb-6">
      <div class="flex-1 relative">
        <input 
          v-model="searchQuery" 
          @keydown.enter="search"
          type="text" 
          placeholder="Rechercher par nom, poste..." 
          class="w-full pl-11 pr-4 py-3.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all text-sm"
        >
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
      </div>
      <button 
        @click="search"
        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-all shadow-sm hover:shadow-md whitespace-nowrap"
      >
        Rechercher
      </button>
    </div>

    <!-- Filtres -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
      <select 
        v-model="selectedOffer"
        @change="search"
        class="px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white text-gray-700 text-sm"
      >
        <option value="">Toutes les offres</option>
        <option v-for="job in myJobs" :key="job.id" :value="job.id">
          {{ job.titre }}
        </option>
      </select>
      <select 
        v-model="selectedStatus"
        @change="search"
        class="px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white text-gray-700 text-sm"
      >
        <option value="">Tous les statuts</option>
        <option value="en_attente">En attente</option>
        <option value="en_cours">En cours</option>
        <option value="preselectionnee">Présélectionnée</option>
        <option value="entretien">Entretien</option>
        <option value="acceptee">Acceptée</option>
        <option value="refusee">Refusée</option>
      </select>
      <select 
        v-model="sortBy"
        @change="sortResults"
        class="px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white text-gray-700 text-sm"
      >
        <option value="score_desc">Score IA (élevé → faible)</option>
        <option value="score_asc">Score IA (faible → élevé)</option>
        <option value="date_desc">Plus récents</option>
        <option value="date_asc">Plus anciens</option>
      </select>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <!-- Résultats -->
    <div v-else class="space-y-3">
      <div 
        v-for="app in results" 
        :key="app.id" 
        class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all"
      >
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <div class="flex items-center gap-3">
              <img 
                :src="getAvatar(app)" 
                alt="Avatar" 
                class="w-12 h-12 rounded-full border-2 border-blue-100 object-cover"
              >
              <div>
                <h4 class="font-semibold text-gray-800">
                  {{ app.candidat?.prenom }} {{ app.candidat?.nom }}
                </h4>
                <p class="text-sm text-gray-500">{{ app.candidat?.diplome || 'Candidat' }}</p>
              </div>
            </div>
            <div class="flex flex-wrap items-center gap-3 mt-2 text-xs text-gray-400">
              <span>📍 {{ app.candidat?.ville || 'N/A' }}</span>
              <span>•</span>
              <span>⏳ {{ app.candidat?.experience_totale || 0 }} ans d'exp.</span>
              <span>•</span>
              <span>💼 {{ app.offre?.titre }}</span>
            </div>
          </div>
          <div class="flex items-center gap-3 shrink-0">
            <div v-if="app.analyse_ia?.score_matching" class="text-center">
              <p class="text-lg font-bold" :class="getScoreTextColor(app.analyse_ia.score_matching)">
                {{ Math.round(app.analyse_ia.score_matching) }}%
              </p>
              <p class="text-xs text-gray-400">Match</p>
            </div>
            <span :class="['text-xs font-medium px-3 py-1 rounded-full', getStatusBadge(app.statut)]">
              {{ formatStatus(app.statut) }}
            </span>
            <router-link 
              to="/recruiter/messages"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all"
            >
              Contacter
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Aucun résultat -->
    <div v-if="!loading && results.length === 0" class="text-center py-12 bg-white rounded-2xl border border-gray-100">
      <p class="text-6xl mb-4">🔍</p>
      <h3 class="text-lg font-semibold text-gray-800">Aucun résultat</h3>
      <p class="text-sm text-gray-500 mt-1">Essayez d'autres mots-clés</p>
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
const results = ref([])
const myJobs = ref([])
const searchQuery = ref('')
const selectedOffer = ref('')
const selectedStatus = ref('')
const sortBy = ref('score_desc')

const myJobsCount = ref(0)
const candidatesCount = ref(0)
const messagesCount = ref(0)

const getAvatar = (app) => {
  const name = `${app.candidat?.prenom || ''}+${app.candidat?.nom || ''}`.trim() || 'User'
  return `https://ui-avatars.com/api/?name=${name}&background=2563eb&color=fff&size=48`
}

const search = async () => {
  loading.value = true
  try {
    const params = {}
    if (searchQuery.value) params.recherche = searchQuery.value
    if (selectedOffer.value) params.id_offre = selectedOffer.value
    if (selectedStatus.value) params.statut = selectedStatus.value

    const response = await api.get('/recruteur/candidatures', { params })
    results.value = response.data.data || response.data.candidatures || response.data || []
    sortResults()
  } catch (err) {
    console.error(err)
    results.value = []
  } finally {
    loading.value = false
  }
}

const sortResults = () => {
  const sorted = [...results.value]
  if (sortBy.value === 'score_desc') {
    sorted.sort((a, b) => (b.analyse_ia?.score_matching || 0) - (a.analyse_ia?.score_matching || 0))
  } else if (sortBy.value === 'score_asc') {
    sorted.sort((a, b) => (a.analyse_ia?.score_matching || 0) - (b.analyse_ia?.score_matching || 0))
  } else if (sortBy.value === 'date_desc') {
    sorted.sort((a, b) => new Date(b.date_candidature) - new Date(a.date_candidature))
  } else if (sortBy.value === 'date_asc') {
    sorted.sort((a, b) => new Date(a.date_candidature) - new Date(b.date_candidature))
  }
  results.value = sorted
}

const fetchMyJobs = async () => {
  try {
    const response = await api.get('/recruteur/offres')
    myJobs.value = response.data.data || response.data.offres || []
    myJobsCount.value = myJobs.value.length
  } catch (err) {
    console.error(err)
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

const getScoreTextColor = (score) => {
  if (score >= 80) return 'text-green-600'
  if (score >= 60) return 'text-amber-600'
  return 'text-red-600'
}

onMounted(() => {
  fetchMyJobs()
  search()
})
</script>