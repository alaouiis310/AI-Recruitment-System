<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Administrateur" 
    page-title="Paramètres" 
    page-subtitle="Gérez les administrateurs et les paramètres de la plateforme"
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
        
        <!-- ==================== GESTION DES ADMINISTRATEURS ==================== -->
        <div v-if="activeTab === 'admins'">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h3 class="font-semibold text-gray-800">👑 Gestion des administrateurs</h3>
              <p class="text-sm text-gray-500 mt-0.5">Ajoutez, modifiez ou supprimez des administrateurs</p>
            </div>
            <button 
              @click="showCreateAdmin = true"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md"
            >
              + Ajouter un admin
            </button>
          </div>

          <!-- Liste des administrateurs -->
          <div class="space-y-3">
            <div 
              v-for="admin in admins" 
              :key="admin.id"
              class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-all"
            >
              <div class="flex items-center gap-4">
                <img 
                  :src="admin.avatar" 
                  alt="Avatar" 
                  class="w-11 h-11 rounded-full border-2 border-blue-100 object-cover"
                >
                <div>
                  <p class="font-medium text-gray-800">{{ admin.name }}</p>
                  <p class="text-xs text-gray-500">{{ admin.email }}</p>
                  <div class="flex items-center gap-3 mt-0.5">
                    <span :class="['text-xs font-medium px-2 py-0.5 rounded-full', admin.status === 'Actif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
                      {{ admin.status }}
                    </span>
                    <span class="text-xs text-gray-400">{{ admin.role }}</span>
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <button 
                  @click="editAdmin(admin)"
                  class="px-3 py-1.5 text-xs font-medium text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                >
                  ✎ Modifier
                </button>
                <button 
                  @click="deleteAdmin(admin.id)"
                  class="px-3 py-1.5 text-xs font-medium text-red-500 hover:bg-red-50 rounded-lg transition-all"
                >
                  🗑️ Supprimer
                </button>
              </div>
            </div>
          </div>

          <!-- Aucun admin -->
          <div v-if="admins.length === 0" class="text-center py-12">
            <p class="text-5xl mb-3">👑</p>
            <p class="text-gray-500">Aucun administrateur trouvé</p>
          </div>
        </div>

        <!-- ==================== CRÉER UN ADMINISTRATEUR ==================== -->
        <div v-if="activeTab === 'admins' && showCreateAdmin">
          <div class="border-t border-gray-100 my-6"></div>
          <div class="bg-blue-50 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
              <h4 class="font-semibold text-gray-800">
                {{ editingAdmin ? '✎ Modifier un administrateur' : '➕ Ajouter un administrateur' }}
              </h4>
              <button 
                @click="closeAdminForm"
                class="text-gray-400 hover:text-gray-600"
              >
                ✕
              </button>
            </div>

            <form @submit.prevent="saveAdmin" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">Prénom</label>
                  <input 
                    v-model="adminForm.firstName" 
                    type="text" 
                    placeholder="Prénom"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white"
                    required
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">Nom</label>
                  <input 
                    v-model="adminForm.lastName" 
                    type="text" 
                    placeholder="Nom"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white"
                    required
                  >
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                <input 
                  v-model="adminForm.email" 
                  type="email" 
                  placeholder="admin@entreprise.com"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white"
                  required
                >
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Mot de passe</label>
                <input 
                  v-model="adminForm.password" 
                  type="password" 
                  :placeholder="editingAdmin ? 'Laisser vide pour conserver' : 'Créez un mot de passe'"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white"
                  :required="!editingAdmin"
                >
                <p class="text-xs text-gray-400 mt-1">8 caractères min. avec majuscule, minuscule, chiffre et caractère spécial</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Statut</label>
                <select 
                  v-model="adminForm.status"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white"
                >
                  <option value="Actif">Actif</option>
                  <option value="Inactif">Inactif</option>
                </select>
              </div>

              <div class="flex items-center gap-3 pt-2">
                <button 
                  type="submit"
                  class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-all shadow-sm hover:shadow-md"
                >
                  {{ editingAdmin ? 'Mettre à jour' : 'Créer l\'administrateur' }}
                </button>
                <button 
                  type="button"
                  @click="closeAdminForm"
                  class="px-6 py-3 border border-gray-200 hover:bg-gray-50 text-gray-600 font-medium rounded-xl transition-all"
                >
                  Annuler
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- ==================== GÉNÉRAL ==================== -->
        <div v-if="activeTab === 'general'">
          <h3 class="font-semibold text-gray-800 mb-4">⚙️ Paramètres généraux</h3>
          <form @submit.prevent="saveGeneral" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Nom de la plateforme</label>
              <input type="text" v-model="general.platformName" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">URL de la plateforme</label>
              <input type="text" v-model="general.platformUrl" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Email de contact</label>
              <input type="email" v-model="general.contactEmail" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
            </div>
            <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
              Enregistrer
            </button>
          </form>
        </div>

        <!-- ==================== SÉCURITÉ ==================== -->
        <div v-if="activeTab === 'security'">
          <h3 class="font-semibold text-gray-800 mb-4">🔒 Sécurité</h3>
          <form @submit.prevent="saveSecurity" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Mot de passe administrateur</label>
              <input type="password" v-model="security.password" placeholder="Nouveau mot de passe" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirmer</label>
              <input type="password" v-model="security.passwordConfirm" placeholder="Confirmer le mot de passe" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
            </div>
            <div class="flex items-center gap-3">
              <input type="checkbox" v-model="security.twoFactor" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
              <span class="text-sm text-gray-600">Authentification à deux facteurs (2FA)</span>
            </div>
            <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
              Mettre à jour
            </button>
          </form>
        </div>

        <!-- ==================== IA ==================== -->
        <div v-if="activeTab === 'ai'">
          <h3 class="font-semibold text-gray-800 mb-4">🤖 Configuration IA</h3>
          <form @submit.prevent="saveAI" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Clé API</label>
              <input type="password" v-model="ai.apiKey" placeholder="sk-..." class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Modèle IA</label>
              <select v-model="ai.model" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all bg-white">
                <option value="gpt-4">GPT-4</option>
                <option value="gpt-3.5-turbo">GPT-3.5-Turbo</option>
                <option value="mistral-7b" selected>Mistral-7B</option>
                <option value="llama-2">Llama 2</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Seuil de score IA</label>
              <input type="number" v-model="ai.threshold" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all">
              <p class="text-xs text-gray-400 mt-1">Score minimum pour qu'un candidat soit recommandé</p>
            </div>
            <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
              Enregistrer
            </button>
          </form>
        </div>

      </div>
    </div>

    <!-- Modal Suppression -->
    <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">🗑️ Supprimer l'administrateur</h3>
        <p class="text-sm text-gray-600">
          Êtes-vous sûr de vouloir supprimer l'administrateur <strong>{{ adminToDelete?.name }}</strong> ?
          Cette action est irréversible.
        </p>
        <div class="flex items-center gap-3 mt-6">
          <button 
            @click="confirmDelete"
            class="flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl transition-all"
          >
            Supprimer
          </button>
          <button 
            @click="showDeleteModal = false"
            class="flex-1 px-4 py-3 border border-gray-200 hover:bg-gray-50 text-gray-600 font-medium rounded-xl transition-all"
          >
            Annuler
          </button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const userName = ref('Admin')
