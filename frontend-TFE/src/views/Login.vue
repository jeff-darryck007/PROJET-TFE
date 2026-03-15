<template>
  <div class="login-container">
    <div class="login-box">

      <!-- Avatar -->
      <div class="avatar">
        <img
          src="https://cdn-icons-png.flaticon.com/512/847/847969.png"
          alt="User avatar"
        />
      </div>

      <!-- Titre -->
      <h1 class="site-title">Partage Gratuit</h1>
      <p class="welcome-msg">
        Donnez, et trouvez des objets gratuits près de chez vous
      </p>

      <!-- Formulaire -->
      <form @submit.prevent="handleLogin">

        <!-- Email -->
        <div class="input-group" :class="{ error: errorMessage }">
          <i class="fas fa-user"></i>
          <input
            type="email"
            v-model="email"
            placeholder="Email"
            required
          />
        </div>

        <!-- Password -->
        <div class="input-group password-group" :class="{ error: errorMessage }">
          <i class="fas fa-lock"></i>

          <input
            :type="showPassword ? 'text' : 'password'"
            v-model="password"
            placeholder="Mot de passe"
            required
          />

          <span class="toggle-password" @click="togglePassword">
            <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
          </span>
        </div>

        <!-- Message erreur -->
        <div v-if="errorMessage" class="error-message">
          {{ errorMessage }}
        </div>

        <!-- Options -->
        <div class="options">
          <label class="remember">
            <input type="checkbox" v-model="rememberMe" />
            Se souvenir de moi
          </label>

          <a class="forgot" @click="goForgot">
            Mot de passe oublié ?
          </a>
        </div>

        <!-- Bouton -->
        <button
          type="submit"
          class="login-btn"
          :disabled="loading"
        >
          {{ loading ? "Connexion..." : "Se connecter" }}
        </button>

        <!-- Inscription -->
        <p class="register-msg">
          Pas encore inscrit ?
          <a @click="goToRegister">Créer un compte</a>
        </p>

      </form>
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

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const goToRegister = () => router.push("/register")
const goForgot = () => router.push("/forgot-password")

// Supprime le message d'erreur quand on modifie les champs
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
  } catch (error) {
    errorMessage.value =
      "Adresse email ou mot de passe incorrect."
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>

/* ===== Background ===== */
.login-container {
  min-height: 100vh;
  background: #e9e9e9;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
}

/* ===== Carte ===== */
.login-box {
  background: #f3f3f3;
  border-radius: 30px;
  width: 100%;
  max-width: 650px;
  padding: 60px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  text-align: center;
}

/* ===== Avatar ===== */
.avatar img {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  border: 2px solid #222;
  margin-bottom: 20px;
}

/* ===== Titre ===== */
.site-title {
  font-size: 42px;
  color: #0a4a8a;
  font-family: Georgia, serif;
  margin-bottom: 10px;
}

.welcome-msg {
  font-size: 16px;
  margin-bottom: 40px;
  color: #333;
}

/* ===== Champs ===== */
.input-group {
  position: relative;
  border: 2px solid #222;
  border-radius: 15px;
  padding: 18px;
  margin-bottom: 20px;
  background: #fff;
  display: flex;
  align-items: center;
  transition: 0.3s;
}

.input-group i {
  color: #f1b800;
  margin-right: 12px;
  font-size: 18px;
}

.input-group input {
  border: none;
  outline: none;
  flex: 1;
  font-size: 16px;
  background: transparent;
}

/* Erreur champ */
.input-group.error {
  border-color: #c40000;
  background: #fff5f5;
}

/* ===== Password ===== */
.password-group {
  position: relative;
}

.toggle-password {
  position: absolute;
  right: 18px;
  cursor: pointer;
  color: #f1b800;
}

/* ===== Message erreur ===== */
.error-message {
  background: #ffe5e5;
  color: #c40000;
  border: 1px solid #ffb3b3;
  padding: 12px;
  border-radius: 10px;
  margin-bottom: 20px;
  font-size: 14px;
  text-align: center;
}

/* ===== Options ===== */
.options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  font-size: 14px;
}

.remember {
  display: flex;
  align-items: center;
  gap: 8px;
}

.forgot {
  color: #0a4a8a;
  font-weight: 600;
  cursor: pointer;
}

.forgot:hover {
  text-decoration: underline;
}

/* ===== Bouton ===== */
.login-btn {
  width: 100%;
  padding: 18px;
  background: #f1b800;
  border: none;
  border-radius: 15px;
  font-size: 18px;
  font-weight: bold;
  cursor: pointer;
  transition: 0.3s ease;
  box-shadow: 0 4px 0 #c89b00;
}

.login-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(241, 184, 0, 0.4);
}

.login-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* ===== Register ===== */
.register-msg {
  margin-top: 30px;
  font-size: 15px;
}

.register-msg a {
  color: #f1b800;
  font-weight: bold;
  cursor: pointer;
}

.register-msg a:hover {
  text-decoration: underline;
}

/* ===== Responsive ===== */
@media (max-width: 600px) {
  .login-box {
    padding: 35px;
  }

  .site-title {
    font-size: 30px;
  }
}

</style>