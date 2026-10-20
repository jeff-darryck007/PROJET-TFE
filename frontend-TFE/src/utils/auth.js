// Gestion de la session de connexion côté navigateur.
// Le jeton JWT est valable 1 h (token_ttl côté Symfony) : passé ce délai, la session est effacée,
// même si le navigateur a été fermé puis rouvert.

const SESSION_KEYS = ["token", "idUser", "roles"];

// Date d'expiration du jeton (ms), lue dans sa partie « payload » (champ exp, en secondes)
export function tokenExpiry(token) {
  try {
    const payload = token.split(".")[1].replace(/-/g, "+").replace(/_/g, "/");
    const exp = JSON.parse(atob(payload)).exp;
    return typeof exp === "number" ? exp * 1000 : null;
  } catch {
    return null; // jeton illisible : considéré comme expiré
  }
}

export function clearSession() {
  SESSION_KEYS.forEach((k) => localStorage.removeItem(k));
}

// Efface la session si le jeton est absent, illisible ou expiré. Renvoie true si la session est valide.
export function ensureValidSession() {
  const token = localStorage.getItem("token");
  if (!token) return false;
  const exp = tokenExpiry(token);
  if (!exp || exp <= Date.now()) {
    clearSession();
    return false;
  }
  return true;
}

// Déconnexion automatique au moment exact de l'expiration, si la page reste ouverte
let expiryTimer = null;
export function scheduleExpiry(onExpire) {
  clearTimeout(expiryTimer);
  const token = localStorage.getItem("token");
  const exp = token && tokenExpiry(token);
  if (!exp) return;
  // setTimeout est limité à ~24,8 jours : largement suffisant pour une session d'1 h
  expiryTimer = setTimeout(() => {
    clearSession();
    onExpire();
  }, Math.max(0, exp - Date.now()));
}
