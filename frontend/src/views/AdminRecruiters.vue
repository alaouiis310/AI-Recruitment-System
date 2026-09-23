<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Gestion des recruteurs" 
    page-subtitle="Gérez tous les recruteurs de la plateforme"
  >

    <template #header-actions>
  <button 
    @click="$router.push('/admin/recruiters/add')"
    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md"
  >
    + Ajouter un recruteur
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
      <button @click="fetchRecruiters" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700">
        Réessayer
      </button>
    </div>

    <div v-else>
      <!-- Statistiques -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 md:gap-4 mb-6">
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
          <p class="text-2xl font-bold text-gray-800">{{ pagination.total || recruiters.length }}</p>
          <p class="text-xs text-gray-500">Total</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
          <p class="text-2xl font-bold text-green-600">{{ countByStatus('actif') }}</p>
          <p class="text-xs text-gray-500">Actifs</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
          <p class="text-2xl font-bold text-amber-600">{{ countByStatus('suspendu') }}</p>
          <p class="text-xs text-gray-500">Suspendus</p>
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
            placeholder="Rechercher un recruteur..." 
            class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
          >
          <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
        </div>
        <select 
          v-model="selectedStatus" 
          @change="fetchRecruiters"
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
                <th class="px-6 py-3 font-medium text-gray-500">Recruteur</th>
                <th class="px-6 py-3 font-medium text-gray-500">Entreprise</th>
                <th class="px-6 py-3 font-medium text-gray-500">Offres</th>
                <th class="px-6 py-3 font-medium text-gray-500">Statut</th>
                <th class="px-6 py-3 font-medium text-gray-500">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="r in recruiters" 
                :key="r.id_recruteur" 
                class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors"
              >
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <img 
                      :src="getAvatar(r)" 
                      alt="Avatar" 
                      class="w-9 h-9 rounded-full border-2 border-blue-100 object-cover"
                    >
                    <div>
                      <p class="font-medium text-gray-800">{{ r.utilisateur?.prenom }} {{ r.utilisateur?.nom }}</p>
                      <p class="text-xs text-gray-500">{{ r.utilisateur?.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-gray-600">{{ r.entreprise?.nom || 'N/A' }}</td>
                <td class="px-6 py-4 text-gray-600">{{ r.nombre_offres || 0 }}</td>
                <td class="px-6 py-4">
                  <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', getStatusBadge(r.utilisateur?.etat_compte)]">
                    {{ formatStatus(r.utilisateur?.etat_compte) }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <button @click="fiche = construireFiche(r)" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Voir</button>
                    <span class="text-gray-300">|</span>
                    <button 
                      @click="toggleStatus(r)"
                      class="text-amber-600 hover:text-amber-700 text-sm"
                    >
                      {{ r.utilisateur?.etat_compte === 'actif' ? 'Suspendre' : 'Activer' }}
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="recruiters.length === 0">
                <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                  Aucun recruteur
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
const recruiters = ref([])
const pagination = ref({ total: 0 })
const searchQuery = ref('')
const selectedStatus = ref('')

let searchTimeout = null
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => fetchRecruiters(), 400)
}

const countByStatus = (status) => {
  return recruiters.value.filter(r => r.utilisateur?.etat_compte === status).length
}

const getAvatar = (r) => {
  const name = `${r.utilisateur?.prenom || ''}+${r.utilisateur?.nom || ''}`.trim() || 'User'
  return `https://ui-avatars.com/api/?name=${name}&background=2563eb&color=fff&size=36`
}

const fetchRecruiters = async () => {
  loading.value = true
  error.value = ''

  try {
    const params = {}
    if (searchQuery.value) params.recherche = searchQuery.value
    if (selectedStatus.value) params.etat_compte = selectedStatus.value

    const response = await api.get('/admin/recruteurs', { params })
    recruiters.value = response.data.data || response.data.recruteurs || []
    pagination.value = response.data.pagination || { total: recruiters.value.length }
  } catch (err) {
    console.error('Erreur recruteurs:', err)
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

const toggleStatus = async (r) => {
  const newStatus = r.utilisateur?.etat_compte === 'actif' ? 'suspendu' : 'actif'
  if (!confirm(`Changer le statut de ${r.utilisateur?.prenom} à "${formatStatus(newStatus)}" ?`)) return

  try {
    await api.patch(`/admin/utilisateurs/${r.utilisateur.id_user}/etat`, { etat_compte: newStatus })
    r.utilisateur.etat_compte = newStatus
  } catch (err) {
    alert('❌ Erreur lors de la mise à jour.')
  }
}

const openCreateModal = () => {
  router.push('/admin/recruiters/add')
}

const formatStatus = (status) => {
  const labels = { 'actif': 'Actif', 'suspendu': 'Suspendu', 'desactive': 'Désactivé' }
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
  fetchRecruiters()
})

// Fiche détaillée ouverte par le bouton « Voir ».
const fiche = ref(null)
const dateFr = (d) => (d ? new Date(d).toLocaleDateString('fr-FR') : '')
const construireFiche = (r) => ({
  titre: r.utilisateur?.nom_complet || 'Recruteur',
  lignes: [
    { label: 'Email', valeur: r.utilisateur?.email },
    { label: 'Téléphone', valeur: r.telephone },
    { label: 'Poste', valeur: r.poste },
    { label: 'Entreprise', valeur: r.entreprise?.nom },
    { label: 'Ville', valeur: r.entreprise?.ville },
    { label: 'Offres publiées', valeur: r.nombre_offres },
    { label: 'État du compte', valeur: r.utilisateur?.etat_compte },
  ],
})
</script>