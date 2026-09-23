<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Ajouter un recruteur" 
    page-subtitle="Créez un nouveau profil recruteur sur la plateforme"
  >
    <div class="max-w-4xl mx-auto">
      
      <!-- Info mode simulation -->
      <div v-if="USE_MOCK" class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-xl">
        <p class="text-xs text-amber-700">
          🧪 <strong>Mode simulation</strong> — Le backend n'est pas encore branché. Les données ne sont pas réellement sauvegardées.
        </p>
      </div>

      <!-- Carte du Formulaire -->
      <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
        
        <!-- Message de succès -->
        <div v-if="successMessage" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl">
          <p class="text-sm text-green-700">✅ {{ successMessage }}</p>
        </div>

        <!-- Message d'erreur -->
        <div v-if="error" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
          <p class="text-sm text-red-700">⚠️ {{ error }}</p>
        </div>

        <form @submit.prevent="addRecruiter" class="space-y-6">
          
          <!-- Section 1: Informations Personnelles -->
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <span class="bg-blue-100 text-blue-600 p-1.5 rounded-lg text-sm">👤</span>
              Informations Personnelles
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Prénom *</label>
                <input type="text" v-model="form.prenom" required placeholder="Ex: Sophie" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nom *</label>
                <input type="text" v-model="form.nom" required placeholder="Ex: Martin" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email professionnel *</label>
                <input type="email" v-model="form.email" required placeholder="sophie@entreprise.com" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Téléphone</label>
                <input type="tel" v-model="form.telephone" placeholder="+33 6 12 34 56 78" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
              </div>
            </div>
          </div>

          <div class="border-t border-gray-100"></div>

          <!-- Section 2: Détails Professionnels -->
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <span class="bg-purple-100 text-purple-600 p-1.5 rounded-lg text-sm">🏢</span>
              Détails de l'entreprise
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nom de l'entreprise</label>
                <input type="text" v-model="form.entreprise" placeholder="Ex: TechCorp" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Poste / Titre</label>
                <input type="text" v-model="form.poste" placeholder="Ex: Responsable Recrutement" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Localisation</label>
                <input type="text" v-model="form.localisation" placeholder="Ex: Paris, France" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Statut initial</label>
                <select v-model="form.statut" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white">
                  <option value="actif">Actif</option>
                  <option value="en_attente">En attente</option>
                  <option value="inactif">Inactif</option>
                </select>
              </div>
            </div>
          </div>

          <div class="border-t border-gray-100"></div>

          <!-- Section 3: Informations Complémentaires -->
          <div>
             <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <span class="bg-green-100 text-green-600 p-1.5 rounded-lg text-sm">📋</span>
              Informations complémentaires
            </h3>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Secteur d'activité</label>
              <input type="text" v-model="form.secteur" placeholder="Ex: Informatique, Santé, Finance..." class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end gap-4 pt-4">
            <button type="button" @click="$router.back()" class="px-6 py-3 text-gray-600 font-medium hover:bg-gray-50 rounded-xl transition-all">
              Annuler
            </button>
            <button type="submit" :disabled="saving" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-semibold rounded-xl transition-all shadow-sm hover:shadow-lg hover:shadow-blue-200 flex items-center gap-2">
              <span v-if="!saving">+</span>
              {{ saving ? 'Enregistrement...' : 'Ajouter le recruteur' }}
            </button>
          </div>

        </form>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

// CONFIGURATION
// Passer à `false` quand le backend sera prêt
const USE_MOCK = true

const router = useRouter()
const authStore = useAuthStore()

const userName = computed(() => {
  const user = authStore.user
  return user?.prenom ? `${user.prenom} ${user.nom || ''}`.trim() : 'Admin'
})

const saving = ref(false)
const error = ref('')
const successMessage = ref('')

// Données du formulaire
const form = reactive({
  prenom: '',
  nom: '',
  email: '',
  telephone: '',
  entreprise: '',
  poste: '',
  localisation: '',
  statut: 'actif',
  secteur: ''
})

const addRecruiter = async () => {
  saving.value = true
  error.value = ''
  successMessage.value = ''

  try {
    let response

    if (USE_MOCK) {
      // 🧪 MODE SIMULATION (sans backend)
      await new Promise(resolve => setTimeout(resolve, 800))
      response = {
        data: {
          success: true,
          message: 'Recruteur ajouté avec succès (simulation)',
          data: { id: Date.now(), ...form }
        }
      }
    } else {
      //  VRAI APPEL API (quand le backend sera prêt)
      response = await api.post('/admin/recruteurs', form)
    }

    successMessage.value = response.data.message || 'Recruteur ajouté avec succès !'

    // Redirection après 1.5 seconde
    setTimeout(() => {
      router.push('/admin/recruiters')
    }, 1500)

  } catch (err) {
    console.error('Erreur lors de l\'ajout:', err)
    error.value = err.response?.data?.message || 'Erreur lors de l\'ajout du recruteur.'
  } finally {
    saving.value = false
  }
}
</script>