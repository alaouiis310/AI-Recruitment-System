<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Ajouter un recruteur" 
    page-subtitle="Créez un compte recruteur et rattachez-le à son entreprise"
  >
    <template #menu>
      <SidebarItem to="/admin" icon="🏠" label="Accueil" />
      <SidebarItem to="/admin/recruiters" icon="👥" label="Recruteurs" active />
      <SidebarItem to="/admin/candidates" icon="👨‍💼" label="Candidats" />
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
        <form @submit.prevent="addRecruiter" class="space-y-6">

          <!-- Section 1 : informations personnelles -->
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <span class="bg-blue-100 text-blue-600 p-1.5 rounded-lg text-sm">👤</span>
              Informations personnelles
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Prénom</label>
                <input type="text" v-model="form.prenom" placeholder="Ex : Salma" :class="champ('prenom')">
                <p v-if="erreur('prenom')" class="mt-1 text-xs text-red-600">{{ erreur('prenom') }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nom</label>
                <input type="text" v-model="form.nom" placeholder="Ex : Bennani" :class="champ('nom')">
                <p v-if="erreur('nom')" class="mt-1 text-xs text-red-600">{{ erreur('nom') }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email professionnel</label>
                <input type="email" v-model="form.email" placeholder="salma@entreprise.ma" :class="champ('email')">
                <p v-if="erreur('email')" class="mt-1 text-xs text-red-600">{{ erreur('email') }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Téléphone (optionnel)</label>
                <input type="tel" v-model="form.telephone" placeholder="05 39 00 00 00" :class="champ('telephone')">
                <p v-if="erreur('telephone')" class="mt-1 text-xs text-red-600">{{ erreur('telephone') }}</p>
              </div>
            </div>
          </div>

          <div class="border-t border-gray-100"></div>

          <!-- Section 2 : entreprise (RG6, RG7) -->
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <span class="bg-purple-100 text-purple-600 p-1.5 rounded-lg text-sm">🏢</span>
              Entreprise
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Entreprise</label>
                <input type="text" v-model="form.entreprise" list="entreprises-existantes" placeholder="Ex : TechnoMaroc" :class="champ('entreprise.nom')">
                <datalist id="entreprises-existantes">
                  <option v-for="e in entreprises" :key="e.id_entreprise" :value="e.nom" />
                </datalist>
                <p v-if="erreur('entreprise.nom') || erreur('id_entreprise')" class="mt-1 text-xs text-red-600">{{ erreur('entreprise.nom') || erreur('id_entreprise') }}</p>
                <p v-else class="mt-1 text-xs text-gray-500">
                  {{ entrepriseExistante ? 'Entreprise existante : le recruteur y sera rattaché.' : 'Nouvelle entreprise : elle sera créée.' }}
                </p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Poste (optionnel)</label>
                <input type="text" v-model="form.poste" placeholder="Ex : Responsable des ressources humaines" :class="champ('poste')">
              </div>
              <template v-if="!entrepriseExistante">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">Secteur (optionnel)</label>
                  <input type="text" v-model="form.secteur" placeholder="Ex : Technologies de l'information" :class="champ('entreprise.secteur')">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">Ville (optionnel)</label>
                  <input type="text" v-model="form.ville" placeholder="Ex : Tanger" :class="champ('entreprise.ville')">
                </div>
              </template>
            </div>
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
              <button type="button" @click="form.password = motDePasseProvisoire()" class="px-4 py-3 border border-gray-200 hover:bg-gray-50 rounded-xl text-sm text-gray-700 whitespace-nowrap">
                Générer
              </button>
            </div>
            <p v-if="erreur('password')" class="mt-1 text-xs text-red-600">{{ erreur('password') }}</p>
            <p v-else class="mt-1 text-xs text-gray-500">À transmettre au recruteur, qui pourra le changer depuis son profil.</p>
          </div>

          <div class="flex items-center justify-end gap-4 pt-4">
            <button type="button" @click="router.push('/admin/recruiters')" class="px-6 py-3 text-gray-600 font-medium hover:bg-gray-50 rounded-xl transition-all">
              Annuler
            </button>
            <button type="submit" :disabled="envoi" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-semibold rounded-xl transition-all shadow-sm hover:shadow-lg hover:shadow-blue-200 flex items-center gap-2">
              {{ envoi ? 'Création...' : '+ Ajouter le recruteur' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
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
  entreprise: '',
  poste: '',
  secteur: '',
  ville: '',
  password: '',
})

const entreprises = ref([])
const envoi = ref(false)
const erreurs = ref({})
const messageErreur = ref('')

// RG7 : un recruteur appartient à exactement une entreprise, existante ou créée ici.
const entrepriseExistante = computed(() => {
  const nom = form.entreprise.trim().toLowerCase()
  return nom ? entreprises.value.find(e => e.nom.toLowerCase() === nom) || null : null
})

const erreur = (c) => erreurs.value?.[c]?.[0] || ''
const champ = (c) => [
  'w-full px-4 py-3 rounded-xl border outline-none transition-all focus:ring-2',
  erreur(c) ? 'border-red-300 focus:border-red-500 focus:ring-red-100' : 'border-gray-200 focus:border-blue-500 focus:ring-blue-100',
]

const addRecruiter = async () => {
  envoi.value = true
  erreurs.value = {}
  messageErreur.value = ''

  const payload = {
    prenom: form.prenom,
    nom: form.nom,
    email: form.email,
    password: form.password,
    password_confirmation: form.password,
  }
  if (form.telephone) payload.telephone = form.telephone
  if (form.poste) payload.poste = form.poste

  if (entrepriseExistante.value) {
    payload.id_entreprise = entrepriseExistante.value.id_entreprise
  } else {
    payload.entreprise = { nom: form.entreprise.trim() }
    if (form.secteur) payload.entreprise.secteur = form.secteur
    if (form.ville) payload.entreprise.ville = form.ville
  }

  try {
    await api.post('/admin/recruteurs', payload)
    alert(`✅ Compte recruteur créé.\nMot de passe provisoire : ${form.password}`)
    router.push('/admin/recruiters')
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

onMounted(async () => {
  try {
    const response = await api.get('/entreprises', { params: { per_page: 100 } })
    entreprises.value = response.data.entreprises || []
  } catch (err) {
    console.error('Erreur entreprises:', err)
  }
})
</script>
