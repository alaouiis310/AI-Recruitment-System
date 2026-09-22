<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Candidat" 
    page-title="Mon CV" 
    page-subtitle="Gérez votre CV et vos compétences"
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

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="text-center">
        <div class="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p class="text-gray-500 mt-4 text-sm">Chargement...</p>
      </div>
    </div>

    <!-- Contenu -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Upload CV -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 mb-4">📄 Mon CV</h3>
        
        <!-- Input caché -->
        <input 
          ref="fileInput"
          type="file" 
          accept=".pdf,.doc,.docx"
          @change="uploadCV"
          class="hidden"
        >

        <!-- Zone de drop -->
        <div 
          @click="$refs.fileInput.click()"
          class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-blue-400 transition-all cursor-pointer"
        >
          <span class="text-4xl block mb-2">📤</span>
          <p class="text-gray-600 text-sm">Glissez-déposez votre CV ici</p>
          <p class="text-gray-400 text-xs mt-1">ou cliquez pour parcourir</p>
          <p class="text-gray-400 text-xs mt-2">Formats : PDF, DOC, DOCX (max 10 Mo)</p>
        </div>

        <!-- CV actuel -->
        <div v-if="cvUrl" class="mt-4 p-3 bg-green-50 rounded-xl">
          <p class="text-sm text-green-700">✅ CV actuel : {{ cvFileName }}</p>
          <div class="flex gap-2 mt-2">
            <a 
              :href="cvUrl" 
              target="_blank"
              class="flex-1 text-center px-3 py-1.5 bg-white border border-green-200 text-green-700 rounded-lg text-xs font-medium hover:bg-green-100 transition-all"
            >
              Voir
            </a>
            <button 
              @click="deleteCV"
              class="flex-1 px-3 py-1.5 bg-white border border-red-200 text-red-500 rounded-lg text-xs font-medium hover:bg-red-50 transition-all"
            >
              Supprimer
            </button>
          </div>
        </div>
      </div>

      <!-- Compétences -->
      <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 mb-4">🛠️ Mes compétences</h3>

        <!-- Loading compétences -->
        <div v-if="loadingSkills" class="text-center py-4">
          <div class="w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
        </div>

        <!-- Liste des compétences -->
        <div v-else class="flex flex-wrap gap-2 mb-4">
          <span 
            v-for="skill in skills" 
            :key="skill.id" 
            class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-full text-sm"
          >
            {{ skill.nom || skill.competence?.nom }}
            <span class="text-xs text-blue-400">({{ skill.niveau }})</span>
            <button 
              @click="removeSkill(skill)"
              class="text-blue-300 hover:text-red-400 ml-1"
            >
              ✕
            </button>
          </span>
          <p v-if="skills.length === 0" class="text-sm text-gray-400">
            Aucune compétence déclarée
          </p>
        </div>

        <!-- Ajouter compétence -->
        <div class="flex gap-2">
          <input 
            type="text" 
            v-model="newSkill" 
            placeholder="Ajouter une compétence..."
            @keydown.enter="addSkill"
            class="flex-1 px-4 py-2 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all text-sm"
          >
          <select 
            v-model="newSkillLevel"
            class="px-4 py-2 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white text-sm"
          >
            <option value="debutant">Débutant</option>
            <option value="intermediaire">Intermédiaire</option>
            <option value="avance">Avancé</option>
            <option value="expert">Expert</option>
          </select>
          <button 
            @click="addSkill"
            :disabled="!newSkill.trim()"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-xl transition-all text-sm font-medium"
          >
            Ajouter
          </button>
        </div>
      </div>
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
const loadingSkills = ref(false)
const fileInput = ref(null)
const cvUrl = ref('')
const cvFileName = ref('')
const skills = ref([])
const newSkill = ref('')
const newSkillLevel = ref('intermediaire')

const applicationsCount = ref(0)
const jobsCount = ref(0)
const interviewsCount = ref(0)
const savedCount = ref(0)

const fetchData = async () => {
  loading.value = true
  try {
    // Récupérer le profil avec le CV
    const profileResponse = await api.get('/auth/moi')
    const user = profileResponse.data.user || profileResponse.data

    if (user.candidat?.cv_pdf) {
      const baseUrl = (import.meta.env.VITE_API_URL || 'http://localhost:8000').replace(/\/api$/, '')
      cvUrl.value = `${baseUrl}/storage/${user.candidat.cv_pdf}`
      cvFileName.value = user.candidat.cv_pdf.split('/').pop()
    }

    // Récupérer les compétences
    await fetchSkills()

  } catch (err) {
    console.error('Erreur:', err)
  } finally {
    loading.value = false
  }
}

const fetchSkills = async () => {
  loadingSkills.value = true
  try {
    const response = await api.get('/candidat/competences')
    skills.value = response.data.data || response.data.competences || response.data || []
  } catch (err) {
    console.error('Erreur compétences:', err)
  } finally {
    loadingSkills.value = false
  }
}

const uploadCV = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  // Validation
  if (file.size > 10 * 1024 * 1024) {
    alert('❌ Le fichier ne doit pas dépasser 10 Mo')
    return
  }

  const formData = new FormData()
  formData.append('cv', file)

  try {
    const response = await api.post('/candidat/cv', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    const baseUrl = (import.meta.env.VITE_API_URL || 'http://localhost:8000').replace(/\/api$/, '')
    cvUrl.value = `${baseUrl}/storage/${response.data.cv_pdf || response.data.data?.cv_pdf}`
    cvFileName.value = file.name
    alert('✅ CV téléchargé avec succès !')
  } catch (err) {
    console.error(err)
    alert('❌ Erreur lors du téléchargement du CV.')
  } finally {
    if (fileInput.value) fileInput.value.value = ''
  }
}

const deleteCV = async () => {
  if (!confirm('Supprimer votre CV ?')) return
  try {
    await api.delete('/candidat/cv')
    cvUrl.value = ''
    cvFileName.value = ''
    alert('✅ CV supprimé.')
  } catch (err) {
    alert('❌ Erreur lors de la suppression.')
  }
}

const addSkill = async () => {
  if (!newSkill.value.trim()) return

  try {
    await api.post('/candidat/competences', {
      nom: newSkill.value.trim(),
      niveau: newSkillLevel.value,
    })
    newSkill.value = ''
    await fetchSkills()
  } catch (err) {
    alert('❌ Erreur lors de l\'ajout de la compétence.')
  }
}

const removeSkill = async (skill) => {
  if (!confirm('Retirer cette compétence ?')) return
  try {
    await api.delete(`/candidat/competences/${skill.id}`)
    await fetchSkills()
  } catch (err) {
    alert('❌ Erreur lors de la suppression.')
  }
}

onMounted(() => {
  fetchData()
})
</script>