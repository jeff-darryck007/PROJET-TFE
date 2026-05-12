<template>
  <div class="reset-page">

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
        <p>Vous y êtes presque ! Créez votre nouveau mot de passe.</p>

        <div class="steps">
          <div class="step step--done">
            <span class="step-num"><i class="fas fa-check"></i></span>
            <div class="step-text">
              <strong>Email envoyé</strong>
              <span>Lien de réinitialisation reçu</span>
            </div>
          </div>
          <div class="step-connector step-connector--done"></div>
          <div class="step step--done">
            <span class="step-num"><i class="fas fa-check"></i></span>
            <div class="step-text">
              <strong>Lien vérifié</strong>
              <span>Identité confirmée</span>
            </div>
          </div>
          <div class="step-connector step-connector--done"></div>
          <div class="step step--active">
            <span class="step-num">3</span>
            <div class="step-text">
              <strong>Nouveau mot de passe</strong>
              <span>Choisissez un mot de passe sécurisé</span>
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

      <!-- Formulaire nouveau mot de passe -->
      <div v-if="!success" class="form-card">
        <div class="form-icon">
          <i class="fas fa-key"></i>
        </div>

        <h2>Nouveau mot de passe</h2>
        <p class="form-subtitle">
          Choisissez un mot de passe fort d'au moins 8 caractères, avec des lettres et des chiffres.
        </p>

        <form @submit.prevent="handleResetPassword" autocomplete="off">

          <!-- Nouveau mot de passe -->
          <div class="input-group">
            <label for="new-pwd">Nouveau mot de passe</label>
            <div class="field" :class="{ error: errorMessage }">
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

          <!-- Confirmation -->
          <div class="input-group">
            <label for="confirm-pwd">Confirmer le mot de passe</label>
            <div class="field" :class="{ error: errorMessage }">
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

          <!-- Erreur -->
          <div v-if="errorMessage" class="msg-error">
            <i class="fas fa-exclamation-circle"></i> {{ errorMessage }}
          </div>

          <!-- Indicateur de force -->
          <div v-if="newPassword" class="strength-bar">
            <div class="strength-track">
              <div class="strength-fill" :class="strengthClass" :style="{ width: strengthWidth }"></div>
            </div>
            <span class="strength-label" :class="strengthClass">{{ strengthLabel }}</span>
          </div>

          <button type="submit" class="submit-btn" :disabled="loading">
            <i :class="loading ? 'fas fa-spinner fa-spin' : 'fas fa-shield-alt'"></i>
            {{ loading ? "Enregistrement..." : "Enregistrer le mot de passe" }}
          </button>

        </form>

        <p class="mobile-back">
          <a @click="goToLogin"><i class="fas fa-arrow-left"></i> Retour à la connexion</a>
        </p>
      </div>

      <!-- Succès -->
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
import { ref, computed } from "vue"
import { useRouter } from "vue-router"

const router = useRouter()

const newPassword = ref("")
const confirmPassword = ref("")
const showNew = ref(false)
const showConfirm = ref(false)
const loading = ref(false)
const errorMessage = ref("")
const success = ref(false)

const goToLogin = () => router.push("/login")

const strengthScore = computed(() => {
  const p = newPassword.value
  if (!p) return 0
  let score = 0
  if (p.length >= 8) score++
  if (p.length >= 12) score++
  if (/[A-Z]/.test(p)) score++
  if (/[0-9]/.test(p)) score++
  if (/[^A-Za-z0-9]/.test(p)) score++
  return score
})

const strengthClass = computed(() => {
  if (strengthScore.value <= 1) return "weak"
  if (strengthScore.value <= 3) return "medium"
  return "strong"
})

const strengthWidth = computed(() => {
  return `${(strengthScore.value / 5) * 100}%`
})

const strengthLabel = computed(() => {
  if (strengthScore.value <= 1) return "Faible"
  if (strengthScore.value <= 3) return "Moyen"
  return "Fort"
})

const handleResetPassword = async () => {
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
  await new Promise(r => setTimeout(r, 800))
  loading.value = false
  success.value = true
}
</script>

<style scoped>
/* === PAGE === */
.reset-page {
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
}

.info-block > p {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.75);
  margin: 0;
  line-height: 1.6;
}

/* === ÉTAPES === */
.steps {
  display: flex;
  flex-direction: column;
}

.step {
  display: flex;
  align-items: flex-start;
  gap: 14px;
}

.step-connector {
  width: 2px;
  height: 20px;
  background: rgba(255, 255, 255, 0.15);
  margin-left: 17px;
}

.step-connector--done {
  background: rgba(241, 184, 0, 0.5);
}

.step-num {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  border: 2px solid rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 700;
  flex-shrink: 0;
  color: rgba(255, 255, 255, 0.4);
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
  color: rgba(255, 255, 255, 0.4);
}

.step--done .step-text strong,
.step--active .step-text strong {
  color: white;
}

.step-text span {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.35);
}

.step--done .step-text span,
.step--active .step-text span {
  color: rgba(255, 255, 255, 0.65);
}

.back-hint {
  font-size: 14px;
  margin: 0;
}

.back-hint a {
  color: rgba(255, 255, 255, 0.7);
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: color 0.2s;
}

.back-hint a:hover {
  color: #f1b800;
}

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
  font-size: 28px;
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
  margin: 0 0 28px 0;
}

/* === CHAMPS === */
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

.field.error {
  border-color: #dc2626;
  background: #fff5f5;
}

.field i:first-child {
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
  padding: 12px 0;
  font-family: inherit;
}

.eye {
  padding: 0 14px;
  cursor: pointer;
  color: #aaa;
  transition: color 0.2s;
  font-size: 14px;
}

.eye:hover {
  color: #f1b800;
}

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
  margin-bottom: 20px;
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

.strength-fill.weak { background: #dc2626; }
.strength-fill.medium { background: #f59e0b; }
.strength-fill.strong { background: #16a34a; }

.strength-label {
  font-size: 12px;
  font-weight: 700;
  min-width: 40px;
  text-align: right;
}

.strength-label.weak { color: #dc2626; }
.strength-label.medium { color: #f59e0b; }
.strength-label.strong { color: #16a34a; }

/* === BOUTON === */
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
}

.submit-btn:hover {
  background: #dca600;
  transform: translateY(-1px);
}

.submit-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
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

.mobile-back a:hover {
  text-decoration: underline;
}

/* === RESPONSIVE === */
@media (max-width: 900px) {
  .left-panel { display: none; }
  .right-panel { width: 100%; padding: 24px 16px; }
  .form-card { box-shadow: none; background: transparent; padding: 30px 8px; }
  .mobile-back { display: block; }
}
</style>
