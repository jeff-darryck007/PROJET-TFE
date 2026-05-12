<template>
  <div class="forgot-page">

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
          <div class="step step--active">
            <span class="step-num">1</span>
            <div class="step-text">
              <strong>Entrez votre email</strong>
              <span>Nous vous envoyons un lien sécurisé</span>
            </div>
          </div>
          <div class="step-connector"></div>
          <div class="step">
            <span class="step-num">2</span>
            <div class="step-text">
              <strong>Vérifiez votre boîte mail</strong>
              <span>Cliquez sur le lien reçu</span>
            </div>
          </div>
          <div class="step-connector"></div>
          <div class="step">
            <span class="step-num">3</span>
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

      <!-- Formulaire -->
      <div v-if="!sent" class="form-card">
        <div class="form-icon">
          <i class="fas fa-lock-open"></i>
        </div>

        <h2>Mot de passe oublié ?</h2>
        <p class="form-subtitle">
          Entrez l'adresse email de votre compte. Nous vous enverrons un lien pour créer un nouveau mot de passe.
        </p>

        <form @submit.prevent="handleReset" autocomplete="off">
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

          <button type="submit" class="submit-btn" :disabled="loading">
            <i :class="loading ? 'fas fa-spinner fa-spin' : 'fas fa-paper-plane'"></i>
            {{ loading ? "Envoi en cours..." : "Envoyer le lien de réinitialisation" }}
          </button>
        </form>

        <p class="mobile-back">
          <a @click="goToLogin"><i class="fas fa-arrow-left"></i> Retour à la connexion</a>
        </p>
      </div>

      <!-- Confirmation envoi -->
      <div v-else class="form-card">
        <div class="success-icon">
          <i class="fas fa-envelope-open-text"></i>
        </div>

        <h2>Email envoyé !</h2>
        <p class="form-subtitle">
          Un lien de réinitialisation a été envoyé à
        </p>
        <p class="email-display">{{ email }}</p>
        <p class="note">
          <i class="fas fa-info-circle"></i>
          Vérifiez également vos spams. Le lien expire dans <strong>30 minutes</strong>.
        </p>

        <button class="submit-btn" @click="goToLogin">
          <i class="fas fa-sign-in-alt"></i> Retour à la connexion
        </button>

        <button class="retry-btn" @click="sent = false; email = ''">
          <i class="fas fa-redo"></i> Utiliser une autre adresse
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from "vue"
import { useRouter } from "vue-router"

const router = useRouter()

const email = ref("")
const loading = ref(false)
const sent = ref(false)

const goToLogin = () => router.push("/login")

const handleReset = async () => {
  loading.value = true
  await new Promise(r => setTimeout(r, 800))
  loading.value = false
  sent.value = true
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
  margin-left: 18px;
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

.step--active .step-text strong {
  color: white;
}

.step-text span {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.45);
}

.step--active .step-text span {
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

/* === ICÔNE === */
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
  margin: 0 0 28px 0;
}

.email-display {
  font-size: 15px;
  font-weight: 700;
  color: #0054a6;
  background: #eef4ff;
  padding: 8px 16px;
  border-radius: 8px;
  margin: 0 0 16px 0;
  word-break: break-all;
}

.note {
  font-size: 13px;
  color: #888;
  background: #f9fafb;
  border-radius: 8px;
  padding: 10px 14px;
  margin-bottom: 24px;
  text-align: left;
  line-height: 1.6;
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.note i {
  color: #0054a6;
  margin-top: 2px;
  flex-shrink: 0;
}

/* === CHAMP === */
.input-group {
  display: flex;
  flex-direction: column;
  margin-bottom: 20px;
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

.field i {
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
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
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
