<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Ajouter un candidat" 
    page-subtitle="Créez un nouveau profil candidat sur la plateforme"
  >
    <template #menu>
      <SidebarItem to="/admin" icon="🏠" label="Accueil" />
      <SidebarItem to="/admin/recruiters" icon="👥" label="Recruteurs" />
      <SidebarItem to="/admin/candidates" icon="👨‍💼" label="Candidats" active />
      <SidebarItem to="/admin/jobs" icon="💼" label="Offres d'emploi" />
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
        <form @submit.prevent="addCandidate" class="space-y-6">

          <!-- Section 1 : informations personnelles -->
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <span class="bg-blue-100 text-blue-600 p-1.5 rounded-lg text-sm">👤</span>
              Informations personnelles
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Prénom</label>
                <input type="text" v-model="form.prenom" placeholder="Ex : Youssef" :class="champ('prenom')">
                <p v-if="erreur('prenom')" class="mt-1 text-xs text-red-600">{{ erreur('prenom') }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nom</label>
                <input type="text" v-model="form.nom" placeholder="Ex : Alami" :class="champ('nom')">
                <p v-if="erreur('nom')" class="mt-1 text-xs text-red-600">{{ erreur('nom') }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                <input type="email" v-model="form.email" placeholder="youssef@email.ma" :class="champ('email')">
                <p v-if="erreur('email')" class="mt-1 text-xs text-red-600">{{ erreur('email') }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Téléphone (optionnel)</label>
                <input type="tel" v-model="form.telephone" placeholder="06 12 34 56 78" :class="champ('telephone')">
                <p v-if="erreur('telephone')" class="mt-1 text-xs text-red-600">{{ erreur('telephone') }}</p>
              </div>
            </div>
          </div>

          <div class="border-t border-gray-100"></div>

          <!-- Section 2 : profil -->
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <span class="bg-purple-100 text-purple-600 p-1.5 rounded-lg text-sm">💼</span>
              Profil
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Diplôme (optionnel)</label>
                <input type="text" v-model="form.diplome" placeholder="Ex : Diplôme d'ingénieur en informatique" :class="champ('diplome')">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Adresse (optionnel)</label>
                <input type="text" v-model="form.adresse" placeholder="Ex : Quartier Iberia, Tanger" :class="champ('adresse')">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Expérience totale (années)</label>
                <input type="number" min="0" max="60" step="0.5" v-model="form.experience_totale" :class="champ('experience_totale')">
                <p v-if="erreur('experience_totale')" class="mt-1 text-xs text-red-600">{{ erreur('experience_totale') }}</p>
              </div>
            </div>
            <p class="mt-3 text-xs text-gray-500">
              Les compétences sont déclarées par le candidat lui-même, depuis sa page CV.
            </p>
          </div>

          <div class="border-t border-gray-100"></div>

          <!-- Section 3 : accès au compte -->
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <span class="bg-green-100 text-green-600 p-1.5 rounded-lg text-sm">🔑</span>
              Accès au compte
            </h3>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Mot de passe provisoire</label>
            <div class="flex gap-2">
              <input type="text" v-model="form.password" autocomplete="new-password" placeholder="8 caractères minimum, lettres et chiffres" :class="champ('password')" class="font-mono">
              <button type="button" @click="genererMotDePasse" class="px-4 py-3 border border-gray-200 hover:bg-gray-50 rounded-xl text-sm text-gray-700 whitespace-nowrap">
                Générer
              </button>
            </div>
            <p v-if="erreur('password')" class="mt-1 text-xs text-red-600">{{ erreur('password') }}</p>
            <p v-else class="mt-1 text-xs text-gray-500">À transmettre au candidat, qui pourra le changer depuis son profil.</p>
          </div>

          <div class="flex items-center justify-end gap-4 pt-4">
            <button type="button" @click="router.push('/admin/candidates')" class="px-6 py-3 text-gray-600 font-medium hover:bg-gray-50 rounded-xl transition-all">
              Annuler
            </button>
            <button type="submit" :disabled="envoi" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-semibold rounded-xl transition-all shadow-sm hover:shadow-lg hover:shadow-blue-200 flex items-center gap-2">
              {{ envoi ? 'Création...' : '+ Ajouter le candidat' }}
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
import SidebarItem from '../components/SidebarItem.vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
import { motDePasseProvisoire } from '../services/motDePasse'

const router = useRouter()
const authStore = useAuthStore()
const userName = computed(() => authStore.user?.nom_complet || 'Administrateur')

const form = reactive({
  prenom: '',
  nom: '',
  email: '',
  telephone: '',
  diplome: '',
  adresse: '',
  experience_totale: 0,
  password: '',
})

const envoi = ref(false)
const erreurs = ref({})
const messageErreur = ref('')

const erreur = (c) => erreurs.value?.[c]?.[0] || ''
const champ = (c) => [
  'w-full px-4 py-3 rounded-xl border outline-none transition-all focus:ring-2',
  erreur(c) ? 'border-red-300 focus:border-red-500 focus:ring-red-100' : 'border-gray-200 focus:border-blue-500 focus:ring-blue-100',
]

const genererMotDePasse = () => { form.password = motDePasseProvisoire() }

const addCandidate = async () => {
  envoi.value = true
  erreurs.value = {}
  messageErreur.value = ''

  // Les champs optionnels vides ne sont pas envoyés.
  const payload = { ...form, password_confirmation: form.password }
  for (const cle of ['telephone', 'diplome', 'adresse']) {
    if (!payload[cle]) delete payload[cle]
  }

  try {
    await api.post('/admin/candidats', payload)
    alert(`✅ Compte candidat créé.\nMot de passe provisoire : ${form.password}`)
    router.push('/admin/candidates')
  } catch (err) {
    if (err.response?.status === 422) {
      erreurs.value = err.response.data.errors || {}
      messageErreur.value = 'Vérifiez les champs signalés.'
    } else {
      messageErreur.value = err.response?.data?.message || 'La création a échoué. Réessayez.'
    }
  } finally {
    envoi.value = false
  }
}
</script>
