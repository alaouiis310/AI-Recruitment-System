<template>
  <DashboardLayout 
    :user-name="userName" 
    user-role="Candidat" 
    page-title="AI Helper" 
    page-subtitle="Optimisez vos candidatures avec l'intelligence artificielle"
  >
    <!-- Menu -->
    <template #menu>
      <SidebarItem to="/candidate" icon="🏠" label="Accueil" />
      <SidebarItem to="/candidate/applications" icon="📝" label="Mes candidatures" :badge="applicationsCount" />
      <SidebarItem to="/candidate/jobs" icon="💼" label="Offres d'emploi" :badge="jobsCount" />
      <SidebarItem to="/candidate/interviews" icon="🗓️" label="Mes entretiens" :badge="interviewsCount" />
      <SidebarItem to="/candidate/saved" icon="⭐" label="Offres sauvegardées" :badge="savedCount" />
      <SidebarItem to="/candidate/profile" icon="👤" label="Mon profil" />
      <SidebarItem to="/candidate/cv" icon="📄" label="Mon CV" />
      <div class="mt-4 border-t border-gray-100 pt-4">
        <SidebarItem to="/candidate/ai-helper" icon="🤖" label="AI Helper" active />
      </div>
    </template>

    <!-- 4 Cards IA -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
      <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-5 text-white shadow-lg hover:shadow-xl transition-all cursor-pointer">
        <div class="text-3xl mb-2">📄</div>
        <h4 class="font-semibold">Analyse CV</h4>
        <p class="text-blue-100 text-sm mt-1">Optimisez votre CV</p>
      </div>
      <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-2xl p-5 text-white shadow-lg hover:shadow-xl transition-all cursor-pointer">
        <div class="text-3xl mb-2">🎯</div>
        <h4 class="font-semibold">Matching</h4>
        <p class="text-purple-100 text-sm mt-1">Offres compatibles</p>
      </div>
      <div class="bg-gradient-to-br from-amber-600 to-amber-700 rounded-2xl p-5 text-white shadow-lg hover:shadow-xl transition-all cursor-pointer">
        <div class="text-3xl mb-2">💡</div>
        <h4 class="font-semibold">Suggestions</h4>
        <p class="text-amber-100 text-sm mt-1">Améliorations</p>
      </div>
      <div class="bg-gradient-to-br from-green-600 to-green-700 rounded-2xl p-5 text-white shadow-lg hover:shadow-xl transition-all cursor-pointer">
        <div class="text-3xl mb-2">📝</div>
        <h4 class="font-semibold">Lettre</h4>
        <p class="text-green-100 text-sm mt-1">Génération automatique</p>
      </div>
    </div>

    <!-- Compatibilité réelle (RG40) et compétences manquantes (RG41) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 mb-3">📊 Votre meilleure compatibilité</h3>
        <template v-if="meilleure">
          <div class="text-center">
            <div class="relative inline-block">
              <svg class="w-32 h-32" viewBox="0 0 36 36">
                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#e5e7eb" stroke-width="3"/>
                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" :stroke="couleur(meilleure.analyse.score_matching)" stroke-width="3" :stroke-dasharray="`${meilleure.analyse.score_matching} 100`"/>
              </svg>
              <span class="absolute inset-0 flex items-center justify-center text-3xl font-bold text-gray-800">{{ Math.round(meilleure.analyse.score_matching) }}</span>
            </div>
            <p class="mt-2 text-sm text-gray-600">{{ meilleure.offre?.titre }}</p>
            <p class="text-xs text-gray-400">{{ meilleure.analyse.recommandation_libelle }}</p>
          </div>
          <div class="space-y-2 mt-4">
            <div v-for="volet in volets(meilleure.analyse)" :key="volet.label">
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">{{ volet.label }}</span>
                <span class="font-medium" :style="{ color: couleur(volet.score) }">{{ Math.round(volet.score) }}/100</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-1.5">
                <div class="h-1.5 rounded-full" :style="{ width: volet.score + '%', background: couleur(volet.score) }"></div>
              </div>
            </div>
          </div>
        </template>
        <p v-else-if="!chargementAnalyses" class="text-sm text-gray-500 py-6 text-center">
          Postulez à une offre : son analyse vous donnera un score de compatibilité.
        </p>
        <p v-else class="text-sm text-gray-400 py-6 text-center">Chargement...</p>
        <router-link to="/candidate/applications" class="block w-full mt-4 py-2 text-center bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium transition-all">
          Voir mes candidatures
        </router-link>
      </div>

      <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 mb-3">💡 Suggestions d'amélioration</h3>
        <div class="space-y-3">
          <div v-for="suggestion in suggestions" :key="suggestion.id" class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl">
            <span class="text-lg shrink-0">{{ suggestion.icon }}</span>
            <div>
              <p class="text-sm font-medium text-gray-800">{{ suggestion.title }}</p>
              <p class="text-xs text-gray-500">{{ suggestion.description }}</p>
            </div>
            <router-link :to="suggestion.lien" class="ml-auto shrink-0 text-xs font-medium text-blue-600 hover:text-blue-700">{{ suggestion.action }}</router-link>
          </div>
          <p v-if="!chargementAnalyses && suggestions.length === 0" class="text-sm text-gray-500 py-4 text-center">
            Aucune lacune détectée sur vos candidatures actuelles.
          </p>
        </div>
      </div>
    </div>

    <!-- Chat IA -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="p-5 border-b border-gray-100 flex items-center gap-3">
        <span class="text-2xl">🤖</span>
        <div>
          <h4 class="font-semibold text-gray-800">Assistant IA</h4>
          <p class="text-xs text-gray-500">Conseils personnalisés pour vos candidatures</p>
        </div>
        <span class="ml-auto text-xs text-green-600 font-medium">● En ligne</span>
      </div>

      <!-- Messages -->
      <div class="p-5 max-h-64 overflow-y-auto space-y-4">
        <div class="flex items-start gap-3">
          <span class="text-xl">🤖</span>
          <div class="bg-gray-100 rounded-2xl rounded-tl-none px-4 py-2.5 max-w-[80%]">
            <p class="text-sm text-gray-700">Bonjour ! Je suis votre assistant IA. Je peux vous aider à :</p>
            <ul class="text-sm text-gray-600 mt-1 list-disc list-inside">
              <li>Optimiser votre CV</li>
              <li>Trouver des offres compatibles</li>
              <li>Préparer vos entretiens</li>
            </ul>
          </div>
        </div>

        <div v-for="(msg, index) in messages" :key="index" class="flex items-start gap-3" :class="msg.sender === 'user' ? 'flex-row-reverse' : ''">
          <span class="text-xl shrink-0">{{ msg.sender === 'user' ? '👤' : '🤖' }}</span>
          <div :class="[
            'rounded-2xl px-4 py-2.5 max-w-[80%]',
            msg.sender === 'user' ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-gray-100 text-gray-700 rounded-tl-none'
          ]">
            <p class="text-sm whitespace-pre-line">{{ msg.text }}</p>
          </div>
        </div>
        <div v-if="enAttente" class="flex items-start gap-3" aria-live="polite">
          <span class="text-xl shrink-0">🤖</span>
          <div class="rounded-2xl rounded-tl-none px-4 py-2.5 bg-gray-100 text-gray-500">
            <p class="text-sm italic">L'assistant rédige sa réponse…</p>
          </div>
        </div>
      </div>

      <!-- Input -->
      <div class="p-4 border-t border-gray-100 flex gap-3">
        <input 
          v-model="userMessage" 
          type="text" 
          placeholder="Posez une question sur votre candidature..." 
          class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all text-sm"
          @keydown.enter="sendMessage"
        >
        <button @click="sendMessage" :disabled="enAttente" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-medium rounded-xl transition-all shadow-sm hover:shadow-md">
          Envoyer
        </button>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import SidebarItem from '../components/SidebarItem.vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
