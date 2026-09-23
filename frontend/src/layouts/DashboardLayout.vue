<template>
  <div class="flex min-h-screen bg-gray-50">
    <!-- Sidebar Overlay (mobile) -->
    <div 
      v-if="isSidebarOpen" 
      class="fixed inset-0 z-40 bg-black/50 lg:hidden transition-opacity duration-300"
      @click="closeSidebar"
    ></div>

    <!-- Sidebar -->
    <aside 
      :class="[
        'fixed lg:sticky top-0 z-50 h-screen w-72 bg-white border-r border-gray-200 shadow-lg transition-transform duration-300 ease-in-out flex flex-col',
        isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
      ]"
    >
      <!-- Logo / Brand -->
      <div class="flex items-center gap-3 px-6 h-20 border-b border-gray-100 shrink-0">
        <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-md">
          AI
        </div>
        <span class="text-lg font-bold text-gray-800 tracking-tight">Recruitment <span class="text-blue-600">System</span></span>
      </div>



     <!-- Profile (top of sidebar) - Cliquable vers Mon Profil -->
      <div 
        @click="goToProfile"
        class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 shrink-0 cursor-pointer hover:bg-gray-50 transition-colors group"
      >
        <img 
          :src="userAvatar" 
          alt="Avatar" 
          class="w-11 h-11 rounded-full border-2 border-blue-500 object-cover"
          @error="userAvatar = 'https://ui-avatars.com/api/?name=' + userName + '&background=2563eb&color=fff&size=44'"
        />
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-gray-800 truncate">{{ userName }}</p>
          <p class="text-xs text-gray-500 truncate">{{ userRole }}</p>
        </div>
        <button 
          class="text-gray-400 group-hover:text-blue-600 transition-colors p-1 rounded-lg group-hover:bg-blue-50"
          title="Mon profil"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </button>
      </div>




      <!-- Navigation - Menu Dynamique basé sur le rôle -->
      <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        <!-- Menu Candidat -->
        <template v-if="userRole === 'Candidat'">
          <SidebarItem to="/candidate" icon="🏠" label="Accueil" />
          <SidebarItem to="/candidate/applications" icon="📝" label="Mes candidatures" :badge="12" />
          <SidebarItem to="/candidate/jobs" icon="💼" label="Offres d'emploi" :badge="24" />
          <SidebarItem to="/candidate/interviews" icon="🗓️" label="Mes entretiens" :badge="2" />
          <SidebarItem to="/candidate/saved" icon="⭐" label="Offres sauvegardées" :badge="5" />
          
          <div class="border-t border-gray-100 my-3"></div>
          
          <SidebarItem to="/candidate/ai-helper" icon="🤖" label="AI Helper" />
          
          <div class="border-t border-gray-100 my-3"></div>
          
          <SidebarItem to="/candidate/profile" icon="👤" label="Mon profil" />
          <SidebarItem to="/candidate/cv" icon="📄" label="Mon CV" />
        </template>

        <!-- Menu Recruteur -->
        <template v-if="userRole === 'Recruteur'">
          <SidebarItem to="/recruiter" icon="🏠" label="Accueil" />
          <SidebarItem to="/recruiter/jobs" icon="💼" label="Mes offres" :badge="12" />
          <SidebarItem to="/recruiter/candidates" icon="🧑‍💻" label="Candidats" :badge="86" />
          
          <div class="border-t border-gray-100 my-3"></div>
          
          <SidebarItem to="/recruiter/search" icon="🔍" label="Rechercher" />
          <SidebarItem to="/recruiter/ai-helper" icon="🤖" label="AI Helper" />
          <SidebarItem to="/recruiter/messages" icon="💬" label="Messages" :badge="4" />
          
          <div class="border-t border-gray-100 my-3"></div>
          
          <SidebarItem to="/recruiter/profile" icon="👤" label="Mon profil" />
        </template>

        <!-- Menu Admin -->
        <template v-if="userRole === 'Administrateur'">
          <SidebarItem to="/admin" icon="🏠" label="Accueil" />
          <SidebarItem to="/admin/recruiters" icon="👤" label="Recruteurs" :badge="86" />
          <SidebarItem to="/admin/candidates" icon="🧑‍💻" label="Candidats" :badge="1248" />
          <SidebarItem to="/admin/jobs" icon="💼" label="Offres d'emploi" :badge="47" />
          <SidebarItem to="/admin/applications" icon="📝" label="Candidatures" :badge="5426" />
          
          <div class="border-t border-gray-100 my-3"></div>
          
          <SidebarItem to="/admin/analytics" icon="📈" label="Analytiques" />
          <SidebarItem to="/admin/settings" icon="⚙️" label="Paramètres" />
          
          <div class="border-t border-gray-100 my-3"></div>
          
          <SidebarItem to="/admin/profile" icon="👤" label="Mon profil" />
        </template>
      </nav>

      <!-- Footer -->
      <div class="border-t border-gray-100 p-4 shrink-0">
        <button 
          @click="handleLogout"
          class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-red-500 hover:bg-red-50 transition-colors text-sm font-medium"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          Déconnexion
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 min-w-0">
      <!-- Header (mobile) -->
      <header class="lg:hidden flex items-center justify-between px-4 py-3 bg-white border-b border-gray-200 sticky top-0 z-30">
        <button @click="toggleSidebar" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
          <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        <span class="font-semibold text-gray-800 text-sm">{{ pageTitle }}</span>
        <div class="w-8"></div>
      </header>

      <!-- Page Content -->
      <div class="p-4 md:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
          <!-- Page Header -->
          <div class="flex items-center justify-between mb-6">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">{{ pageTitle }}</h1>
              <p class="text-sm text-gray-500 mt-0.5">{{ pageSubtitle }}</p>
            </div>
            <slot name="header-actions"></slot>
          </div>

          <slot></slot>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import SidebarItem from '../components/SidebarItem.vue'
import { useAuthStore } from '../stores/auth'

const props = defineProps({
  userName: {
    type: String,
    default: 'Utilisateur'
  },
  userRole: {
    type: String,
    default: 'Recruteur'
  },
  pageTitle: {
    type: String,
    default: 'Dashboard'
  },
  pageSubtitle: {
    type: String,
    default: 'Vue d\'ensemble de votre activité'
  }
})

const router = useRouter()
const auth = useAuthStore()
const isSidebarOpen = ref(false)

const userAvatar = ref('')

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}

const closeSidebar = () => {
  isSidebarOpen.value = false
}


// ✅ Redirection intelligente selon le rôle
const goToProfile = () => {
  if (props.userRole === 'Administrateur') {
    router.push('/admin/profile')
  } else if (props.userRole === 'Recruteur') {
    router.push('/recruiter/profile')
  } else if (props.userRole === 'Candidat') {
    router.push('/candidate/profile')
  }
}

const handleLogout = async () => {
  try {
    await auth.deconnexion()
  } finally {
    await router.push('/')
  }
}
</script>
