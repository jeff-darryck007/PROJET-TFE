import { ref, onMounted, onUnmounted } from "vue";

// Heure actuelle (en ms), mise à jour chaque seconde : sert de base aux comptes à rebours
export function useNow() {
  const now = ref(Date.now());
  let timer = null;
  onMounted(() => { timer = setInterval(() => { now.value = Date.now(); }, 1000); });
  onUnmounted(() => clearInterval(timer));
  return now;
}

// Temps restant (en ms) avant une date limite ISO renvoyée par l'API ; null s'il n'y a pas de limite
export function remainingMs(expiresAt, now) {
  if (!expiresAt) return null;
  return Math.max(0, new Date(expiresAt).getTime() - now);
}

// 35999000 → "9 h 59 min 59 s"
export function formatRemaining(ms) {
  const total = Math.floor(ms / 1000);
  const h = Math.floor(total / 3600);
  const m = Math.floor((total % 3600) / 60);
  const s = total % 60;
  return `${h} h ${String(m).padStart(2, "0")} min ${String(s).padStart(2, "0")} s`;
}
