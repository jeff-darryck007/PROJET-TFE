<template>
  <div class="forgot-page">
    <DarkToggle />

    <!-- PANNEAU GAUCHE -->
    <div class="left-panel">
      <div class="brand" @click="goToLogin">
        <div class="brand-icon">
          <i class="fas fa-hands-helping"></i>
        </div>
        <h1>Partage Gratuit</h1>
      </div>

      <div class="info-block">
        <h2>Récupération de compte</h2>
        <p>Suivez ces trois étapes simples pour retrouver l'accès à votre compte.</p>

        <div class="steps">
          <div class="step" :class="{ 'step--active': currentStep === 1, 'step--done': currentStep > 1 }">
            <span class="step-num">
              <i v-if="currentStep > 1" class="fas fa-check"></i>
              <template v-else>1</template>
            </span>
            <div class="step-text">
              <strong>Entrez votre email</strong>
              <span>Nous vous envoyons un code</span>
            </div>
          </div>
          <div class="step-connector" :class="{ 'step-connector--done': currentStep > 1 }"></div>
          <div class="step" :class="{ 'step--active': currentStep === 2, 'step--done': currentStep > 2 }">
            <span class="step-num">
              <i v-if="currentStep > 2" class="fas fa-check"></i>
              <template v-else>2</template>
            </span>
            <div class="step-text">
              <strong>Entrez le code reçu</strong>
              <span>Code valable 2 minutes</span>
            </div>
          </div>
          <div class="step-connector" :class="{ 'step-connector--done': currentStep > 2 }"></div>
          <div class="step" :class="{ 'step--active': currentStep === 3, 'step--done': currentStep > 3 }">
            <span class="step-num">
              <i v-if="currentStep > 3" class="fas fa-check"></i>
              <template v-else>3</template>
            </span>
            <div class="step-text">
              <strong>Nouveau mot de passe</strong>
              <span>Reconnectez-vous ensuite</span>
            </div>
          </div>
        </div>
      </div>

      <p class="back-hint">
        <a @click="goToLogin"><i class="fas fa-arrow-left"></i> Retour à la connexion</a>
      </p>
    </div>

    <!-- PANNEAU DROIT -->
    <div class="right-panel">

      <!-- ÉTAPE 1 : Email -->
      <div v-if="currentStep === 1" class="form-card">
        <div class="form-icon">
          <i class="fas fa-lock-open"></i>
        </div>
        <h2>Mot de passe oublié ?</h2>
        <p class="form-subtitle">
          Entrez votre adresse email. Nous vous enverrons un code à 6 chiffres valable 2 minutes.
        </p>

        <form @submit.prevent="handleSendCode" autocomplete="off">
          <div class="input-group">
            <label for="forgot-email">Adresse email</label>
            <div class="field">
              <i class="fas fa-envelope"></i>
              <input
                id="forgot-email"
                type="email"
                v-model="email"
                placeholder="jean.dupont@email.com"
                autocomplete="off"
                required
              />
            </div>
          </div>

          <div v-if="errorMessage" class="msg-error">
            <i class="fas fa-exclamation-circle"></i> {{ errorMessage }}
          </div>

          <button type="submit" class="submit-btn" :disabled="loading">
            <i :class="loading ? 'fas fa-spinner fa-spin' : 'fas fa-paper-plane'"></i>
            {{ loading ? "Envoi en cours..." : "Envoyer le code" }}
          </button>
        </form>

        <p class="mobile-back">
          <a @click="goToLogin"><i class="fas fa-arrow-left"></i> Retour à la connexion</a>
        </p>
      </div>

      <!-- ÉTAPE 2 : Code -->
      <div v-else-if="currentStep === 2" class="form-card">
        <div class="form-icon" style="background:#f0fdf4">
          <i class="fas fa-envelope-open-text" style="color:#16a34a"></i>
        </div>
        <h2>Code de vérification</h2>
        <p class="form-subtitle">Un code à 6 chiffres a été envoyé à</p>
        <p class="email-display">{{ email }}</p>

        <form @submit.prevent="handleVerifyCode" autocomplete="off">
          <div class="input-group">
            <label for="code-input">Code à 6 chiffres</label>
            <div class="field">
              <i class="fas fa-hashtag"></i>
              <input
                id="code-input"
                type="text"
                v-model="code"
                placeholder="123456"
                maxlength="6"
                inputmode="numeric"
                autocomplete="one-time-code"
                required
              />
            </div>
          </div>

          <div class="countdown" :class="{ expired: timeLeft === 0 }">
            <i :class="timeLeft > 0 ? 'fas fa-clock' : 'fas fa-exclamation-triangle'"></i>
            <span v-if="timeLeft > 0">
              Code expire dans <strong>{{ formattedTime }}</strong>
            </span>
            <span v-else>Code expiré — renvoyez-en un nouveau</span>
          </div>

          <div v-if="errorMessage" class="msg-error">
            <i class="fas fa-exclamation-circle"></i> {{ errorMessage }}
          </div>

          <button type="submit" class="submit-btn" :disabled="loading || timeLeft === 0">
            <i :class="loading ? 'fas fa-spinner fa-spin' : 'fas fa-arrow-right'"></i>
            {{ loading ? "Vérification..." : "Continuer" }}
          </button>
        </form>

        <button class="retry-btn" @click="resendCode" :disabled="resending">
          <i :class="resending ? 'fas fa-spinner fa-spin' : 'fas fa-redo'"></i>
          {{ resending ? "Renvoi..." : "Renvoyer le code" }}
        </button>
      </div>

      <!-- ÉTAPE 3 : Nouveau mot de passe -->
      <div v-else-if="currentStep === 3" class="form-card">
        <div class="form-icon">
          <i class="fas fa-key"></i>
        </div>
        <h2>Nouveau mot de passe</h2>
        <p class="form-subtitle">
          Choisissez un mot de passe fort d'au moins 8 caractères, avec des lettres et des chiffres.
        </p>

        <form @submit.prevent="handleResetPassword" autocomplete="off">

          <div class="input-group">
            <label for="new-pwd">Nouveau mot de passe</label>
            <div class="field">
              <i class="fas fa-lock"></i>
              <input
                id="new-pwd"
                :type="showNew ? 'text' : 'password'"
                v-model="newPassword"
                placeholder="••••••••"
                autocomplete="new-password"
                required
              />
              <span class="eye" @click="showNew = !showNew">
                <i :class="showNew ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
              </span>
            </div>
          </div>

          <div class="input-group">
            <label for="confirm-pwd">Confirmer le mot de passe</label>
            <div class="field">
              <i class="fas fa-lock"></i>
              <input
                id="confirm-pwd"
                :type="showConfirm ? 'text' : 'password'"
                v-model="confirmPassword"
                placeholder="••••••••"
                autocomplete="new-password"
                required
              />
              <span class="eye" @click="showConfirm = !showConfirm">
                <i :class="showConfirm ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
              </span>
            </div>
          </div>

          <div v-if="newPassword" class="strength-bar">
            <div class="strength-track">
              <div class="strength-fill" :class="strengthClass" :style="{ width: strengthWidth }"></div>
            </div>
            <span class="strength-label" :class="strengthClass">{{ strengthLabel }}</span>
          </div>

          <div v-if="errorMessage" class="msg-error">
            <i class="fas fa-exclamation-circle"></i> {{ errorMessage }}
          </div>

          <button type="submit" class="submit-btn" :disabled="loading">
            <i :class="loading ? 'fas fa-spinner fa-spin' : 'fas fa-shield-alt'"></i>
            {{ loading ? "Enregistrement..." : "Enregistrer le mot de passe" }}
          </button>
        </form>
      </div>

      <!-- ÉTAPE 4 : Succès -->
      <div v-else class="form-card">
        <div class="success-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <h2>Mot de passe mis à jour !</h2>
        <p class="form-subtitle">
          Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.
        </p>
        <button class="submit-btn" @click="goToLogin">
          <i class="fas fa-sign-in-alt"></i> Se connecter
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from "vue"
import { useRouter } from "vue-router"
import DarkToggle from "../components/DarkToggle.vue"
import { BASE_URL } from "../server/config.js"

