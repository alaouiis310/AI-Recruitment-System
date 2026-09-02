<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Candidat" 
    page-title="Offres d'emploi" 
    page-subtitle="Découvrez les meilleures opportunités"
  >
    <!-- Menu -->
    <template #menu>
      <SidebarItem to="/candidate" icon="🏠" label="Accueil" />
      <SidebarItem to="/candidate/applications" icon="📝" label="Mes candidatures" :badge="applicationsCount" />
      <SidebarItem to="/candidate/jobs" icon="💼" label="Offres d'emploi" :badge="jobsCount" active />
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
          class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
        >
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
      </div>
      <div class="flex gap-3">
        <select v-model="selectedContract" class="px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white text-gray-700 text-sm">
          <option value="">Tous les contrats</option>
          <option value="CDI">CDI</option>
          <option value="CDD">CDD</option>
          <option value="Stage">Stage</option>
          <option value="Freelance">Freelance</option>
        </select>
        <select v-model="selectedLocation" class="px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white text-gray-700 text-sm">
          <option value="">Toutes localisations</option>
          <option value="Paris">Paris</option>
          <option value="Lyon">Lyon</option>
          <option value="Bordeaux">Bordeaux</option>
          <option value="Toulouse">Toulouse</option>
        </select>
      </div>
    </div>

    <!-- Résultats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
      <div v-for="job in filteredJobs" :key="job.title" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-200">
        <div class="flex items-start justify-between mb-3">
          <div>
            <h3 class="font-semibold text-gray-800">{{ job.title }}</h3>
            <p class="text-sm text-gray-500">{{ job.company }}</p>
          </div>
          <span class="text-xs font-medium bg-blue-50 text-blue-600 px-2.5 py-1 rounded-full">{{ job.contract }}</span>
        </div>
        
        <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 mb-3">
          <span>📍 {{ job.location }}</span>
          <span>•</span>
          <span>💰 {{ job.salary }}</span>
          <span>•</span>
          <span>📅 Publiée il y a {{ job.posted }}</span>
        </div>

        <div class="flex items-center gap-2 mb-4">
          <span class="text-xs text-gray-400">Match</span>
          <div class="flex-1 bg-gray-200 rounded-full h-1.5">
            <div class="h-1.5 rounded-full bg-green-500" :style="{ width: job.match + '%' }"></div>
          </div>
          <span class="text-xs font-medium text-green-600">{{ job.match }}%</span>
        </div>

        <div class="flex items-center gap-2">
          <button class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all">
            Postuler
          </button>
          <button class="px-4 py-2 border border-gray-200 hover:bg-gray-50 rounded-xl transition-all text-gray-500">
            ⭐
          </button>
        </div>
      </div>
    </div>

    <!-- Aucun résultat -->
    <div v-if="filteredJobs.length === 0" class="text-center py-12">
      <p class="text-4xl mb-4">🔍</p>
      <h3 class="text-lg font-semibold text-gray-800">Aucune offre trouvée</h3>
      <p class="text-sm text-gray-500 mt-1">Essayez de modifier vos filtres de recherche</p>
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

const searchQuery = ref('')
const selectedContract = ref('')
const selectedLocation = ref('')

const jobs = ref([
  { title: 'Développeur Full Stack', company: 'TechNova', contract: 'CDI', location: 'Paris', salary: '45-55K€', posted: '2j', match: 92 },
  { title: 'Data Scientist', company: 'DataMind', contract: 'CDI', location: 'Lyon', salary: '50-60K€', posted: '3j', match: 85 },
  { title: 'UX/UI Designer', company: 'DesignLab', contract: 'CDD', location: 'Bordeaux', salary: '35-42K€', posted: '5j', match: 78 },
  { title: 'Product Owner', company: 'Innovatech', contract: 'CDI', location: 'Paris', salary: '48-58K€', posted: '1j', match: 71 },
  { title: 'DevOps Engineer', company: 'CloudSys', contract: 'Freelance', location: 'Toulouse', salary: '55-65K€', posted: '4j', match: 65 },
  { title: 'Frontend Developer', company: 'WebCorp', contract: 'CDI', location: 'Lyon', salary: '40-50K€', posted: '6j', match: 88 },
])

const filteredJobs = computed(() => {
  return jobs.value.filter(job => {
    const matchSearch = job.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                        job.company.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchContract = !selectedContract.value || job.contract === selectedContract.value
    const matchLocation = !selectedLocation.value || job.location === selectedLocation.value
    return matchSearch && matchContract && matchLocation
  })
})
</script>
