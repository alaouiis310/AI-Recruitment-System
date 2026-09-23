import { useAuthStore } from '../stores/auth'

// Offres sauvegardées par le candidat.
//
// Le MLD ne prévoit pas de table pour ces favoris : ils sont conservés dans
// le navigateur, par utilisateur, et ne suivent donc pas le candidat d'un
// appareil à l'autre. Les offres elles-mêmes restent lues depuis l'API.
const cle = () => `offres_sauvegardees_${useAuthStore().utilisateur?.id_user ?? 'anonyme'}`

// Chaque entrée : { id: id_offre, le: date de sauvegarde ISO }.
const entrees = () => {
  try {
    const valeur = JSON.parse(localStorage.getItem(cle()))
    return Array.isArray(valeur) ? valeur.filter(e => e && typeof e.id === 'number') : []
  } catch {
    return []
  }
}

const ecrire = (liste) => {
  try {
    localStorage.setItem(cle(), JSON.stringify(liste))
  } catch {
    // Stockage indisponible (navigation privée, quota) : on ignore.
  }
}

const lire = () => entrees().map(e => e.id)

export const offresSauvegardees = {
  lire,
  contient: (idOffre) => lire().includes(idOffre),
  dateDe: (idOffre) => entrees().find(e => e.id === idOffre)?.le || null,

  /** Ajoute ou retire l'offre ; renvoie true si elle est désormais sauvegardée. */
  basculer(idOffre) {
    const liste = entrees()
    const dejaLa = liste.some(e => e.id === idOffre)
    ecrire(dejaLa ? liste.filter(e => e.id !== idOffre) : [...liste, { id: idOffre, le: new Date().toISOString() }])
    return !dejaLa
  },

  retirer(idOffre) {
    ecrire(entrees().filter(e => e.id !== idOffre))
  },
}
