<template>
  <div class="register-page">

    <!-- PANNEAU GAUCHE : branding -->
    <div class="left-panel">
      <div class="brand">
        <div class="brand-icon">
          <i class="fas fa-hands-helping"></i>
        </div>
        <h1>Partage Gratuit</h1>
        <p class="brand-tagline">
          Rejoignez notre communauté et participez à l'économie du partage local
        </p>
      </div>

      <ul class="benefits">
        <li>
          <span class="benefit-icon"><i class="fas fa-gift"></i></span>
          <div>
            <strong>Donnez facilement</strong>
            <span>Publiez vos objets en quelques clics</span>
          </div>
        </li>
        <li>
          <span class="benefit-icon"><i class="fas fa-search-location"></i></span>
          <div>
            <strong>Trouvez gratuitement</strong>
            <span>Des milliers d'annonces près de chez vous</span>
          </div>
        </li>
        <li>
          <span class="benefit-icon"><i class="fas fa-shield-alt"></i></span>
          <div>
            <strong>100 % gratuit et sécurisé</strong>
            <span>Aucune transaction, échange direct</span>
          </div>
        </li>
      </ul>

      <p class="login-hint">
        Déjà un compte ?
        <a @click="goToLogin">Se connecter</a>
      </p>
    </div>

    <!-- PANNEAU DROIT : formulaire -->
    <div class="right-panel">
      <div class="form-card">
        <h2>Créer un compte</h2>
        <p class="form-subtitle">Remplissez les informations ci-dessous</p>

        <form @submit.prevent="handleRegister" autocomplete="off" novalidate>

          <!-- Nom + Prénom -->
          <div class="row-2">
            <div class="input-group">
              <label>Nom *</label>
              <div class="field" :class="{ error: nameError }">
                <i class="fas fa-user"></i>
                <input type="text" v-model="name" placeholder="Dupont" autocomplete="off" maxlength="100" />
              </div>
              <p v-if="nameError" class="msg-error">{{ nameError }}</p>
            </div>
            <div class="input-group">
              <label>Prénom *</label>
              <div class="field" :class="{ error: surnameError }">
                <i class="fas fa-user"></i>
                <input type="text" v-model="surname" placeholder="Jean" autocomplete="off" maxlength="100" />
              </div>
              <p v-if="surnameError" class="msg-error">{{ surnameError }}</p>
            </div>
          </div>

          <!-- Email -->
          <div class="input-group">
            <label>Adresse email *</label>
            <div class="field" :class="{ error: emailError }">
              <i class="fas fa-envelope"></i>
              <input
                type="email"
                v-model="email"
                placeholder="jean.dupont@email.com"
                autocomplete="off"
                required
              />
            </div>
            <p v-if="emailError" class="msg-error">{{ emailError }}</p>
          </div>

          <!-- Rôle -->
          <div class="input-group">
            <label>Vous souhaitez... *</label>
            <div class="role-selector">
              <div
                class="role-card"
                :class="{ selected: role === 'donateur', 'role-error': roleError }"
                @click="role = 'donateur'"
              >
                <i class="fas fa-hand-holding-heart"></i>
                <strong>Donateur</strong>
                <span>Donner des objets</span>
              </div>
              <div
                class="role-card"
                :class="{ selected: role === 'visiteur', 'role-error': roleError }"
                @click="role = 'visiteur'"
              >
                <i class="fas fa-box-open"></i>
                <strong>Visiteur</strong>
                <span>Trouver des objets</span>
              </div>
            </div>
            <p v-if="roleError" class="msg-error">{{ roleError }}</p>
          </div>

          <!-- Mot de passe -->
          <div class="input-group">
            <label>Mot de passe *</label>
            <div class="field" :class="{ error: passwordError }">
              <i class="fas fa-lock"></i>
              <input
                :type="showPassword ? 'text' : 'password'"
                v-model="password"
                placeholder="Min. 8 caractères, lettres + chiffres"
                autocomplete="new-password"
                required
              />
              <span class="eye" @click="showPassword = !showPassword">
                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
              </span>
            </div>
            <p v-if="passwordError" class="msg-error">{{ passwordError }}</p>
          </div>

          <!-- Confirmation -->
          <div class="input-group">
            <label>Confirmer le mot de passe *</label>
            <div class="field" :class="{ error: confirmError }">
              <i class="fas fa-lock"></i>
              <input
                :type="showConfirm ? 'text' : 'password'"
                v-model="confirmerPassword"
                placeholder="Répétez votre mot de passe"
                autocomplete="new-password"
                required
              />
              <span class="eye" @click="showConfirm = !showConfirm">
                <i :class="showConfirm ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
              </span>
            </div>
            <p v-if="confirmError" class="msg-error">{{ confirmError }}</p>
          </div>

          <!-- Erreur générale -->
          <div v-if="generalError" class="msg-error-box">
            <i class="fas fa-exclamation-circle"></i> {{ generalError }}
          </div>

          <!-- Succès -->
          <div v-if="successMessage" class="msg-success">
            <i class="fas fa-check-circle"></i> {{ successMessage }}
          </div>

          <!-- Bouton -->
          <button type="submit" class="register-btn" :disabled="loading">
            <i :class="loading ? 'fas fa-spinner fa-spin' : 'fas fa-user-plus'"></i>
            {{ loading ? "Création en cours..." : "Créer mon compte" }}
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

          <p class="mobile-login">
            Déjà un compte ? <a @click="goToLogin">Se connecter</a>
          </p>

        </form>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, watch } from "vue"