const activeTab = ref('admins')
const showCreateAdmin = ref(false)
const editingAdmin = ref(false)
const showDeleteModal = ref(false)
const adminToDelete = ref(null)

// Liste des administrateurs
const admins = ref([
  { 
    id: 1, 
    name: 'Admin', 
    email: 'admin@ai-recruitment.com', 
    status: 'Actif', 
    role: 'Super Admin',
    avatar: 'https://ui-avatars.com/api/?name=Admin&background=2563eb&color=fff&size=44'
  },
  { 
    id: 2, 
    name: 'Sophie Martin', 
    email: 'sophie@ai-recruitment.com', 
    status: 'Actif', 
    role: 'Administrateur',
    avatar: 'https://ui-avatars.com/api/?name=Sophie+Martin&background=7c3aed&color=fff&size=44'
  },
  { 
    id: 3, 
    name: 'Jean Dupont', 
    email: 'jean@ai-recruitment.com', 
    status: 'Inactif', 
    role: 'Administrateur',
    avatar: 'https://ui-avatars.com/api/?name=Jean+Dupont&background=059669&color=fff&size=44'
  },
])

// Formulaire Admin
const adminForm = reactive({
  id: null,
  firstName: '',
  lastName: '',
  email: '',
  password: '',
  status: 'Actif'
})