const userName = computed(() => {
  const user = authStore.user
  return user?.prenom ? `${user.prenom} ${user.nom || ''}`.trim() : 'Candidat'
})
const applicationsCount = ref(0)
const jobsCount = ref(0)
const interviewsCount = ref(0)
const savedCount = ref(0)

const userMessage = ref('')
const messages = ref([])

// Analyses réelles des candidatures du candidat (RG37 à RG41).
const analyses = ref([])
const chargementAnalyses = ref(true)

const meilleure = computed(() =>
  analyses.value.reduce((best, a) => (!best || a.analyse.score_matching > best.analyse.score_matching ? a : best), null)
)

// Les trois volets du score calculé par ScoringService (RG40).
const volets = (a) => [
  { label: 'Compétences', score: a.score_competence },
  { label: 'Expérience', score: a.score_experience },
  { label: 'Diplôme', score: a.score_diplome },
]

const couleur = (score) => (score >= 70 ? '#059669' : score >= 45 ? '#d97706' : '#dc2626')

const POIDS = { essentielle: 3, importante: 2, souhaitee: 1 }
const NIVEAUX = { debutant: 'Débutant', intermediaire: 'Intermédiaire', avance: 'Avancé', expert: 'Expert' }

// RG41 : compétences exigées par les offres visées que le candidat ne déclare
// pas au niveau requis. Une compétence exigée par plusieurs offres n'apparaît
// qu'une fois, avec sa plus forte importance.
const suggestions = computed(() => {
  const parCompetence = new Map()
  for (const { offre, analyse } of analyses.value) {
    for (const m of analyse.competences_manquantes || []) {
      const actuelle = parCompetence.get(m.id_competence)
      if (!actuelle || POIDS[m.importance] > POIDS[actuelle.importance]) {
        parCompetence.set(m.id_competence, { ...m, offre: offre?.titre })
      }
    }
  }
  const lacunes = [...parCompetence.values()]
    .sort((a, b) => POIDS[b.importance] - POIDS[a.importance])
    .slice(0, 5)
    .map(m => ({
      id: 'c' + m.id_competence,
      icon: m.importance === 'essentielle' ? '🎯' : '📝',
      title: m.niveau_actuel ? `Progressez en ${m.nom}` : `Ajoutez ${m.nom} à vos compétences`,
      description: `Niveau ${NIVEAUX[m.niveau_requis] || m.niveau_requis} requis (${m.importance}) par « ${m.offre} »`
        + (m.niveau_actuel ? ` — vous déclarez ${NIVEAUX[m.niveau_actuel] || m.niveau_actuel}.` : '.'),
      action: 'Déclarer',
      lien: '/candidate/cv',
    }))

  const profil = authStore.user?.profil_candidat
  if (profil && !profil.cv_pdf) {
    lacunes.unshift({
      id: 'cv', icon: '📄', title: 'Déposez votre CV',
      description: "L'analyse de vos candidatures s'appuie sur votre CV (RG22).",
      action: 'Déposer', lien: '/candidate/cv',
    })
  }
  return lacunes
})