import { useRouter } from "vue-router"
import { registerUser } from "../controller/controllerLogin.js"

const router = useRouter()

const name = ref("")
const surname = ref("")
const email = ref("")
const role = ref("")
const password = ref("")
const confirmerPassword = ref("")

const loading = ref(false)
const showPassword = ref(false)
const showConfirm = ref(false)

const emailError = ref("")
const passwordError = ref("")
const confirmError = ref("")
const successMessage = ref("")

const goToLogin = () => router.push("/login")
const goToSite = () => router.push("/")

const nameError    = ref("")
const surnameError = ref("")
const roleError    = ref("")
const generalError = ref("")

watch([name], () => { nameError.value = "" })
watch([surname], () => { surnameError.value = "" })
watch([email], () => { emailError.value = "" })
watch([password], () => { passwordError.value = "" })
watch([confirmerPassword], () => { confirmError.value = "" })
watch([role], () => { roleError.value = "" })

const isEmailValid = (val) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)

const isPasswordValid = (pwd) =>
  pwd.length >= 8 && /[A-Za-z]/.test(pwd) && /[0-9]/.test(pwd)

const handleRegister = async () => {
  nameError.value = ""
  surnameError.value = ""
  emailError.value = ""
  passwordError.value = ""
  confirmError.value = ""
  roleError.value = ""
  generalError.value = ""
  successMessage.value = ""

  let valid = true

  if (!name.value.trim() || name.value.trim().length < 2) {
    nameError.value = "Le nom doit contenir au moins 2 caractères."
    valid = false
  }

  if (!surname.value.trim() || surname.value.trim().length < 2) {
    surnameError.value = "Le prénom doit contenir au moins 2 caractères."
    valid = false
  }

  if (!isEmailValid(email.value)) {
    emailError.value = "Adresse email invalide."
    valid = false
  }

  if (!role.value) {
    roleError.value = "Veuillez sélectionner un rôle."
    valid = false
  }

  if (!isPasswordValid(password.value)) {
    passwordError.value = "Le mot de passe doit contenir au moins 8 caractères avec lettres et chiffres."
    valid = false
  }

  if (password.value !== confirmerPassword.value) {
    confirmError.value = "Les mots de passe ne correspondent pas."
    valid = false
  }

  if (!valid) return

  loading.value = true

  try {
    await registerUser(email.value, password.value, name.value.trim(), surname.value.trim(), role.value)
    successMessage.value = "Compte créé avec succès !"
    setTimeout(() => router.push("/login"), 1500)
  } catch (error) {
    const msg = error.message || ""
    if (msg.toLowerCase().includes("email")) {
      emailError.value = msg
    } else if (msg.toLowerCase().includes("mot de passe") || msg.toLowerCase().includes("password")) {
      passwordError.value = msg
    } else if (msg.toLowerCase().includes("nom") || msg.toLowerCase().includes("prénom")) {
      nameError.value = msg
    } else {
      generalError.value = msg
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* === PAGE === */
.register-page {
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

.benefits {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.benefits li {
  display: flex;
  align-items: flex-start;
  gap: 14px;
}

.benefit-icon {
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.15);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.benefit-icon i {
  font-size: 17px;
  color: #f1b800;
}

.benefits li div {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.benefits li strong {
  font-size: 14px;
  font-weight: 700;
  color: white;
}

.benefits li span {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.7);
}

.login-hint {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
  margin: 0;
}

.login-hint a {
  color: #f1b800;
  font-weight: 700;
  cursor: pointer;
  text-decoration: none;
}

.login-hint a:hover {
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
  overflow-y: auto;
}

.form-card {
  background: white;
  border-radius: 16px;
  padding: 40px;
  width: 100%;
  max-width: 480px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
}

.form-card h2 {
  font-size: 24px;
  font-weight: 800;
  color: #222;
  margin: 0 0 6px 0;
}

.form-subtitle {
  font-size: 14px;
  color: #888;
  margin: 0 0 28px 0;
}

/* === RANGÉE 2 COLONNES === */
.row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

/* === GROUPES CHAMPS === */
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

.field {
  position: relative;
  display: flex;
  align-items: center;
  border: 2px solid #e8ecf0;
  border-radius: 10px;
  background: white;
  transition: border-color 0.2s;
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
  padding: 0 12px;
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
  padding: 11px 12px 11px 0;
  font-family: inherit;
}

.eye {
  padding: 0 12px;
  cursor: pointer;
  color: #aaa;
  transition: color 0.2s;
  font-size: 14px;
}

.eye:hover {
  color: #f1b800;
}

/* === SÉLECTEUR DE RÔLE === */
.role-selector {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.role-card {
  border: 2px solid #e8ecf0;
  border-radius: 10px;
  padding: 16px 12px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  user-select: none;
}

.role-card i {
  font-size: 26px;
  color: #ccc;
  margin-bottom: 6px;
  transition: color 0.2s;
}

.role-card strong {
  font-size: 14px;
  font-weight: 700;
  color: #444;
}

.role-card span {
  font-size: 12px;
  color: #999;
}

.role-card:hover {
  border-color: #0054a6;
}

.role-card:hover i {
  color: #0054a6;
}

.role-card.selected {
  border-color: #0054a6;
  background: #eef4ff;
}

.role-card.selected i {
  color: #0054a6;
}

.role-card.selected strong {
  color: #0054a6;
}

/* === MESSAGES === */
.msg-error {
  color: #dc2626;
  font-size: 12px;
  margin: 5px 0 0 0;
}

.msg-error-box {
  background: #fff5f5;
  color: #dc2626;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 13px;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.role-error {
  border-color: #dc2626 !important;
}

.msg-success {
  background: #f0fdf4;
  color: #16a34a;
  border: 1px solid #bbf7d0;
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 14px;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* === BOUTON === */
.register-btn {
  width: 100%;
  padding: 14px;
  background: #f1b800;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s, transform 0.1s;
  font-family: inherit;
  color: #222;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  box-shadow: 0 4px 0 #c89b00;
  margin-top: 4px;
}

.register-btn:hover {
  background: #dca600;
  transform: translateY(-1px);
}

.register-btn:disabled {
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

/* Lien mobile (caché sur grand écran, visible en mobile) */
.mobile-login {
  display: none;
  text-align: center;
  margin-top: 20px;
  font-size: 14px;
  color: #666;
}

.mobile-login a {
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

  .mobile-login {
    display: block;
  }
}

@media (max-width: 480px) {
  .row-2 {
    grid-template-columns: 1fr;
  }
}
</style>
