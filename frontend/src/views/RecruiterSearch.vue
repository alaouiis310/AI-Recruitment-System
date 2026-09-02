<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Recruteur" 
    page-title="Rechercher" 
    page-subtitle="Trouvez les meilleurs talents"
  >
    <!-- Menu -->
    <template #menu>
      <SidebarItem to="/recruiter" icon="🏠" label="Accueil" />
      <SidebarItem to="/recruiter/jobs" icon="💼" label="Mes offres" :badge="myJobsCount" />
      <SidebarItem to="/recruiter/candidates" icon="🧑‍💻" label="Candidats" :badge="candidatesCount" />
      <SidebarItem to="/recruiter/search" icon="🔍" label="Rechercher" active />
      <SidebarItem to="/recruiter/ai-helper" icon="🤖" label="AI Helper" />
      <SidebarItem to="/recruiter/messages" icon="💬" label="Messages" :badge="messagesCount" />
    </template>

    <!-- Barre de recherche -->
    <div class="flex flex-col sm:flex-row gap-4 mb-6">
      <div class="flex-1 relative">
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Rechercher par compétences, poste, localisation..." 
          class="w-full pl-11 pr-4 py-3.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all text-sm"
        >
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
      </div>
      <button class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-all shadow-sm hover:shadow-md whitespace-nowrap">
        Rechercher
      </button>
    </div>

    <!-- Filtres avancés -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-6">
      <select v-model="filters.experience" class="px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white text-gray-700 text-sm">
        <option value="">Expérience</option>
        <option value="0-2">0-2 ans</option>
        <option value="3-5">3-5 ans</option>
        <option value="6-10">6-10 ans</option>
        <option value="10+">10+ ans</option>
      </select>
      <select v-model="filters.location" class="px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white text-gray-700 text-sm">
        <option value="">Localisation</option>
        <option value="Paris">Paris</option>
        <option value="Lyon">Lyon</option>
        <option value="Bordeaux">Bordeaux</option>
        <option value="Toulouse">Toulouse</option>
        <option value="Marseille">Marseille</option>
      </select>
      <select v-model="filters.contract" class="px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white text-gray-700 text-sm">
        <option value="">Type de contrat</option>
        <option value="CDI">CDI</option>
        <option value="CDD">CDD</option>
        <option value="Stage">Stage</option>
        <option value="Freelance">Freelance</option>
      </select>
      <select v-model="filters.sort" class="px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white text-gray-700 text-sm">
        <option value="relevance">Pertinence</option>
        <option value="newest">Plus récents</option>
        <option value="oldest">Plus anciens</option>
        <option value="score">Score IA</option>
      </select>
    </div>

    <!-- Résultats -->
    <div class="space-y-3">
      <div v-for="result in searchResults" :key="result.id" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <div class="flex items-center gap-3">
              <img :src="result.avatar" alt="Avatar" class="w-12 h-12 rounded-full border-2 border-blue-100 object-cover">
              <div>
                <h4 class="font-semibold text-gray-800">{{ result.name }}</h4>
                <p class="text-sm text-gray-500">{{ result.job }}</p>
              </div>
            </div>
            <div class="flex flex-wrap items-center gap-3 mt-2 text-xs text-gray-400">
              <span>📍 {{ result.location }}</span>
              <span>•</span>
              <span>🎓 {{ result.education }}</span>
              <span>•</span>
              <span>⏳ {{ result.experience }}</span>
            </div>
            <div class="flex flex-wrap gap-2 mt-2">
              <span v-for="skill in result.skills" :key="skill" class="px-2.5 py-0.5 bg-blue-50 text-blue-600 rounded-full text-xs">
                {{ skill }}
              </span>
            </div>
          </div>
          <div class="flex items-center gap-3 shrink-0">
            <div class="text-center">
              <p class="text-lg font-bold text-green-600">{{ result.match }}%</p>
              <p class="text-xs text-gray-400">Match</p>
            </div>
            <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all">
              Contacter
            </button>
            <button class="px-3 py-2 border border-gray-200 hover:bg-gray-50 rounded-xl transition-all text-gray-500">⭐</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Aucun résultat -->
    <div v-if="searchResults.length === 0" class="text-center py-12 bg-white rounded-2xl border border-gray-100">
      <p class="text-6xl mb-4">🔍</p>
      <h3 class="text-lg font-semibold text-gray-800">Aucun résultat trouvé</h3>
      <p class="text-sm text-gray-500 mt-1">Essayez d'autres mots-clés ou ajustez vos filtres</p>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import SidebarItem from '../components/SidebarItem.vue'

const userName = ref('Sophie Martin')
const myJobsCount = ref(12)
const candidatesCount = ref(86)
const messagesCount = ref(4)

const searchQuery = ref('')
const filters = reactive({
  experience: '',
  location: '',
  contract: '',
  sort: 'relevance'
})

const searchResults = ref([
  { 
    id: 1, name: 'Thomas Leroy', job: 'Développeur Full Stack', location: 'Paris', 
    education: 'Master Informatique', experience: '5 ans', 
    skills: ['JavaScript', 'Vue.js', 'Laravel', 'Docker'],
    match: 92,
    avatar: 'https://ui-avatars.com/api/?name=Thomas+Leroy&background=2563eb&color=fff&size=48'
  },
  { 
    id: 2, name: 'Camille Dubois', job: 'UX/UI Designer', location: 'Lyon', 
    education: 'Master Design', experience: '3 ans', 
    skills: ['Figma', 'Adobe XD', 'UI/UX', 'Prototypage'],
    match: 85,
    avatar: 'https://ui-avatars.com/api/?name=Camille+Dubois&background=7c3aed&color=fff&size=48'
  },
  { 
    id: 3, name: 'Mehdi Amine', job: 'Data Scientist', location: 'Bordeaux', 
    education: 'PhD Data Science', experience: '4 ans', 
    skills: ['Python', 'Machine Learning', 'SQL', 'TensorFlow'],
    match: 78,
    avatar: 'https://ui-avatars.com/api/?name=Mehdi+Amine&background=059669&color=fff&size=48'
  },
])
</script>