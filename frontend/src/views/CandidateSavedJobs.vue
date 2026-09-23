<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Candidat" 
    page-title="Offres sauvegardées" 
    page-subtitle="Retrouvez toutes les offres que vous avez mises de côté"
  >
    <!-- Menu -->
    <template #menu>
      <SidebarItem to="/candidate" icon="🏠" label="Accueil" />
      <SidebarItem to="/candidate/applications" icon="📝" label="Mes candidatures" :badge="applicationsCount" />
      <SidebarItem to="/candidate/jobs" icon="💼" label="Offres d'emploi" :badge="jobsCount" />
      <SidebarItem to="/candidate/interviews" icon="🗓️" label="Mes entretiens" :badge="interviewsCount" />
      <SidebarItem to="/candidate/saved" icon="⭐" label="Offres sauvegardées" :badge="savedCount" active />
      <SidebarItem to="/candidate/profile" icon="👤" label="Mon profil" />
      <SidebarItem to="/candidate/cv" icon="📄" label="Mon CV" />
    </template>

    <!-- Header Actions -->
    <template #header-actions>
      <div class="flex items-center gap-3">
        <span class="text-sm text-gray-500">{{ savedCount }} offres sauvegardées</span>
        <button @click="exporter" :disabled="savedJobs.length === 0" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 disabled:opacity-50 text-gray-700 text-sm font-medium rounded-xl transition-all">
          Exporter
        </button>
      </div>
    </template>

    <!-- Filtres -->
    <div class="flex flex-wrap items-center gap-3 mb-6">
      <button 
        v-for="filter in filters" 
        :key="filter.value"
        @click="activeFilter = filter.value"
        :class="[
          'px-4 py-2 rounded-xl text-sm font-medium transition-all',
          activeFilter === filter.value 
            ? 'bg-blue-600 text-white shadow-sm' 
            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
        ]"
      >
        {{ filter.label }}
        <span class="ml-1 text-xs opacity-70">({{ filter.count }})</span>
      </button>
    </div>

    <!-- Liste des offres sauvegardées -->
    <div v-if="filteredSavedJobs.length > 0" class="grid grid-cols-1 gap-4">
      <div v-for="job in filteredSavedJobs" :key="job.id" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-200">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <!-- Info offre -->
          <div class="flex-1">
            <div class="flex items-start gap-3">
              <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-xl shrink-0">
                💼
              </div>
              <div>
                <h3 class="font-semibold text-gray-800">{{ job.title }}</h3>
                <p class="text-sm text-gray-500">{{ job.company }}</p>
                <div class="flex flex-wrap items-center gap-3 mt-1 text-xs text-gray-400">
                  <span>📍 {{ job.location }}</span>
                  <span>•</span>
                  <span>💰 {{ job.salary }}</span>
                  <span>•</span>
                  <span>📅 {{ job.contract }}</span>
                  <span>•</span>
                  <span>Sauvegardée il y a {{ job.savedAt }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-2 shrink-0">
            <button @click="postuler(job)" :disabled="applying === job.id" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
              {{ applying === job.id ? 'Envoi...' : 'Postuler' }}
            </button>
            <button @click="copier(job)" aria-label="Copier l'offre" class="px-3 py-2 border border-gray-200 hover:bg-gray-50 rounded-xl transition-all text-gray-500">
              <span class="text-sm">📋</span>
            </button>
            <button @click="removeSaved(job.id)" class="px-3 py-2 border border-red-200 hover:bg-red-50 rounded-xl transition-all text-red-400 hover:text-red-500">
              <span class="text-sm">🗑️</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Message vide -->
    <div v-else class="text-center py-16 bg-white rounded-2xl shadow-sm border border-gray-100">
      <p class="text-6xl mb-4">⭐</p>
      <h3 class="text-xl font-semibold text-gray-800">Aucune offre sauvegardée</h3>
      <p class="text-gray-500 mt-2">Commencez à sauvegarder des offres qui vous intéressent !</p>
      <router-link to="/candidate/jobs" class="inline-block mt-4 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
        Voir les offres
      </router-link>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import SidebarItem from '../components/SidebarItem.vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
import { offresSauvegardees } from '../services/offresSauvegardees'

const authStore = useAuthStore()
const userName = computed(() => {
  const user = authStore.user
  return user?.prenom ? `${user.prenom} ${user.nom || ''}`.trim() : 'Candidat'
})
const applicationsCount = ref(0)
const jobsCount = ref(0)
const interviewsCount = ref(0)
const savedCount = computed(() => savedJobs.value.length)

const activeFilter = ref('all')
const savedJobs = ref([])
const applying = ref(null)

const filters = computed(() => {
  const types = [...new Set(savedJobs.value.map(j => j.contract))]
  return [
    { label: 'Toutes', value: 'all', count: savedJobs.value.length },
    ...types.map(t => ({ label: t, value: t, count: savedJobs.value.filter(j => j.contract === t).length })),
  ]
})

const filteredSavedJobs = computed(() => {
  if (activeFilter.value === 'all') return savedJobs.value
  return savedJobs.value.filter(job => job.contract === activeFilter.value)
})

const ilYA = (iso) => {
  const jours = Math.max(0, Math.floor((Date.now() - new Date(iso)) / 86400000))
  return jours === 0 ? "aujourd'hui" : `${jours}j`
}

// Les offres sont lues depuis l'API : une offre fermée ou expirée n'y figure
// plus (RG17, RG18) et disparaît donc des sauvegardes.
const chargerSauvegardes = async () => {
  const ids = offresSauvegardees.lire()
  if (ids.length === 0) {
    savedJobs.value = []
    return
  }
  try {
    const response = await api.get('/offres', { params: { per_page: 100 } })
    savedJobs.value = (response.data.offres || [])
      .filter(o => ids.includes(o.id_offre))
      .map(o => ({
        id: o.id_offre,
        title: o.titre,
        company: o.departement?.entreprise?.nom || '',
        location: o.localisation,
        salary: o.salaire ? `${new Intl.NumberFormat('fr-FR').format(o.salaire)} DH` : 'Non précisé',
        contract: o.type_contrat_libelle,
        savedAt: ilYA(offresSauvegardees.dateDe(o.id_offre)),
      }))
  } catch (err) {
    console.error('Erreur offres sauvegardées:', err)
  }
}

const removeSaved = (id) => {
  if (!confirm('Supprimer cette offre des sauvegardes ?')) return
  offresSauvegardees.retirer(id)
  savedJobs.value = savedJobs.value.filter(job => job.id !== id)
}

const postuler = async (job) => {
  if (!confirm(`Postuler pour "${job.title}" ?`)) return
  applying.value = job.id
  try {
    await api.post('/candidat/candidatures', { id_offre: job.id })
    alert('✅ Candidature envoyée avec succès !')
  } catch (err) {
    alert('❌ ' + (err.response?.data?.message || "Erreur lors de l'envoi de la candidature."))
  } finally {
    applying.value = null
  }
}

const copier = async (job) => {
  const texte = `${job.title} — ${job.company} — ${job.location} (${job.contract})`
  try {
    await navigator.clipboard.writeText(texte)
    alert('📋 Offre copiée dans le presse-papiers.')
  } catch {
    alert(texte)
  }
}

// Export CSV des offres sauvegardées (séparateur « ; » pour Excel en français).
const exporter = () => {
  const echapper = (v) => `"${String(v ?? '').replace(/"/g, '""')}"`
  const lignes = [
    ['Offre', 'Entreprise', 'Localisation', 'Contrat', 'Salaire'],
    ...savedJobs.value.map(j => [j.title, j.company, j.location, j.contract, j.salary]),
  ].map(l => l.map(echapper).join(';'))
  const blob = new Blob(['\ufeff' + lignes.join('\r\n')], { type: 'text/csv;charset=utf-8' })
  const lien = document.createElement('a')
  lien.href = URL.createObjectURL(blob)
  lien.download = 'offres-sauvegardees.csv'
  lien.click()
  URL.revokeObjectURL(lien.href)
}

onMounted(chargerSauvegardes)
</script>
