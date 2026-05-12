<template>
  <div class="login-page">

    <!-- PANNEAU GAUCHE : branding -->
    <div class="left-panel">
      <div class="brand">
        <div class="brand-icon">
          <i class="fas fa-hands-helping"></i>
        </div>
        <h1>Partage Gratuit</h1>
        <p class="brand-tagline">
          Donnez une seconde vie à vos objets et trouvez des articles gratuits près de chez vous
        </p>
      </div>

      <ul class="features">
        <li>
          <span class="feat-icon"><i class="fas fa-map-marker-alt"></i></span>
          <div>
            <strong>Annonces locales</strong>
            <span>Trouvez des objets dans votre quartier</span>
          </div>
        </li>
        <li>
          <span class="feat-icon"><i class="fas fa-heart"></i></span>
          <div>
            <strong>100 % gratuit</strong>
            <span>Aucun frais, aucune commission</span>
          </div>
        </li>
        <li>
          <span class="feat-icon"><i class="fas fa-comments"></i></span>
          <div>
            <strong>Messagerie intégrée</strong>
            <span>Discutez directement avec les donneurs</span>
          </div>
        </li>
      </ul>

      <p class="register-hint">
        Pas encore inscrit ?
        <a @click="goToRegister">Créer un compte</a>
      </p>
    </div>

    <!-- PANNEAU DROIT : formulaire -->
    <div class="right-panel">
      <div class="form-card">

        <div class="form-header">
          <h2>Bienvenue !</h2>
          <p class="form-subtitle">Connectez-vous à votre compte</p>
        </div>

        <form @submit.prevent="handleLogin" autocomplete="off">

          <!-- Email -->
          <div class="input-group">
            <label for="login-email">Adresse email</label>
            <div class="field" :class="{ error: errorMessage }">
              <i class="fas fa-envelope"></i>
              <input
                id="login-email"
                type="email"
                v-model="email"
                placeholder="jean.dupont@email.com"
                autocomplete="off"
                required
              />
            </div>
          </div>

          <!-- Mot de passe -->
          <div class="input-group">
            <div class="label-row">
              <label for="login-password">Mot de passe</label>
              <a class="forgot" @click="goForgot">Mot de passe oublié ?</a>
            </div>
            <div class="field" :class="{ error: errorMessage }">
              <i class="fas fa-lock"></i>
              <input
                id="login-password"
                :type="showPassword ? 'text' : 'password'"
                v-model="password"
                placeholder="••••••••"
                autocomplete="new-password"
                required
              />
              <span class="eye" @click="showPassword = !showPassword">
                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
              </span>
            </div>
          </div>

          <!-- Erreur -->
          <div v-if="errorMessage" class="msg-error">
            <i class="fas fa-exclamation-circle"></i> {{ errorMessage }}
          </div>

          <!-- Se souvenir -->
          <label class="remember">
            <input type="checkbox" v-model="rememberMe" />
            <span>Se souvenir de moi</span>
          </label>

          <!-- Bouton connexion -->
          <button type="submit" class="login-btn" :disabled="loading">
            <i :class="loading ? 'fas fa-spinner fa-spin' : 'fas fa-sign-in-alt'"></i>
            {{ loading ? "Connexion..." : "Se connecter" }}
          </button>

          <!-- Séparateur -->
          <div class="divider">
            <span>ou</span>
          </div>

          <!-- Visiter sans compte -->
          <button type="button" class="visit-btn" @click="goToSite">
            <i class="fas fa-globe"></i>
            Visiter le site sans compte
          </button>

          <p class="mobile-register">
            Pas encore inscrit ? <a @click="goToRegister">Créer un compte</a>
          </p>

        </form>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, watch } from "vue"
import { useRouter } from "vue-router"
import { loginUser } from "../controller/controllerLogin.js"

const router = useRouter()

const email = ref("")
const password = ref("")
const rememberMe = ref(false)
const showPassword = ref(false)
const loading = ref(false)
const errorMessage = ref("")

const goToRegister = () => router.push("/register")
const goForgot = () => router.push("/forgot-password")
const goToSite = () => router.push("/")

watch([email, password], () => {
  errorMessage.value = ""
})

