<template>
  <DashboardLayout
    :user-name="userName"
    user-role="Recruteur"
    page-title="Publier une offre"
    page-subtitle="Créez une nouvelle offre dans l'un de vos départements"
  >
    <template #menu>
      <SidebarItem to="/recruiter" icon="🏠" label="Accueil" />
      <SidebarItem to="/recruiter/jobs" icon="💼" label="Mes offres" />
      <SidebarItem to="/recruiter/candidates" icon="🧑‍💻" label="Candidats" />
      <SidebarItem to="/recruiter/search" icon="🔍" label="Rechercher" />
      <SidebarItem to="/recruiter/ai-helper" icon="🤖" label="AI Helper" />
      <SidebarItem to="/recruiter/messages" icon="💬" label="Messages" />
    </template>

    <div class="max-w-4xl mx-auto">
      <div v-if="messageErreur" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
        {{ messageErreur }}
      </div>

      <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
        <FormulaireOffre
          :id-entreprise="idEntreprise"
          :envoi-en-cours="envoi"
          :erreurs="erreurs"
          @soumettre="publier"
          @annuler="router.push('/recruiter/jobs')"
        />
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import SidebarItem from '../components/SidebarItem.vue'
import FormulaireOffre from '../components/FormulaireOffre.vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const userName = computed(() => {
  const user = authStore.user
  return user?.prenom ? `${user.prenom} ${user.nom || ''}`.trim() : 'Recruteur'
})

// RG7/RG9 : le recruteur publie dans les départements de sa propre entreprise.
const idEntreprise = computed(() => authStore.user?.profil_recruteur?.entreprise?.id_entreprise || null)

const envoi = ref(false)
const erreurs = ref({})
const messageErreur = ref('')

const publier = async (payload) => {
  envoi.value = true
  erreurs.value = {}
  messageErreur.value = ''
  try {
    await api.post('/recruteur/offres', payload)
    router.push('/recruiter/jobs')
  } catch (err) {
    if (err.response?.status === 422) {
      erreurs.value = err.response.data.errors || {}
      messageErreur.value = 'Vérifiez les champs signalés.'
    } else {
      messageErreur.value = err.response?.data?.message || "La publication a échoué. Réessayez."
    }
  } finally {
    envoi.value = false
  }
}
</script>