// Paramètres
const general = reactive({
  platformName: 'AI Recruitment System',
  platformUrl: 'https://ai-recruitment-system.com',
  contactEmail: 'contact@ai-recruitment-system.com'
})

const security = reactive({
  password: '',
  passwordConfirm: '',
  twoFactor: true
})

const ai = reactive({
  apiKey: '',
  model: 'mistral-7b',
  threshold: 70
})

// Tabs
const tabs = ref([
  { key: 'admins', icon: '👑', label: 'Administrateurs' },
  { key: 'general', icon: '⚙️', label: 'Général' },
  { key: 'security', icon: '🔒', label: 'Sécurité' },
  { key: 'ai', icon: '🤖', label: 'IA' },
])

// CRUD Admin
const saveAdmin = () => {
  if (editingAdmin.value) {
    // Modifier un admin existant
    const index = admins.value.findIndex(a => a.id === adminForm.id)
    if (index !== -1) {
      admins.value[index] = {
        ...admins.value[index],
        name: `${adminForm.firstName} ${adminForm.lastName}`,
        email: adminForm.email,
        status: adminForm.status,
        avatar: `https://ui-avatars.com/api/?name=${adminForm.firstName}+${adminForm.lastName}&background=2563eb&color=fff&size=44`
      }
    }
    alert('✅ Administrateur modifié avec succès !')
  } else {
    // Ajouter un nouvel admin
    const newAdmin = {
      id: Date.now(),
      name: `${adminForm.firstName} ${adminForm.lastName}`,
      email: adminForm.email,
      status: adminForm.status,
      role: 'Administrateur',
      avatar: `https://ui-avatars.com/api/?name=${adminForm.firstName}+${adminForm.lastName}&background=2563eb&color=fff&size=44`
    }
    admins.value.push(newAdmin)
    alert('✅ Administrateur créé avec succès !')
  }
  closeAdminForm()
}

const editAdmin = (admin) => {
  editingAdmin.value = true
  showCreateAdmin.value = true
  const nameParts = admin.name.split(' ')
  adminForm.id = admin.id
  adminForm.firstName = nameParts[0] || ''
  adminForm.lastName = nameParts.slice(1).join(' ') || ''
  adminForm.email = admin.email
  adminForm.password = ''
  adminForm.status = admin.status
}

const deleteAdmin = (id) => {
  adminToDelete.value = admins.value.find(a => a.id === id)
  showDeleteModal.value = true
}

const confirmDelete = () => {
  if (adminToDelete.value) {
    admins.value = admins.value.filter(a => a.id !== adminToDelete.value.id)
    showDeleteModal.value = false
    adminToDelete.value = null
    alert('🗑️ Administrateur supprimé avec succès !')
  }
}

const closeAdminForm = () => {
  showCreateAdmin.value = false
  editingAdmin.value = false
  adminForm.id = null
  adminForm.firstName = ''
  adminForm.lastName = ''
  adminForm.email = ''
  adminForm.password = ''
  adminForm.status = 'Actif'
}

// Sauvegardes
const saveGeneral = () => {
  alert('✅ Paramètres généraux enregistrés !')
}

const saveSecurity = () => {
  if (security.password && security.password !== security.passwordConfirm) {
    alert('❌ Les mots de passe ne correspondent pas !')
    return
  }
  alert('✅ Paramètres de sécurité enregistrés !')
}

const saveAI = () => {
  alert('✅ Configuration IA enregistrée !')
}

// Initialisation
onMounted(() => {
  // Simuler le chargement des admins
})
</script>