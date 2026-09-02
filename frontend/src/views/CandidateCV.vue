<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Candidat" 
    page-title="Mon CV" 
    page-subtitle="Gérez votre CV et vos compétences"
  >
    <!-- Menu -->
    <template #menu>
      <SidebarItem to="/candidate" icon="🏠" label="Accueil" />
      <SidebarItem to="/candidate/applications" icon="📝" label="Mes candidatures" :badge="applicationsCount" />
      <SidebarItem to="/candidate/jobs" icon="💼" label="Offres d'emploi" :badge="jobsCount" />
      <SidebarItem to="/candidate/interviews" icon="🗓️" label="Mes entretiens" :badge="interviewsCount" />
      <SidebarItem to="/candidate/saved" icon="⭐" label="Offres sauvegardées" :badge="savedCount" />
      <SidebarItem to="/candidate/profile" icon="👤" label="Mon profil" />
      <SidebarItem to="/candidate/cv" icon="📄" label="Mon CV" active />
    </template>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Upload CV -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 mb-4">📄 Télécharger mon CV</h3>
        <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-blue-400 transition-all cursor-pointer">
          <span class="text-4xl block mb-2">📤</span>
          <p class="text-gray-600 text-sm">Glissez-déposez votre CV ici</p>
          <p class="text-gray-400 text-xs mt-1">ou cliquez pour parcourir</p>
          <p class="text-gray-400 text-xs mt-2">Formats acceptés : PDF, DOC, DOCX</p>
        </div>
        <div class="mt-4 p-3 bg-green-50 rounded-xl">
          <p class="text-sm text-green-700">✅ CV actuel : CV_Yassine_2024.pdf</p>
          <p class="text-xs text-green-600">Téléchargé le 15/05/2024</p>
        </div>
        <button class="w-full mt-4 py-2 text-sm text-red-500 hover:text-red-600 font-medium">Supprimer le CV</button>
      </div>

      <!-- Compétences -->
      <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 mb-4">🛠️ Mes compétences</h3>
        <div class="flex flex-wrap gap-2 mb-4">
          <span v-for="skill in skills" :key="skill.name" class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-full text-sm">
            {{ skill.name }}
            <span class="text-xs text-blue-400">({{ skill.level }})</span>
            <button class="text-blue-300 hover:text-red-400 ml-1">✕</button>
          </span>
        </div>
        <div class="flex gap-2">
          <input type="text" v-model="newSkill" placeholder="Ajouter une compétence..." class="flex-1 px-4 py-2 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all text-sm">
          <select v-model="newSkillLevel" class="px-4 py-2 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white text-sm">
            <option value="Débutant">Débutant</option>
            <option value="Intermédiaire">Intermédiaire</option>
            <option value="Avancé">Avancé</option>
            <option value="Expert">Expert</option>
          </select>
          <button @click="addSkill" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-all text-sm font-medium">
            Ajouter
          </button>
        </div>

        <hr class="my-6 border-gray-100">

        <h3 class="font-semibold text-gray-800 mb-4">📊 Analyse IA de votre CV</h3>
        <div class="space-y-3">
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Complétude du CV</span>
            <span class="font-medium text-green-600">85%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="h-2 rounded-full bg-green-500" style="width: 85%"></div>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Mots-clés détectés</span>
            <span class="font-medium text-blue-600">24</span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Score de compatibilité</span>
            <span class="font-medium text-amber-600">72%</span>
          </div>
        </div>
        <button class="w-full mt-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl text-sm font-medium transition-all">
          🔄 Analyser mon CV avec l'IA
        </button>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import SidebarItem from '../components/SidebarItem.vue'

const userName = ref('Yassine')
const applicationsCount = ref(12)
const jobsCount = ref(24)
const interviewsCount = ref(2)
const savedCount = ref(5)

const newSkill = ref('')
const newSkillLevel = ref('Intermédiaire')

const skills = ref([
  { name: 'JavaScript', level: 'Avancé' },
  { name: 'Vue.js', level: 'Intermédiaire' },
  { name: 'Laravel', level: 'Intermédiaire' },
  { name: 'Python', level: 'Débutant' },
  { name: 'SQL', level: 'Avancé' },
  { name: 'Docker', level: 'Débutant' },
])

const addSkill = () => {
  if (newSkill.value.trim()) {
    skills.value.push({ name: newSkill.value.trim(), level: newSkillLevel.value })
    newSkill.value = ''
  }
}
</script>