const API = `${BASE_URL}/api`

const router = useRouter()
const goToLogin = () => router.push("/login")

const currentStep = ref(1)
const email = ref("")
const code = ref("")
const newPassword = ref("")
const confirmPassword = ref("")
const showNew = ref(false)
const showConfirm = ref(false)
const loading = ref(false)
const resending = ref(false)
const errorMessage = ref("")

// --- Countdown ---
const timeLeft = ref(120)
let countdownInterval = null

const formattedTime = computed(() => {
  const m = Math.floor(timeLeft.value / 60)
  const s = timeLeft.value % 60
  return `${m}:${s.toString().padStart(2, "0")}`
})

function startCountdown() {
  clearInterval(countdownInterval)
  timeLeft.value = 120
  countdownInterval = setInterval(() => {
    if (timeLeft.value > 0) timeLeft.value--
    else clearInterval(countdownInterval)
  }, 1000)
}

onUnmounted(() => clearInterval(countdownInterval))

// --- Password strength ---
const strengthScore = computed(() => {
  const p = newPassword.value
  if (!p) return 0
  let s = 0
  if (p.length >= 8) s++
  if (p.length >= 12) s++
  if (/[A-Z]/.test(p)) s++
  if (/[0-9]/.test(p)) s++
  if (/[^A-Za-z0-9]/.test(p)) s++
  return s
})
const strengthClass = computed(() => {
  if (strengthScore.value <= 1) return "weak"
  if (strengthScore.value <= 3) return "medium"
  return "strong"
})
const strengthWidth = computed(() => `${(strengthScore.value / 5) * 100}%`)
const strengthLabel = computed(() => {
  if (strengthScore.value <= 1) return "Faible"
  if (strengthScore.value <= 3) return "Moyen"
  return "Fort"
})

