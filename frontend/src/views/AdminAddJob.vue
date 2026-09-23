<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Publier une offre" 
    page-subtitle="Créez une offre au nom d'un recruteur"
  >
    <template #menu>
      <SidebarItem to="/admin" icon="🏠" label="Accueil" />
      <SidebarItem to="/admin/recruiters" icon="👥" label="Recruteurs" />
      <SidebarItem to="/admin/candidates" icon="👨‍💼" label="Candidats" />
      <SidebarItem to="/admin/jobs" icon="💼" label="Offres d'emploi" active />
      <SidebarItem to="/admin/applications" icon="📝" label="Candidatures" />
      <SidebarItem to="/admin/analytics" icon="📊" label="Analytiques" />
      <SidebarItem to="/admin/settings" icon="⚙️" label="Paramètres" />
      <SidebarItem to="/admin/profile" icon="👤" label="Mon profil" />
    </template>

    <div class="max-w-4xl mx-auto">
      <div v-if="messageErreur" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
        {{ messageErreur }}
      </div>

      <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
        <FormulaireOffre
          v-if="recruteurs"
          :recruteurs="recruteurs"
          :envoi-en-cours="envoi"
          :erreurs="erreurs"
          @soumettre="publishJob"
          @annuler="router.push('/admin/jobs')"
        />
        <p v-else class="text-center py-8 text-gray-400">Chargement des recruteurs...</p>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import SidebarItem from '../components/SidebarItem.vue'
import FormulaireOffre from '../components/FormulaireOffre.vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()
const userName = computed(() => authStore.user?.nom_complet || 'Administrateur')

const recruteurs = ref(null)
const envoi = ref(false)
const erreurs = ref({})
const messageErreur = ref('')

// RG13 : l'offre est publiée au nom d'un recruteur, dans un département de son entreprise.
const publishJob = async (payload) => {
  envoi.value = true
  erreurs.value = {}
  messageErreur.value = ''
  try {
    await api.post('/admin/offres', payload)
    router.push('/admin/jobs')
  } catch (err) {
    if (err.response?.status === 422) {
      erreurs.value = err.response.data.errors || {}
      messageErreur.value = 'Vérifiez les champs signalés.'
    } else {
      messageErreur.value = err.response?.data?.message || 'La publication a échoué. Réessayez.'
    }
  } finally {
    envoi.value = false
  }
}

onMounted(async () => {
  try {
    const response = await api.get('/admin/recruteurs', { params: { per_page: 100 } })
    recruteurs.value = response.data.recruteurs || []
  } catch (err) {
    recruteurs.value = []
    messageErreur.value = 'Impossible de charger la liste des recruteurs.'
  }
})
</script>
