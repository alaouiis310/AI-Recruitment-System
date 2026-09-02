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
        <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-xl transition-all">
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
            <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
              Postuler
            </button>
            <button class="px-3 py-2 border border-gray-200 hover:bg-gray-50 rounded-xl transition-all text-gray-500">
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
import { ref, computed } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import SidebarItem from '../components/SidebarItem.vue'

const userName = ref('Yassine')
const applicationsCount = ref(12)
const jobsCount = ref(24)
const interviewsCount = ref(2)
const savedCount = ref(5)

const activeFilter = ref('all')

const filters = ref([
  { label: 'Toutes', value: 'all', count: 5 },
  { label: 'CDI', value: 'CDI', count: 2 },
  { label: 'CDD', value: 'CDD', count: 1 },
  { label: 'Stage', value: 'Stage', count: 1 },
  { label: 'Freelance', value: 'Freelance', count: 1 },
])

const savedJobs = ref([
  { id: 1, title: 'Développeur Full Stack', company: 'TechNova', location: 'Paris', salary: '45-55K€', contract: 'CDI', savedAt: '2j' },
  { id: 2, title: 'Data Scientist', company: 'DataMind', location: 'Lyon', salary: '50-60K€', contract: 'CDI', savedAt: '3j' },
  { id: 3, title: 'UX/UI Designer', company: 'DesignLab', location: 'Bordeaux', salary: '35-42K€', contract: 'CDD', savedAt: '5j' },
  { id: 4, title: 'DevOps Engineer', company: 'CloudSys', location: 'Toulouse', salary: '55-65K€', contract: 'Freelance', savedAt: '1j' },
  { id: 5, title: 'Product Owner', company: 'Innovatech', location: 'Paris', salary: '48-58K€', contract: 'CDI', savedAt: '4j' },
])

const filteredSavedJobs = computed(() => {
  if (activeFilter.value === 'all') return savedJobs.value
  return savedJobs.value.filter(job => job.contract === activeFilter.value)
})

const removeSaved = (id) => {
  if (confirm('Supprimer cette offre des sauvegardes ?')) {
    const index = savedJobs.value.findIndex(job => job.id === id)
    if (index !== -1) {
      savedJobs.value.splice(index, 1)
      savedCount.value = savedJobs.value.length
    }
  }
}
</script>