// --- ÉTAPE 1 : envoyer le code ---
async function handleSendCode() {
  errorMessage.value = ""
  loading.value = true
  try {
    const res = await fetch(`${API}/forgot-password`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email: email.value })
    })
    const data = await res.json()
    if (!res.ok) {
      errorMessage.value = data.error || "Une erreur est survenue."
      return
    }
    currentStep.value = 2
    startCountdown()
  } catch {
    errorMessage.value = "Impossible de contacter le serveur."
  } finally {
    loading.value = false
  }
}

// --- ÉTAPE 2 : vérifier le code (format seulement) ---
function handleVerifyCode() {
  errorMessage.value = ""
  if (!/^\d{6}$/.test(code.value)) {
    errorMessage.value = "Le code doit contenir exactement 6 chiffres."
    return
  }
  clearInterval(countdownInterval)
  currentStep.value = 3
}

// --- ÉTAPE 2 : renvoyer le code ---
async function resendCode() {
  resending.value = true
  errorMessage.value = ""
  try {
    const res = await fetch(`${API}/forgot-password`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email: email.value })
    })
    const data = await res.json()
    if (!res.ok) {
      errorMessage.value = data.error || "Une erreur est survenue."
      return
    }
    code.value = ""
    startCountdown()
  } catch {
    errorMessage.value = "Impossible de contacter le serveur."
  } finally {
    resending.value = false
  }
}

