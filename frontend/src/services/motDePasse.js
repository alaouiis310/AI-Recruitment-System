// Mot de passe provisoire conforme à la règle de l'API :
// 8 caractères minimum, au moins une lettre et un chiffre.
const LETTRES = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ'
const CHIFFRES = '23456789'

const tirer = (alphabet) => {
  const valeur = new Uint32Array(1)
  crypto.getRandomValues(valeur)
  return alphabet[valeur[0] % alphabet.length]
}

export const motDePasseProvisoire = (longueur = 12) => {
  const caracteres = [tirer(LETTRES), tirer(CHIFFRES)]
  while (caracteres.length < longueur) {
    caracteres.push(tirer(LETTRES + CHIFFRES))
  }
  // Mélange, pour que la lettre et le chiffre garantis ne soient pas en tête.
  for (let i = caracteres.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1))
    ;[caracteres[i], caracteres[j]] = [caracteres[j], caracteres[i]]
  }
  return caracteres.join('')
}
