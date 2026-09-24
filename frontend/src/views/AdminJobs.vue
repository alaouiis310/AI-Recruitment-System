<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Gestion des offres" 
    page-subtitle="Gérez toutes les offres d'emploi"
  >
    <template #header-actions>
      <button @click="router.push('/admin/jobs/add')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
        + Publier une offre
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
      <button @click="fetchJobs" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700">
        Réessayer
      </button>
    </div>

    <div v-else>
      <!-- Statistiques -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 md:gap-4 mb-6">
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
          <p class="text-2xl font-bold text-gray-800">{{ pagination.total || jobs.length }}</p>
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

      <!-- Recherche -->
      <div class="flex flex-col sm:flex-row gap-4 mb-6">
        <div class="flex-1 relative">
          <input 
            v-model="searchQuery" 
            @input="debouncedSearch"
            type="text" 
            placeholder="Rechercher une offre..." 
            class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
          >
          <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
        </div>
        <select 
          v-model="selectedStatus" 
          @change="fetchJobs"
          class="px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white text-gray-700 text-sm"
        >
          <option value="">Tous les statuts</option>
          <option value="ouverte">Ouverte</option>
          <option value="fermee">Fermée</option>
          <option value="suspendue">Suspendue</option>
        </select>
      </div>

      <!-- Tableau -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-100 text-left">
                <th class="px-6 py-3 font-medium text-gray-500">Offre</th>
                <th class="px-6 py-3 font-medium text-gray-500">Entreprise</th>
                <th class="px-6 py-3 font-medium text-gray-500">Candidatures</th>
                <th class="px-6 py-3 font-medium text-gray-500">Statut</th>
                <th class="px-6 py-3 font-medium text-gray-500">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="job in jobs" 
                :key="job.id_offre" 
                class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors"
              >
                <td class="px-6 py-4">
                  <div>
                    <p class="font-medium text-gray-800">{{ job.titre }}</p>
                    <p class="text-xs text-gray-500">{{ job.localisation }} • {{ formatContract(job.type_contrat) }}</p>
                  </div>
                </td>
                <td class="px-6 py-4 text-gray-600">
                  {{ job.departement?.entreprise?.nom || 'N/A' }}
                </td>
                <td class="px-6 py-4 text-gray-600">
                  {{ job.nombre_candidatures || 0 }}
                </td>
                <td class="px-6 py-4">
                  <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', getStatusBadge(job.statut)]">
                    {{ formatStatus(job.statut) }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <button @click="fiche = construireFiche(job)" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Voir</button>
                    <span class="text-gray-300">|</span>
                    <button @click="router.push(`/admin/jobs/${job.id_offre}/edit`)" aria-label="Modifier l’offre" class="text-gray-500 hover:text-gray-700 text-sm">✎</button>
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
                  Aucune offre
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <FicheDetail :ouvert="!!fiche" :titre="fiche?.titre" :lignes="fiche?.lignes || []" @fermer="fiche = null" />
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import FicheDetail from '../components/FicheDetail.vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
const router = useRouter()

const userName = computed(() => {
  const user = authStore.user
  return user?.prenom ? `${user.prenom} ${user.nom || ''}`.trim() : 'Admin'
})

const loading = ref(true)
const error = ref('')
const jobs = ref([])
const pagination = ref({ total: 0 })
const searchQuery = ref('')
const selectedStatus = ref('')

let searchTimeout = null
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => fetchJobs(), 400)
}

const countByStatus = (status) => {
  return jobs.value.filter(j => j.statut === status).length
}

const fetchJobs = async () => {
  loading.value = true
  error.value = ''

  try {
    const params = {}
    if (searchQuery.value) params.mots_cles = searchQuery.value
    if (selectedStatus.value) params.statut = selectedStatus.value

    const response = await api.get('/admin/offres', { params })
    jobs.value = response.data.data || response.data.offres || []
    pagination.value = response.data.pagination || { total: jobs.value.length }
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
    await api.delete(`/admin/offres/${job.id_offre}`)
    jobs.value = jobs.value.filter(j => j.id_offre !== job.id_offre)
  } catch (err) {
    alert('❌ ' + (err.response?.data?.message || 'Erreur lors de la suppression.'))
  }
}

const formatContract = (type) => {
  const types = { 'cdi': 'CDI', 'cdd': 'CDD', 'stage': 'Stage', 'freelance': 'Freelance', 'alternance': 'Alternance' }
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
    'fermee': 'bg-red-100 text-red-700',
  }
  return badges[status] || 'bg-gray-100 text-gray-700'
}

onMounted(() => {
  fetchJobs()
})

// Fiche détaillée ouverte par le bouton « Voir ».
const fiche = ref(null)
const dateFr = (d) => (d ? new Date(d).toLocaleDateString('fr-FR') : '')
const construireFiche = (job) => ({
  titre: job.titre,
  lignes: [
    { label: 'Entreprise', valeur: job.departement?.entreprise?.nom },
    { label: 'Département', valeur: job.departement?.nom },
    { label: 'Localisation', valeur: job.localisation },
    { label: 'Contrat', valeur: job.type_contrat_libelle },
    { label: 'Salaire', valeur: job.salaire ? `${new Intl.NumberFormat('fr-FR').format(job.salaire)} DH` : '' },
    { label: 'Expérience min.', valeur: `${job.experience_min ?? 0} an(s)` },
    { label: "Niveau d'études", valeur: job.niveau_etude_libelle },
    { label: 'Statut', valeur: job.statut_libelle },
    { label: 'Publiée le', valeur: dateFr(job.date_publication) },
    { label: 'Expire le', valeur: dateFr(job.date_expiration) },
    { label: 'Candidatures', valeur: job.nombre_candidatures },
    { label: 'Compétences', valeur: (job.competences || []).map(x => `${x.nom} (${x.niveau_requis_libelle}, ${x.importance_libelle})`).join('\n') },
    { label: 'Description', valeur: job.description },
  ],
})
</script>