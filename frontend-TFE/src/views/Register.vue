<template>
  <div class="register-container">
    <div class="register-box">

      <div class="avatar">
        <img src="https://cdn-icons-png.flaticon.com/512/847/847969.png" />
      </div>

      <h2 class="site-title">Créer un compte</h2>
      <p class="welcome-msg">
        Rejoignez la communauté de <strong>Partage Gratuit</strong>
      </p>

      <form @submit.prevent="handleRegister">

        <!-- Nom -->
        <div class="input-group">
          <i class="fas fa-user"></i>
          <input type="text" v-model="name" placeholder="Nom" required />
        </div>

        <!-- Prénom -->
        <div class="input-group">
          <i class="fas fa-user-tag"></i>
          <input type="text" v-model="surname" placeholder="Prénom" required />
        </div>

        <!-- Email -->
        <div class="input-group" :class="{ error: emailError }">
          <i class="fas fa-envelope"></i>
          <input type="email" v-model="email" placeholder="Email" required />
        </div>
        <p v-if="emailError" class="error-msg">{{ emailError }}</p>

        <!-- Rôle -->
        <div class="input-group">
          <i class="fas fa-user-cog"></i>
          <select v-model="role" required>
            <option value="" disabled>Choisir un rôle</option>
            <option value="donateur">Donateur</option>
            <option value="visiteur">Visiteur</option>
          </select>
        </div>

        <!-- Mot de passe -->
        <div class="input-group" :class="{ error: passwordError }">
          <i class="fas fa-lock"></i>
          <input
            :type="showPassword ? 'text' : 'password'"
            v-model="password"
            placeholder="Mot de passe"
            required
          />
          <span class="toggle-password" @click="showPassword = !showPassword">
            <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
          </span>
        </div>
        <p v-if="passwordError" class="error-msg">{{ passwordError }}</p>

        <!-- Confirmation -->
        <div class="input-group" :class="{ error: confirmError }">
          <i class="fas fa-lock"></i>
          <input
            :type="showConfirm ? 'text' : 'password'"
            v-model="confirmerPassword"
            placeholder="Confirmer le mot de passe"
            required
          />
        </div>
        <p v-if="confirmError" class="error-msg">{{ confirmError }}</p>

        <!-- Bouton -->
        <button type="submit" class="register-btn" :disabled="loading">
          {{ loading ? "Création..." : "S'inscrire" }}
        </button>

        <p v-if="successMessage" class="success-msg">
          {{ successMessage }}
        </p>

        <p class="login-msg">
          Déjà un compte ?
          <a @click="goToLogin">Se connecter</a>
        </p>

      </form>
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

const emailError = ref("")
const passwordError = ref("")
const confirmError = ref("")
const successMessage = ref("")

const showPassword = ref(false)
const showConfirm = ref(false)

const goToLogin = () => router.push("/login")

// Nettoyage auto erreurs
watch([email, password, confirmerPassword], () => {
  emailError.value = ""
  passwordError.value = ""
  confirmError.value = ""
})

// Validation mot de passe
const isPasswordValid = (pwd) => {
  const regex = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/
  return regex.test(pwd)
}

const handleRegister = async () => {

  emailError.value = ""
  passwordError.value = ""
  confirmError.value = ""
  successMessage.value = ""

  // Vérification mot de passe
  if (!isPasswordValid(password.value)) {
    passwordError.value =
      "Le mot de passe doit contenir au moins 8 caractères avec lettres et chiffres."
    return
  }

  // Vérification confirmation
  if (password.value !== confirmerPassword.value) {
    confirmError.value = "Les mots de passe ne correspondent pas."
    return
  }

  loading.value = true

  try {
    await registerUser(
      email.value,
      password.value,
      name.value,
      surname.value,
      role.value
    )

    successMessage.value = "Compte créé avec succès 🎉"

    setTimeout(() => {
      router.push("/login")
    }, 1500)

  } catch (error) {

    // Si ton backend renvoie 409 ou message email existant
    if (error.response?.status === 409) {
      emailError.value = "Cette adresse email existe déjà."
    } else {
      emailError.value = error.message
    }

  } finally {
    loading.value = false
  }
}
</script>

<style scoped>

/* Layout */
.register-container {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background: #f4f4f4;
}

.register-box {
  background: white;
  padding: 50px;
  border-radius: 20px;
  width: 100%;
  max-width: 600px;
  box-shadow: 0 15px 40px rgba(0,0,0,0.15);
  text-align: center;
}

/* Avatar */
.avatar img {
  width: 100px;
  margin-bottom: 20px;
}

/* Champs */
.input-group {
  border: 2px solid #ccc;
  border-radius: 12px;
  padding: 15px;
  margin: 15px 0;
  display: flex;
  align-items: center;
  background: #fff;
}

.input-group.error {
  border-color: red;
  background: #fff5f5;
}

.input-group input,
.input-group select {
  flex: 1;
  border: none;
  outline: none;
  font-size: 16px;
  background: transparent;
}

.input-group i {
  margin-right: 10px;
  color: #F1B800;
}

/* Erreurs */
.error-msg {
  color: red;
  font-size: 14px;
  text-align: left;
  margin-top: -8px;
}

/* Succès */
.success-msg {
  color: green;
  margin-top: 15px;
}

/* Bouton */
.register-btn {
  width: 100%;
  padding: 15px;
  background: #F1B800;
  border: none;
  border-radius: 12px;
  font-weight: bold;
  cursor: pointer;
  margin-top: 15px;
}

.register-btn:hover {
  background: #dca600;
}

.login-msg {
  margin-top: 20px;
}

.toggle-password {
  cursor: pointer;
}

</style>