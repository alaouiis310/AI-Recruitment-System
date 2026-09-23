<template>
  <DashboardLayout
    :user-name="userName"
    user-role="Recruteur"
    page-title="Modifier l'offre"
    page-subtitle="Mettez à jour votre offre, ou fermez-la aux nouvelles candidatures"
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
      <div v-if="chargement" class="text-center py-12 text-gray-400">Chargement de l'offre...</div>

      <template v-else>
        <div v-if="messageErreur" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
          {{ messageErreur }}
        </div>

        <div v-if="offre" class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
          <FormulaireOffre
            :offre="offre"
            :id-entreprise="idEntreprise"
            :envoi-en-cours="envoi"
            :erreurs="erreurs"
            libelle-bouton="Enregistrer les modifications"
            @soumettre="enregistrer"
            @annuler="router.push('/recruiter/jobs')"
          />
        </div>
      </template>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import SidebarItem from '../components/SidebarItem.vue'
import FormulaireOffre from '../components/FormulaireOffre.vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const userName = computed(() => {
  const user = authStore.user
  return user?.prenom ? `${user.prenom} ${user.nom || ''}`.trim() : 'Recruteur'
})

const idEntreprise = computed(() => authStore.user?.profil_recruteur?.entreprise?.id_entreprise || null)

const offre = ref(null)
const chargement = ref(true)
const envoi = ref(false)
const erreurs = ref({})
const messageErreur = ref('')

onMounted(async () => {
  try {
    const response = await api.get(`/offres/${route.params.id}`)
    offre.value = response.data.offre
  } catch (err) {
    messageErreur.value = err.response?.status === 404
      ? "Cette offre n'existe pas."
      : (err.response?.data?.message || "Impossible de charger l'offre.")
  } finally {
    chargement.value = false
  }
})

// RG12/RG13 : seul le recruteur qui a publié l'offre peut la modifier (403 sinon).
const enregistrer = async (payload) => {
  envoi.value = true
  erreurs.value = {}
  messageErreur.value = ''
  try {
    await api.patch(`/recruteur/offres/${route.params.id}`, payload)
    router.push('/recruiter/jobs')
  } catch (err) {
    if (err.response?.status === 422) {
      erreurs.value = err.response.data.errors || {}
      messageErreur.value = 'Vérifiez les champs signalés.'
    } else if (err.response?.status === 403) {
      messageErreur.value = "Vous ne pouvez modifier que les offres que vous avez publiées."
    } else {
      messageErreur.value = err.response?.data?.message || "L'enregistrement a échoué. Réessayez."
    }
  } finally {
    envoi.value = false
  }
}
</script>