// --- ÉTAPE 3 : réinitialiser le mot de passe ---
async function handleResetPassword() {
  errorMessage.value = ""
  if (newPassword.value.length < 8) {
    errorMessage.value = "Le mot de passe doit contenir au moins 8 caractères."
    return
  }
  if (newPassword.value !== confirmPassword.value) {
    errorMessage.value = "Les mots de passe ne correspondent pas."
    return
  }
  loading.value = true
  try {
    const res = await fetch(`${API}/reset-password`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        email: email.value,
        code: code.value,
        password: newPassword.value
      })
    })
    const data = await res.json()
    if (!res.ok) {
      errorMessage.value = data.error || "Une erreur est survenue."
      if (data.error && data.error.includes("expiré")) {
        // Code expired → restart from step 1
        currentStep.value = 1
        code.value = ""
      } else {
        // Wrong code → back to step 2
        currentStep.value = 2
        startCountdown()
      }
      return
    }
    currentStep.value = 4
  } catch {
    errorMessage.value = "Impossible de contacter le serveur."
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* === PAGE === */
.forgot-page {
  min-height: 100vh;
  display: flex;
  font-family: Arial, sans-serif;
}

/* === PANNEAU GAUCHE === */
.left-panel {
  width: 42%;
  background: linear-gradient(160deg, #0a3d7a 0%, #0054a6 55%, #1a72cc 100%);
  color: white;
  padding: 60px 48px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 40px;
}

.brand {
  display: flex;
  align-items: center;
  gap: 14px;
  cursor: pointer;
}

.brand-icon {
  width: 48px;
  height: 48px;
  background: rgba(255, 255, 255, 0.15);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.brand-icon i {
  font-size: 22px;
  color: #f1b800;
}

.brand h1 {
  font-size: 22px;
  font-weight: 800;
  margin: 0;
}

.info-block {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 24px;
}

.info-block h2 {
  font-size: 26px;
  font-weight: 800;
  margin: 0;
  letter-spacing: -0.3px;
}

.info-block p {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.75);
  margin: 0;
  line-height: 1.6;
}

/* === ÉTAPES === */
.steps {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.step {
  display: flex;
  align-items: flex-start;
  gap: 14px;
}

.step-connector {
  width: 2px;
  height: 20px;
  background: rgba(255, 255, 255, 0.2);
  margin-left: 17px;
}

.step-connector--done {
  background: rgba(241, 184, 0, 0.5);
}

.step-num {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.15);
  border: 2px solid rgba(255, 255, 255, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
  flex-shrink: 0;
  color: rgba(255, 255, 255, 0.6);
}

.step--done .step-num {
  background: rgba(241, 184, 0, 0.2);
  border-color: #f1b800;
  color: #f1b800;
}

.step--active .step-num {
  background: #f1b800;
  border-color: #f1b800;
  color: #222;
}

.step-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding-top: 6px;
}

.step-text strong {
  font-size: 14px;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.5);
}

.step--active .step-text strong,
.step--done .step-text strong {
  color: white;
}

.step-text span {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.45);
}

.step--active .step-text span,
.step--done .step-text span {
  color: rgba(255, 255, 255, 0.7);
}

.back-hint {
  font-size: 14px;
  margin: 0;
}

.back-hint a {
  color: rgba(255, 255, 255, 0.7);
  cursor: pointer;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: color 0.2s;
}

