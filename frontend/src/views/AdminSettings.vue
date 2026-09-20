<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Paramètres" 
    page-subtitle="Gérez les utilisateurs et les paramètres de la plateforme"
  >
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Menu paramètres -->
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 h-fit">
        <button 
          v-for="tab in tabs" 
          :key="tab.key"
          @click="activeTab = tab.key"
          :class="[
            'w-full text-left px-4 py-3 rounded-xl text-sm font-medium transition-all',
            activeTab === tab.key ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50'
          ]"
        >
          <span class="mr-2">{{ tab.icon }}</span> {{ tab.label }}
        </button>
      </div>

      <!-- Contenu -->
      <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        
        <!-- Gestion des utilisateurs -->
        <div v-if="activeTab === 'users'">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h3 class="font-semibold text-gray-800">👥 Gestion des utilisateurs</h3>
              <p class="text-sm text-gray-500 mt-0.5">Gérez les comptes candidats et recruteurs</p>
            </div>
          </div>

          <!-- Filtre rôle -->
          <div class="flex gap-2 mb-4">
            <button 
              @click="userType = 'candidats'"
              :class="[
                'px-4 py-2 rounded-xl text-sm font-medium transition-all',
                userType === 'candidats' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600'
              ]"
            >
              Candidats ({{ candidats.length }})
            </button>
            <button 
              @click="userType = 'recruteurs'"
              :class="[
                'px-4 py-2 rounded-xl text-sm font-medium transition-all',
                userType === 'recruteurs' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600'
              ]"
            >
              Recruteurs ({{ recruteurs.length }})
            </button>
          </div>

          <!-- Loading -->
          <div v-if="loadingUsers" class="flex items-center justify-center py-10">
            <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
          </div>

          <!-- Liste -->
          <div v-else class="space-y-3">
            <div 
              v-for="u in currentUsers" 
              :key="u.id"
              class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-all"
            >
              <div class="flex items-center gap-4">
                <img 
                  :src="getAvatar(u)" 
                  alt="Avatar" 
                  class="w-11 h-11 rounded-full border-2 border-blue-100 object-cover"
                >
                <div>
                  <p class="font-medium text-gray-800">{{ u.prenom }} {{ u.nom }}</p>
                  <p class="text-xs text-gray-500">{{ u.user?.email }}</p>
                  <span :class="['text-xs font-medium px-2 py-0.5 rounded-full mt-1 inline-block', getStatusBadge(u.user?.etat_compte)]">
                    {{ formatStatus(u.user?.etat_compte) }}
                  </span>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <button 
                  @click="toggleUserStatus(u)"
                  class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all"
                  :class="u.user?.etat_compte === 'actif' 
                    ? 'text-amber-600 hover:bg-amber-50' 
                    : 'text-green-600 hover:bg-green-50'"
                >
                  {{ u.user?.etat_compte === 'actif' ? '⏸️ Suspendre' : '▶️ Activer' }}
                </button>
              </div>
            </div>
            <p v-if="currentUsers.length === 0" class="text-center text-sm text-gray-400 py-8">
              Aucun utilisateur
            </p>
          </div>
        </div>

        <!-- Général -->
        <div v-if="activeTab === 'general'">
          <h3 class="font-semibold text-gray-800 mb-4">⚙️ Paramètres généraux</h3>
          <form @submit.prevent="saveGeneral" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Nom de la plateforme</label>
              <input type="text" v-model="general.platformName" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">URL</label>
              <input type="text" v-model="general.platformUrl" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Email de contact</label>
              <input type="email" v-model="general.contactEmail" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
            </div>
            <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-all">
              Enregistrer
            </button>
          </form>
        </div>

        <!-- Sécurité -->
        <div v-if="activeTab === 'security'">
          <h3 class="font-semibold text-gray-800 mb-4">🔒 Sécurité</h3>
          <form @submit.prevent="saveSecurity" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Nouveau mot de passe</label>
              <input type="password" v-model="security.password" placeholder="Nouveau mot de passe" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirmer</label>
              <input type="password" v-model="security.passwordConfirm" placeholder="Confirmer" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
            </div>
            <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-all">
              Mettre à jour
            </button>
          </form>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()

const userName = computed(() => {
  const user = authStore.user
  return user?.prenom ? `${user.prenom} ${user.nom || ''}`.trim() : 'Admin'
})

const activeTab = ref('users')
const userType = ref('candidats')
const loadingUsers = ref(true)

const candidats = ref([])
const recruteurs = ref([])

const general = reactive({
  platformName: 'AI Recruitment System',
  platformUrl: 'https://ai-recruitment-system.com',
  contactEmail: 'contact@ai-recruitment-system.com'
})

const security = reactive({
  password: '',
  passwordConfirm: ''
})

const tabs = ref([
  { key: 'users', icon: '👥', label: 'Utilisateurs' },
  { key: 'general', icon: '⚙️', label: 'Général' },
  { key: 'security', icon: '🔒', label: 'Sécurité' },
])

const currentUsers = computed(() => {
  return userType.value === 'candidats' ? candidats.value : recruteurs.value
})

const getAvatar = (u) => {
  const name = `${u.prenom || ''}+${u.nom || ''}`.trim() || 'User'
  return `https://ui-avatars.com/api/?name=${name}&background=2563eb&color=fff&size=44`
}

const fetchUsers = async () => {
  loadingUsers.value = true
  try {
    const [candRes, recrRes] = await Promise.all([
      api.get('/admin/candidats'),
      api.get('/admin/recruteurs')
    ])
    candidats.value = candRes.data.data || candRes.data.candidats || []
    recruteurs.value = recrRes.data.data || recrRes.data.recruteurs || []
  } catch (err) {
    console.error('Erreur utilisateurs:', err)
  } finally {
    loadingUsers.value = false
  }
}

const toggleUserStatus = async (u) => {
  const newStatus = u.user?.etat_compte === 'actif' ? 'suspendu' : 'actif'
  if (!confirm(`Changer le statut à "${formatStatus(newStatus)}" ?`)) return

  try {
    await api.patch(`/admin/utilisateurs/${u.user.id}/etat`, { etat_compte: newStatus })
    u.user.etat_compte = newStatus
  } catch (err) {
    alert('❌ Erreur lors de la mise à jour.')
  }
}

const formatStatus = (status) => {
  const labels = { 'actif': 'Actif', 'suspendu': 'Suspendu', 'desactive': 'Désactivé' }
  return labels[status] || status || 'N/A'
}

const getStatusBadge = (status) => {
  const badges = {
    'actif': 'bg-green-100 text-green-700',
    'suspendu': 'bg-amber-100 text-amber-700',
    'desactive': 'bg-red-100 text-red-700',
  }
  return badges[status] || 'bg-gray-100 text-gray-700'
}

const saveGeneral = () => alert('✅ Paramètres généraux enregistrés !')

const saveSecurity = () => {
  if (security.password && security.password !== security.passwordConfirm) {
    alert('❌ Les mots de passe ne correspondent pas !')
    return
  }
  alert('✅ Paramètres de sécurité enregistrés !')
}

onMounted(() => {
  fetchUsers()
})
</script>