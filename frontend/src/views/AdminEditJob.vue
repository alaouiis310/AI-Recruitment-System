<template>
  <DashboardLayout
    :user-name="userName"
    user-role="Administrateur"
    page-title="Modifier l'offre"
    page-subtitle="Mettez à jour l'offre ou changez son statut"
  >
    <div class="max-w-4xl mx-auto">
      <div v-if="chargement" class="text-center py-12 text-gray-400">Chargement de l'offre...</div>

      <template v-else>
        <div v-if="messageErreur" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
          {{ messageErreur }}
        </div>

        <div v-if="offre" class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
          <!-- L'auteur de l'offre ne change pas : le département reste dans son entreprise (RG9, RG11). -->
          <FormulaireOffre
            :offre="offre"
            :id-entreprise="offre.departement?.id_entreprise || null"
            :envoi-en-cours="envoi"
            :erreurs="erreurs"
            libelle-bouton="Enregistrer les modifications"
            @soumettre="enregistrer"
            @annuler="router.push('/admin/jobs')"
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
import FormulaireOffre from '../components/FormulaireOffre.vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const userName = computed(() => authStore.user?.nom_complet || 'Administrateur')

const offre = ref(null)
const chargement = ref(true)
const envoi = ref(false)
const erreurs = ref({})
const messageErreur = ref('')

onMounted(async () => {
  try {
    const response = await api.get(`/admin/offres/${route.params.id}`)
    offre.value = response.data.offre
  } catch (err) {
    messageErreur.value = err.response?.status === 404
      ? "Cette offre n'existe pas."
      : (err.response?.data?.message || "Impossible de charger l'offre.")
  } finally {
    chargement.value = false
  }
})

const enregistrer = async (payload) => {
  envoi.value = true
  erreurs.value = {}
  messageErreur.value = ''
  try {
    await api.patch(`/admin/offres/${route.params.id}`, payload)
    router.push('/admin/jobs')
  } catch (err) {
    if (err.response?.status === 422) {
      erreurs.value = err.response.data.errors || {}
      messageErreur.value = 'Vérifiez les champs signalés.'
    } else {
      messageErreur.value = err.response?.data?.message || "L'enregistrement a échoué. Réessayez."
    }
  } finally {
    envoi.value = false
  }
}
</script>