.back-hint a:hover { color: #f1b800; }

/* === PANNEAU DROIT === */
.right-panel {
  flex: 1;
  background: #f4f6f9;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 24px;
}

.form-card {
  background: white;
  border-radius: 16px;
  padding: 44px;
  width: 100%;
  max-width: 440px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
  text-align: center;
}

/* === ICÔNES === */
.form-icon {
  width: 68px;
  height: 68px;
  background: #eef4ff;
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
}

.form-icon i {
  font-size: 30px;
  color: #0054a6;
}

.success-icon {
  width: 68px;
  height: 68px;
  background: #f0fdf4;
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
}

.success-icon i {
  font-size: 30px;
  color: #16a34a;
}

/* === TEXTES === */
.form-card h2 {
  font-size: 22px;
  font-weight: 800;
  color: #222;
  margin: 0 0 10px 0;
}

.form-subtitle {
  font-size: 14px;
  color: #666;
  line-height: 1.6;
  margin: 0 0 16px 0;
}

.email-display {
  font-size: 15px;
  font-weight: 700;
  color: #0054a6;
  background: #eef4ff;
  padding: 8px 16px;
  border-radius: 8px;
  margin: 0 0 20px 0;
  word-break: break-all;
}

/* === COUNTDOWN === */
.countdown {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #555;
  background: #f9fafb;
  border-radius: 8px;
  padding: 10px 14px;
  margin-bottom: 16px;
  text-align: left;
  border: 1px solid #e8ecf0;
}

.countdown i { color: #0054a6; }

.countdown.expired {
  background: #fff5f5;
  border-color: #fecaca;
  color: #dc2626;
}

.countdown.expired i { color: #dc2626; }

/* === CHAMP === */
.input-group {
  display: flex;
  flex-direction: column;
  margin-bottom: 16px;
  text-align: left;
}

.input-group label {
  font-size: 13px;
  font-weight: 700;
  color: #444;
  margin-bottom: 7px;
}

.field {
  display: flex;
  align-items: center;
  border: 2px solid #e8ecf0;
  border-radius: 10px;
  background: white;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.field:focus-within {
  border-color: #0054a6;
  box-shadow: 0 0 0 3px rgba(0, 84, 166, 0.08);
}

.field > i {
  padding: 0 14px;
  color: #f1b800;
  font-size: 15px;
  pointer-events: none;
}

.field input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 14px;
  background: transparent;
  padding: 12px 12px 12px 0;
  font-family: inherit;
}

.eye {
  padding: 0 14px;
  cursor: pointer;
  color: #aaa;
  transition: color 0.2s;
  font-size: 14px;
}

.eye:hover { color: #f1b800; }

/* === ERREUR === */
.msg-error {
  background: #fff5f5;
  color: #dc2626;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 13px;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
  text-align: left;
}

/* === FORCE DU MDP === */
.strength-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
}

.strength-track {
  flex: 1;
  height: 5px;
  background: #e8ecf0;
  border-radius: 99px;
  overflow: hidden;
}

.strength-fill {
  height: 100%;
  border-radius: 99px;
  transition: width 0.3s, background 0.3s;
}

.strength-fill.weak   { background: #dc2626; }
.strength-fill.medium { background: #f59e0b; }
.strength-fill.strong { background: #16a34a; }

.strength-label {
  font-size: 12px;
  font-weight: 700;
  min-width: 40px;
  text-align: right;
}

.strength-label.weak   { color: #dc2626; }
.strength-label.medium { color: #f59e0b; }
.strength-label.strong { color: #16a34a; }

/* === BOUTONS === */
.submit-btn {
  width: 100%;
  padding: 14px;
  background: #f1b800;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s, transform 0.1s;
  box-shadow: 0 4px 0 #c89b00;
  font-family: inherit;
  color: #222;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-bottom: 12px;
}

.submit-btn:hover {
  background: #dca600;
  transform: translateY(-1px);
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.retry-btn {
  width: 100%;
  padding: 12px;
  background: transparent;
  border: 2px solid #e8ecf0;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  color: #666;
  cursor: pointer;
  transition: border-color 0.2s, color 0.2s;
  font-family: inherit;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.retry-btn:hover {
  border-color: #0054a6;
  color: #0054a6;
}

.retry-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* === LIEN MOBILE === */
.mobile-back {
  margin-top: 20px;
  font-size: 14px;
  display: none;
}

.mobile-back a {
  color: #0054a6;
  cursor: pointer;
  text-decoration: none;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.mobile-back a:hover { text-decoration: underline; }

/* === RESPONSIVE === */
@media (max-width: 900px) {
  .left-panel  { display: none; }
  .right-panel { width: 100%; padding: 24px 16px; }
  .form-card   { box-shadow: none; background: transparent; padding: 30px 8px; }
  .mobile-back { display: block; }
}
</style>
