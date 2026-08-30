<template>
  <div class="min-h-screen bg-white flex items-center justify-center p-4">
    <div class="flex flex-col lg:flex-row max-w-6xl w-full">
      
      <!-- Partie Gauche - Branding -->
      <div class="lg:w-1/2 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-3xl p-10 lg:p-14 text-white flex flex-col justify-between relative overflow-hidden">
        
        <!-- Décoration de fond -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/3"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full translate-y-1/2 -translate-x-1/3"></div>
        
        <div>
          <!-- Logo -->
          <div class="flex items-center gap-3 mb-8 relative z-10">
            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
              <span class="text-2xl font-extrabold">AI</span>
            </div>
            <div>
              <span class="text-2xl font-extrabold tracking-tight">AI Recruitment System</span>
            </div>
          </div>
          
          <!-- Titre -->
          <div class="relative z-10">
            <h1 class="text-4xl lg:text-5xl font-extrabold leading-tight">
              Recrutez <br class="hidden sm:block">
              <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-200 to-white">intelligemment</span>
            </h1>
          </div>
          
          <!-- Sous-titre -->
          <div class="mt-6 relative z-10">
            <h2 class="text-3xl lg:text-4xl font-light text-blue-100">
              Le bon talent.
            </h2>
            <h2 class="text-3xl lg:text-4xl font-bold text-white">
              Au bon moment.
            </h2>
          </div>
          
          <p class="text-blue-100/80 mt-4 text-sm lg:text-base leading-relaxed relative z-10 max-w-sm">
            Notre solution IA analyse, sélectionne et met en relation les meilleurs talents avec les bonnes opportunités.
          </p>
        </div>

        <!-- Avantages -->
        <div class="space-y-4 mt-8 relative z-10">
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center shrink-0 backdrop-blur-sm text-xl">🤖</div>
            <div>
              <p class="font-semibold text-white">IA Avancée</p>
              <p class="text-blue-200/70 text-sm">Analyse intelligente des CV et présélection automatique</p>
            </div>
          </div>
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center shrink-0 backdrop-blur-sm text-xl">🎯</div>
            <div>
              <p class="font-semibold text-white">Matching Précis</p>
              <p class="text-blue-200/70 text-sm">Mise en relation basée sur les compétences et la culture</p>
            </div>
          </div>
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center shrink-0 backdrop-blur-sm text-xl">⚖️</div>
            <div>
              <p class="font-semibold text-white">Recrutement Équitable</p>
              <p class="text-blue-200/70 text-sm">Des décisions objectives basées sur la data</p>
            </div>
          </div>
        </div>

        <!-- Statistiques -->
        <div class="flex items-center gap-8 mt-8 pt-6 border-t border-white/10 relative z-10">
          <div>
            <p class="text-2xl font-bold">10K+</p>
            <p class="text-blue-200/70 text-xs">Entreprises</p>
          </div>
          <div>
            <p class="text-2xl font-bold">500K+</p>
            <p class="text-blue-200/70 text-xs">Talents</p>
          </div>
          <div>
            <p class="text-2xl font-bold">95%</p>
            <p class="text-blue-200/70 text-xs">Satisfaction</p>
          </div>
        </div>
      </div>

      <!-- Partie Droite - Formulaire -->
      <div class="lg:w-1/2 px-8 lg:px-12 py-10 flex flex-col justify-center">
        
        <!-- En-tête -->
        <div class="mb-6">
          <h2 class="text-2xl font-bold text-gray-900">Bienvenue !</h2>
          <p class="text-gray-500 text-sm mt-1">Connectez-vous à votre compte</p>
        </div>

        <!-- Tabs Connexion / Inscription -->
        <div class="flex gap-2 bg-gray-100 rounded-xl p-1 mb-6 w-fit">
          <button 
            @click="activeTab = 'login'" 
            :class="[
              'px-6 py-2 rounded-lg text-sm font-medium transition-all duration-200',
              activeTab === 'login' 
                ? 'bg-white text-gray-900 shadow-sm' 
                : 'text-gray-500 hover:text-gray-700'
            ]"
          >
            Connexion
          </button>
          <button 
            @click="activeTab = 'signup'" 
            :class="[
              'px-6 py-2 rounded-lg text-sm font-medium transition-all duration-200',
              activeTab === 'signup' 
                ? 'bg-white text-gray-900 shadow-sm' 
                : 'text-gray-500 hover:text-gray-700'
            ]"
          >
            Inscription
          </button>
        </div>

        <!-- Formulaire de Connexion -->
        <form v-if="activeTab === 'login'" @submit.prevent="handleLogin" class="space-y-4">
          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Adresse email</label>
            <input 
              v-model="loginForm.email" 
              type="email" 
              placeholder="Entrez votre adresse email"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all placeholder:text-gray-400"
              required
            >
          </div>
          
          <!-- Mot de passe -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Mot de passe</label>
            <div class="relative">
              <input 
                v-model="loginForm.password" 
                :type="showPassword ? 'text' : 'password'" 
                placeholder="Entrez votre mot de passe"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all placeholder:text-gray-400"
                required
              >
              <button 
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-lg"
              >
                🔒
              </button>
            </div>
          </div>

          <!-- Options -->
          <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-gray-600 cursor-pointer">
              <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
              Se souvenir de moi
            </label>
            <a href="#" class="text-blue-600 hover:text-blue-800 font-medium">Mot de passe oublié ?</a>
          </div>

          <!-- Bouton Connexion -->
          <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all shadow-sm hover:shadow-lg hover:shadow-blue-200">
            Se connecter
          </button>
        </form>

        <!-- Formulaire d'Inscription -->
        <form v-else @submit.prevent="handleSignup" class="space-y-3">
          <!-- Prénom & Nom -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Prénom</label>
              <input 
                v-model="signupForm.firstName" 
                type="text" 
                placeholder="Entrez votre prénom"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all placeholder:text-gray-400"
                required
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Nom</label>
              <input 
                v-model="signupForm.lastName" 
                type="text" 
                placeholder="Entrez votre nom"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all placeholder:text-gray-400"
                required
              >
            </div>
          </div>

          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
            <input 
              v-model="signupForm.email" 
              type="email" 
              placeholder="exemple@entreprise.com"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all placeholder:text-gray-400"
              required
            >
          </div>

          <!-- Mot de passe -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Mot de passe</label>
            <input 
              v-model="signupForm.password" 
              type="password" 
              placeholder="Créez un mot de passe"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all placeholder:text-gray-400"
              required
            >
            <p class="text-xs text-gray-400 mt-1">Le mot de passe doit contenir au moins 8 caractères, dont une majuscule, une minuscule, un chiffre et un caractère spécial.</p>
          </div>

          <!-- Rôle -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Je suis</label>
            <div class="flex gap-4">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" v-model="signupForm.role" value="candidat" class="w-4 h-4 text-blue-600 focus:ring-blue-500" checked>
                <span class="text-sm text-gray-700">Candidat</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" v-model="signupForm.role" value="recruteur" class="w-4 h-4 text-blue-600 focus:ring-blue-500">
                <span class="text-sm text-gray-700">Recruteur</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" v-model="signupForm.role" value="admin" class="w-4 h-4 text-blue-600 focus:ring-blue-500">
                <span class="text-sm text-gray-700">Administrateur</span>
              </label>
            </div>
          </div>

          <!-- Conditions -->
          <label class="flex items-start gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="checkbox" required class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 mt-0.5">
            <span>J'accepte les <a href="#" class="text-blue-600 hover:underline">Conditions d'utilisation</a> et la <a href="#" class="text-blue-600 hover:underline">Politique de confidentialité</a></span>
          </label>

          <!-- Bouton Inscription -->
          <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all shadow-sm hover:shadow-lg hover:shadow-blue-200 mt-2">
            Créer mon compte
          </button>
        </form>

        <!-- Séparateur -->
        <div class="relative my-6">
          <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-200"></div>
          </div>
          <div class="relative flex justify-center text-sm">
            <span class="px-4 bg-white text-gray-500">ou continuer avec</span>
          </div>
        </div>

        <!-- Boutons Sociaux -->
        <div class="flex gap-3">
          <button type="button" class="flex-1 py-3 border border-gray-200 rounded-xl hover:bg-gray-50 transition-all flex items-center justify-center gap-2 text-sm font-medium text-gray-700">
            <svg class="w-5 h-5" viewBox="0 0 24 24">
              <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
              <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
              <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
              <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            Google
          </button>
          <button type="button" class="flex-1 py-3 border border-gray-200 rounded-xl hover:bg-gray-50 transition-all flex items-center justify-center gap-2 text-sm font-medium text-gray-700">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
              <path d="M11.23 12.14c0-.75-.06-1.44-.19-2.12H6.64v4.28h2.6c-.11.65-.43 1.2-.92 1.57v1.3h1.49c.87-.8 1.37-1.98 1.37-3.03z" fill="#1e88e5"/>
              <path d="M6.64 12.14c0-.73.13-1.44.39-2.12V7.74H5.15c-.74 1.35-1.17 2.89-1.17 4.4s.43 3.05 1.17 4.4h1.88v-2.28c-.26-.68-.39-1.39-.39-2.12z" fill="#1e88e5"/>
              <path d="M9.38 13.67c-.23.72-.23 1.5 0 2.22h-1.88v1.3c1.23.9 2.82 1.44 4.5 1.44 2.6 0 4.8-1.44 5.76-3.52h-1.88c-.48.9-1.46 1.5-2.6 1.5-1.98 0-3.66-1.34-4.28-3.18h-1.88v1.3z" fill="#1e88e5"/>
            </svg>
            Microsoft
          </button>
        </div>

        <!-- Lien création de compte -->
        <p class="text-center text-sm text-gray-500 mt-6">
          Vous n'avez pas encore de compte ? 
          <button @click="activeTab = 'signup'" class="text-blue-600 font-medium hover:text-blue-800">
            Créer un compte
          </button>
        </p>

        <!-- Footer -->
        <div class="flex flex-wrap items-center justify-center gap-4 mt-6 text-xs text-gray-400">
          <span>🔒 Sécurisé et confidentiel</span>
          <span>•</span>
          <span>Vos données sont protégées</span>
          <span>•</span>
          <a href="#" class="hover:text-gray-600">Conditions d'utilisation</a>
          <span>•</span>
          <a href="#" class="hover:text-gray-600">Politique de confidentialité</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const activeTab = ref('login')
const showPassword = ref(false)

const loginForm = reactive({
  email: '',
  password: '',
})

const signupForm = reactive({
  firstName: '',
  lastName: '',
  email: '',
  password: '',
  role: 'candidat',
})

const handleLogin = async () => {
  try {
    const response = await axios.post('http://localhost:8000/api/login', loginForm)
    localStorage.setItem('token', response.data.token)
    localStorage.setItem('user', JSON.stringify(response.data.user))
    router.push('/dashboard')
  } catch (error) {
    alert('Erreur de connexion: ' + (error.response?.data?.message || 'Veuillez réessayer'))
  }
}

const handleSignup = async () => {
  try {
    const response = await axios.post('http://localhost:8000/api/register', signupForm)
    alert('Inscription réussie ! Connectez-vous maintenant.')
    activeTab.value = 'login'
    loginForm.email = signupForm.email
  } catch (error) {
    alert('Erreur d\'inscription: ' + (error.response?.data?.message || 'Veuillez réessayer'))
  }
}
</script>