const chargerAnalyses = async () => {
  try {
    const response = await api.get('/candidat/candidatures')
    const candidatures = response.data.candidatures || []
    applicationsCount.value = candidatures.length

    const resultats = await Promise.allSettled(
      candidatures.map(c => api.get(`/candidat/candidatures/${c.id_candidature}/analyse`))
    )
    analyses.value = resultats
      .map((r, i) => (r.status === 'fulfilled' && r.value.data.analyse
        ? { offre: candidatures[i].offre, analyse: r.value.data.analyse }
        : null))
      .filter(Boolean)
  } catch (err) {
    console.error('Erreur analyses:', err)
  } finally {
    chargementAnalyses.value = false
  }
}

onMounted(chargerAnalyses)

// Assistant IA réel (module de Nilam) : POST /api/ia/assistant.
const enAttente = ref(false)

const sendMessage = async () => {
  const texte = userMessage.value.trim()
  if (!texte || enAttente.value) return

  messages.value.push({ sender: 'user', text: texte })
  userMessage.value = ''
  enAttente.value = true

  try {
    const response = await api.post('/ia/assistant', { message: texte })
    messages.value.push({ sender: 'ai', text: response.data.data.reponse })
  } catch (err) {
    // 503 : pas de clé Gemini sur ce serveur ; 502 : Gemini injoignable.
    messages.value.push({
      sender: 'ai',
      text: err.response?.data?.message || "L'assistant est momentanément indisponible.",
    })
  } finally {
    enAttente.value = false
  }
}
</script>