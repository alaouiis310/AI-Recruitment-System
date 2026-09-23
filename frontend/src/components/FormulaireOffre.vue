<template>
  <form @submit.prevent="soumettre" class="space-y-6">

    <!-- Section 1 : informations sur le poste -->
    <div>
      <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
        <span class="bg-blue-100 text-blue-600 p-1.5 rounded-lg text-sm">💼</span>
        Informations sur le poste
      </h3>
      <div class="grid grid-cols-1 gap-4">

        <!-- Administrateur : l'offre est publiée au nom d'un recruteur (RG13) -->
        <div v-if="recruteurs">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Recruteur</label>
          <select v-model="form.id_recruteur" :class="classeChamp('id_recruteur')" class="bg-white">
            <option value="" disabled>Choisir un recruteur</option>
            <option v-for="r in recruteurs" :key="r.id_recruteur" :value="r.id_recruteur">
              {{ r.utilisateur?.nom_complet }} — {{ r.entreprise?.nom }}
            </option>
          </select>
          <p v-if="erreur('id_recruteur')" class="mt-1 text-xs text-red-600">{{ erreur('id_recruteur') }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Titre de l'offre</label>
          <input type="text" v-model="form.titre" placeholder="Ex : Développeur Full Stack" :class="classeChamp('titre')">
          <p v-if="erreur('titre')" class="mt-1 text-xs text-red-600">{{ erreur('titre') }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Département</label>
            <select v-model="form.id_departement" :disabled="!departements.length" :class="classeChamp('id_departement')" class="bg-white">
              <option value="" disabled>{{ departements.length ? 'Choisir un département' : 'Aucun département disponible' }}</option>
              <option v-for="d in departements" :key="d.id_departement" :value="d.id_departement">{{ d.nom }}</option>
            </select>
            <p v-if="erreur('id_departement')" class="mt-1 text-xs text-red-600">{{ erreur('id_departement') }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Localisation</label>
            <input type="text" v-model="form.localisation" placeholder="Ex : Tanger" :class="classeChamp('localisation')">
            <p v-if="erreur('localisation')" class="mt-1 text-xs text-red-600">{{ erreur('localisation') }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Type de contrat</label>
            <select v-model="form.type_contrat" :class="classeChamp('type_contrat')" class="bg-white">
              <option value="cdi">CDI</option>
              <option value="cdd">CDD</option>
              <option value="stage">Stage</option>
              <option value="alternance">Alternance</option>
              <option value="freelance">Freelance</option>
              <option value="interim">Intérim</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Salaire mensuel (optionnel)</label>
            <input type="number" min="0" step="100" v-model="form.salaire" placeholder="Ex : 14000" :class="classeChamp('salaire')">
            <p v-if="erreur('salaire')" class="mt-1 text-xs text-red-600">{{ erreur('salaire') }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Expérience minimale (années)</label>
            <input type="number" min="0" max="60" step="0.5" v-model="form.experience_min" :class="classeChamp('experience_min')">
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Niveau d'études (optionnel)</label>
          <select v-model="form.niveau_etude" :class="classeChamp('niveau_etude')" class="bg-white">
            <option value="">Non précisé</option>
            <option value="bac">Baccalauréat</option>
            <option value="bac_2">Bac +2 (DUT, BTS)</option>
            <option value="bac_3">Bac +3 (Licence)</option>
            <option value="bac_5">Bac +5 (Master, ingénieur)</option>
            <option value="bac_8">Bac +8 (Doctorat)</option>
          </select>
        </div>
      </div>
    </div>

    <div class="border-t border-gray-100"></div>

    <!-- Section 2 : description et compétences requises -->
    <div>
      <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
        <span class="bg-purple-100 text-purple-600 p-1.5 rounded-lg text-sm">📝</span>
        Description du poste
      </h3>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
          <textarea v-model="form.description" rows="4" placeholder="Décrivez les missions principales..." :class="classeChamp('description')" class="resize-none"></textarea>
          <p v-if="erreur('description')" class="mt-1 text-xs text-red-600">{{ erreur('description') }}</p>
        </div>

        <!-- RG19/RG21 : compétences du référentiel, avec niveau et importance -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Compétences requises</label>
          <div v-for="(ligne, index) in form.competences" :key="ligne.id_competence" class="flex flex-wrap items-center gap-2 mb-2">
            <span class="flex-1 min-w-[8rem] px-3 py-2 bg-blue-50 text-blue-700 rounded-lg text-sm font-medium">{{ nomCompetence(ligne.id_competence) }}</span>
            <select v-model="ligne.niveau_requis" class="px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm" aria-label="Niveau requis">
              <option value="debutant">Débutant</option>
              <option value="intermediaire">Intermédiaire</option>
              <option value="avance">Avancé</option>
              <option value="expert">Expert</option>
            </select>
            <select v-model="ligne.importance" class="px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm" aria-label="Importance">
              <option value="essentielle">Essentielle</option>
              <option value="importante">Importante</option>
              <option value="souhaitee">Souhaitée</option>
            </select>
            <button type="button" @click="form.competences.splice(index, 1)" class="px-3 py-2 text-red-500 hover:bg-red-50 rounded-lg text-sm" :aria-label="'Retirer ' + nomCompetence(ligne.id_competence)">✕</button>
          </div>
          <select v-model="competenceAjoutee" @change="ajouterCompetence" class="w-full px-4 py-3 rounded-xl border border-dashed border-gray-300 bg-white text-sm text-gray-600">
            <option value="">+ Ajouter une compétence du référentiel</option>
            <option v-for="c in competencesDisponibles" :key="c.id_competence" :value="c.id_competence">{{ c.nom }}</option>
          </select>
          <p v-if="erreurCompetences" class="mt-1 text-xs text-red-600">{{ erreurCompetences }}</p>
        </div>
      </div>
    </div>

    <div class="border-t border-gray-100"></div>

    <!-- Section 3 : paramètres de publication -->
    <div>
      <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
        <span class="bg-green-100 text-green-600 p-1.5 rounded-lg text-sm">⚙️</span>
        Paramètres de publication
      </h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Statut</label>
          <select v-model="form.statut" :class="classeChamp('statut')" class="bg-white">
            <option value="ouverte">Ouverte</option>
            <option value="suspendue">Suspendue</option>
            <option value="fermee">Fermée</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Date d'expiration (optionnel)</label>
          <input type="date" v-model="form.date_expiration" :min="aujourdhui" :class="classeChamp('date_expiration')">
          <p v-if="erreur('date_expiration')" class="mt-1 text-xs text-red-600">{{ erreur('date_expiration') }}</p>
        </div>
      </div>
    </div>

    <div class="flex items-center justify-end gap-4 pt-4">
      <button type="button" @click="$emit('annuler')" class="px-6 py-3 text-gray-600 font-medium hover:bg-gray-50 rounded-xl transition-all">
        Annuler
      </button>
      <button type="submit" :disabled="envoiEnCours" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-semibold rounded-xl transition-all shadow-sm hover:shadow-lg hover:shadow-blue-200 flex items-center gap-2">
        {{ envoiEnCours ? 'Enregistrement...' : libelleBouton }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import api from '../services/api'

const props = defineProps({
  // Entreprise dont les départements sont proposés (recruteur connecté).
  idEntreprise: { type: Number, default: null },
  // Administrateur : liste des recruteurs au nom desquels publier.
  recruteurs: { type: Array, default: null },
  envoiEnCours: { type: Boolean, default: false },
  // Erreurs de validation renvoyées par l'API (422), indexées par champ.
  erreurs: { type: Object, default: () => ({}) },
  // Modification : l'offre existante sert de valeurs initiales.
  offre: { type: Object, default: null },
  libelleBouton: { type: String, default: "Publier l'offre" },
})

const emit = defineEmits(['soumettre', 'annuler'])

const aujourdhui = new Date().toISOString().slice(0, 10)

const form = reactive({
  id_recruteur: '',
  titre: '',
  description: '',
  type_contrat: 'cdi',
  localisation: '',
  salaire: '',
  experience_min: 0,
  niveau_etude: '',
  id_departement: '',
  statut: 'ouverte',
  date_expiration: '',
  competences: [],
})

// Modification : pré-remplissage depuis l'offre existante.
if (props.offre) {
  const o = props.offre
  Object.assign(form, {
    titre: o.titre || '',
    description: o.description || '',
    type_contrat: o.type_contrat || 'cdi',
    localisation: o.localisation || '',
    salaire: o.salaire ?? '',
    experience_min: o.experience_min ?? 0,
    niveau_etude: o.niveau_etude || '',
    id_departement: o.id_departement || '',
    statut: o.statut || 'ouverte',
    date_expiration: o.date_expiration || '',
    competences: (o.competences || []).map(c => ({
      id_competence: c.id_competence,
      niveau_requis: c.niveau_requis,
      importance: c.importance,
    })),
  })
}

const departements = ref([])
const referentiel = ref([])
const competenceAjoutee = ref('')

// RG9/RG11 : le département relève de l'entreprise de l'auteur de l'offre.
const entrepriseCible = computed(() => {
  if (props.recruteurs) {
    const r = props.recruteurs.find(r => r.id_recruteur === form.id_recruteur)
    return r?.entreprise?.id_entreprise || null
  }
  return props.idEntreprise
})

const chargerDepartements = async (idEntreprise) => {
  departements.value = []
  if (!idEntreprise) {
    form.id_departement = ''
    return
  }
  try {
    const response = await api.get(`/entreprises/${idEntreprise}/departements`, { params: { per_page: 100 } })
    departements.value = response.data.departements || []
    // Un département d'une autre entreprise n'est plus valable (changement de recruteur).
    if (!departements.value.some(d => d.id_departement === form.id_departement)) {
      form.id_departement = ''
    }
  } catch (err) {
    console.error('Erreur départements:', err)
  }
}

watch(entrepriseCible, chargerDepartements, { immediate: true })

const competencesDisponibles = computed(() => {
  const prises = new Set(form.competences.map(c => c.id_competence))
  return referentiel.value.filter(c => !prises.has(c.id_competence))
})

const nomCompetence = (id) => referentiel.value.find(c => c.id_competence === id)?.nom || '…'

const ajouterCompetence = () => {
  if (!competenceAjoutee.value) return
  form.competences.push({
    id_competence: Number(competenceAjoutee.value),
    niveau_requis: 'intermediaire',
    importance: 'importante',
  })
  competenceAjoutee.value = ''
}

const erreur = (champ) => props.erreurs?.[champ]?.[0] || ''

const erreurCompetences = computed(() => {
  const cle = Object.keys(props.erreurs || {}).find(k => k.startsWith('competences'))
  return cle ? props.erreurs[cle][0] : ''
})

const classeChamp = (champ) => [
  'w-full px-4 py-3 rounded-xl border outline-none transition-all focus:ring-2',
  erreur(champ)
    ? 'border-red-300 focus:border-red-500 focus:ring-red-100'
    : 'border-gray-200 focus:border-blue-500 focus:ring-blue-100',
]

// Les champs optionnels vides ne sont pas envoyés : l'API les rejetterait.
const soumettre = () => {
  const payload = {
    titre: form.titre.trim(),
    description: form.description.trim(),
    type_contrat: form.type_contrat,
    localisation: form.localisation.trim(),
    id_departement: form.id_departement || null,
    experience_min: Number(form.experience_min) || 0,
    statut: form.statut,
    competences: form.competences.map(c => ({ ...c })),
  }
  if (form.salaire !== '' && form.salaire !== null) payload.salaire = Number(form.salaire)
  if (form.niveau_etude) payload.niveau_etude = form.niveau_etude
  if (form.date_expiration) payload.date_expiration = form.date_expiration
  if (props.recruteurs) payload.id_recruteur = form.id_recruteur || null

  emit('soumettre', payload)
}

onMounted(async () => {
  try {
    const response = await api.get('/competences', { params: { per_page: 100 } })
    referentiel.value = response.data.competences || []
  } catch (err) {
    console.error('Erreur référentiel:', err)
  }
})
</script>