const handleLogin = async () => {
  try {
    loading.value = true
    errorMessage.value = ""

    const data = await loginUser(email.value, password.value)

    localStorage.setItem("token", data.token)
    localStorage.setItem("idUser", data.id)
    localStorage.setItem("roles", data.roles)

    router.push("/")
  } catch (err) {
    if (err.message === "Identifiants invalides") {
      errorMessage.value = "Adresse email ou mot de passe incorrect."
    } else {
      errorMessage.value = "Impossible de joindre le serveur. Vérifiez que le backend est démarré."
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* === PAGE === */
.login-page {
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
  justify-content: center;
  gap: 40px;
}

.brand-icon {
  width: 72px;
  height: 72px;
  background: rgba(255, 255, 255, 0.15);
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
}

.brand-icon i {
  font-size: 34px;
  color: #f1b800;
}

.brand h1 {
  font-size: 32px;
  font-weight: 800;
  margin: 0 0 12px 0;
  letter-spacing: -0.5px;
}

.brand-tagline {
  font-size: 15px;
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.6;
  margin: 0;
}

.features {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.features li {
  display: flex;
  align-items: flex-start;
  gap: 14px;
}

.feat-icon {
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.15);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.feat-icon i {
  font-size: 17px;
  color: #f1b800;
}

.features li div {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.features li strong {
  font-size: 14px;
  font-weight: 700;
}

.features li span {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.7);
}

.register-hint {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
  margin: 0;
}

.register-hint a {
  color: #f1b800;
  font-weight: 700;
  cursor: pointer;
  text-decoration: none;
}

.register-hint a:hover {
  text-decoration: underline;
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
}

.form-header {
  margin-bottom: 32px;
}

.form-card h2 {
  font-size: 26px;
  font-weight: 800;
  color: #222;
  margin: 0 0 6px 0;
}

.form-subtitle {
  font-size: 14px;
  color: #888;
  margin: 0;
}

/* === CHAMPS === */
.input-group {
  display: flex;
  flex-direction: column;
  margin-bottom: 18px;
}

.input-group label {
  font-size: 13px;
  font-weight: 700;
  color: #444;
  margin-bottom: 7px;
}

.label-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 7px;
}

.label-row label {
  font-size: 13px;
  font-weight: 700;
  color: #444;
  margin: 0;
}

.forgot {
  font-size: 12px;
  font-weight: 600;
  color: #0054a6;
  cursor: pointer;
  text-decoration: none;
}

.forgot:hover {
  text-decoration: underline;
}

.field {
  position: relative;
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
}

/* === SE SOUVENIR === */
.remember {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #555;
  cursor: pointer;
  margin-bottom: 24px;
  user-select: none;
}

.remember input[type="checkbox"] {
  width: 16px;
  height: 16px;
  accent-color: #0054a6;
  cursor: pointer;
}

/* === BOUTON === */
.login-btn {
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

.login-btn:hover {
  background: #dca600;
  transform: translateY(-1px);
}

.login-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

/* === SÉPARATEUR === */
.divider {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 16px 0;
  color: #ccc;
  font-size: 13px;
}

.divider::before,
.divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #e8ecf0;
}

/* === BOUTON VISITER === */
.visit-btn {
  width: 100%;
  padding: 13px;
  background: transparent;
  border: 2px solid #e8ecf0;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: border-color 0.2s, color 0.2s, background 0.2s;
  font-family: inherit;
  color: #555;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-bottom: 4px;
}

.visit-btn:hover {
  border-color: #0054a6;
  color: #0054a6;
  background: #f0f6ff;
}

/* === LIEN MOBILE === */
.mobile-register {
  display: none;
  text-align: center;
  margin-top: 20px;
  font-size: 14px;
  color: #666;
}

.mobile-register a {
  color: #f1b800;
  font-weight: 700;
  cursor: pointer;
}

/* === RESPONSIVE === */
@media (max-width: 900px) {
  .left-panel {
    display: none;
  }

  .right-panel {
    width: 100%;
    padding: 24px 16px;
  }

  .form-card {
    padding: 30px 24px;
    box-shadow: none;
    background: transparent;
  }

  .mobile-register {
    display: block;
  }
}
</style>
