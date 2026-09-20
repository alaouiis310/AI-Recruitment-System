<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Candidat" 
    page-title="Offres d'emploi" 
    page-subtitle="Découvrez les meilleures opportunités"
  >
    <template #menu>
      <SidebarItem to="/candidate" icon="🏠" label="Accueil" />
      <SidebarItem to="/candidate/applications" icon="📝" label="Mes candidatures" :badge="applicationsCount" />
      <SidebarItem to="/candidate/jobs" icon="💼" label="Offres d'emploi" :badge="jobsCount" />
      <SidebarItem to="/candidate/interviews" icon="🗓️" label="Mes entretiens" :badge="interviewsCount" />
      <SidebarItem to="/candidate/saved" icon="⭐" label="Offres sauvegardées" :badge="savedCount" />
      <SidebarItem to="/candidate/profile" icon="👤" label="Mon profil" />
      <SidebarItem to="/candidate/cv" icon="📄" label="Mon CV" />
    </template>

    <!-- Barre de recherche -->
    <div class="flex flex-col sm:flex-row gap-4 mb-6">
      <div class="flex-1 relative">
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Rechercher une offre..."
          @keydown.enter="fetchJobs"
          class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
        >
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
      </div>
      <div class="flex gap-3">
        <select 
          v-model="selectedContract" 
          @change="fetchJobs"
          class="px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white text-gray-700 text-sm"
        >
          <option value="">Tous les contrats</option>
          <option value="cdi">CDI</option>
          <option value="cdd">CDD</option>
          <option value="stage">Stage</option>
          <option value="freelance">Freelance</option>
        </select>
        <button 
          @click="fetchJobs"
          class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-all"
        >
          Rechercher
        </button>
      </div>
    </div>

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

    <!-- Résultats -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
      <div 
        v-for="job in jobs" 
        :key="job.id" 
        class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-200"
      >
        <div class="flex items-start justify-between mb-3">
          <div>
            <h3 class="font-semibold text-gray-800">{{ job.titre }}</h3>
            <p class="text-sm text-gray-500">{{ job.departement?.entreprise?.nom || job.entreprise?.nom || 'N/A' }}</p>
          </div>
          <span class="text-xs font-medium bg-blue-50 text-blue-600 px-2.5 py-1 rounded-full uppercase">
            {{ job.type_contrat }}
          </span>
        </div>
        
        <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 mb-3">
          <span>📍 {{ job.localisation }}</span>
          <span v-if="job.salaire">• 💰 {{ job.salaire }}€</span>
          <span>• 📅 {{ formatDate(job.date_publication) }}</span>
        </div>

        <p class="text-sm text-gray-600 mb-4 line-clamp-2">
          {{ job.description?.substring(0, 120) }}...
        </p>

        <div class="flex items-center gap-2">
          <button 
            @click="applyToJob(job)"
            :disabled="applying === job.id"
            class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-medium rounded-xl transition-all"
          >
            {{ applying === job.id ? 'Envoi...' : 'Postuler' }}
          </button>
          <button class="px-4 py-2 border border-gray-200 hover:bg-gray-50 rounded-xl transition-all text-gray-500">
            ⭐
          </button>
        </div>
      </div>
    </div>

    <!-- Aucun résultat -->
    <div v-if="!loading && !error && jobs.length === 0" class="text-center py-12">
      <p class="text-4xl mb-4">🔍</p>
      <h3 class="text-lg font-semibold text-gray-800">Aucune offre trouvée</h3>
      <p class="text-sm text-gray-500 mt-1">Essayez de modifier vos filtres</p>
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
  return user?.prenom ? `${user.prenom} ${user.nom || ''}`.trim() : 'Candidat'
})

const loading = ref(true)
const error = ref('')
const jobs = ref([])
const searchQuery = ref('')
const selectedContract = ref('')
const applying = ref(null)

const applicationsCount = ref(0)
const jobsCount = computed(() => jobs.value.length)
const interviewsCount = ref(0)
const savedCount = ref(0)

const fetchJobs = async () => {
  loading.value = true
  error.value = ''

  try {
    const params = {}
    if (searchQuery.value) params.mots_cles = searchQuery.value
    if (selectedContract.value) params.type_contrat = selectedContract.value

    const response = await api.get('/offres', { params })
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

const applyToJob = async (job) => {
  if (!confirm(`Postuler pour "${job.titre}" ?`)) return

  applying.value = job.id
  try {
    await api.post('/candidat/candidatures', { id_offre: job.id })
    alert('✅ Candidature envoyée avec succès !')
  } catch (err) {
    if (err.response?.status === 422) {
      alert('❌ ' + (err.response.data.message || 'Vous avez déjà postulé à cette offre.'))
    } else {
      alert('❌ Erreur lors de l\'envoi de la candidature.')
    }
  } finally {
    applying.value = null
  }
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  try {
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short'
    })
  } catch { return date }
}

onMounted(() => {
  fetchJobs()
})
</script>