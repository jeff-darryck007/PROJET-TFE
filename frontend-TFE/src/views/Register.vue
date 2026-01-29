<template>
  <div class="register-container">
    <div class="register-box">
      <div class="avatar animate-pop">
        <img v-if="previewImage" :src="previewImage" alt="Photo de profil" />
        <img v-else src="https://cdn-icons-png.flaticon.com/512/847/847969.png" alt="Avatar par défaut" />
      </div>

      <h2 class="site-title fade-in">Créer un compte</h2>
      <p class="welcome-msg fade-in-delay">
        Rejoignez la communauté de <strong>Partage Gratuit</strong> 
      </p>

      <form @submit.prevent="handleRegister" class="fade-in-delay2">
        <div class="input-group">
          <i class="fas fa-user"></i>
          <input type="text" v-model="name" placeholder="Nom" required />
        </div>

        <div class="input-group">
          <i class="fas fa-user-tag"></i>
          <input type="text" v-model="surname" placeholder="Prénom" required />
        </div>

        <div class="input-group">
          <i class="fas fa-envelope"></i>
          <input type="email" v-model="email" placeholder="Email" required />
        </div>

        <div class="input-group">
          <i class="fas fa-user-cog"></i>
          <select v-model="role" required>
            <option value="" disabled>Choisir un rôle</option>
            <option value="donateur">Donateur</option>
            <option value="visiteur">Visiteur</option>
          </select>
        </div>

        <div class="input-group password-group">
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

        <div class="input-group password-group">
          <i class="fas fa-lock"></i>

          <input
            :type="showConfirm ? 'text' : 'password'"
            v-model="confirmerPassword"
            placeholder="Confirmer le mot de passe"
            required
          />

          <span class="toggle-password" @click="showConfirm = !showConfirm">
            <i :class="showConfirm ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
          </span>
        </div>

        <button type="submit" class="register-btn" :disabled="loading">
          <span v-if="!loading">S'inscrire</span>
          <span v-else class="loader"></span>
        </button>

        <p v-if="errorMessage" class="error-msg">{{ errorMessage }}</p>
        <p v-if="successMessage" class="success-msg">{{ successMessage }}</p>

        <p class="login-msg">
          Déjà un compte ? <a href="#" @click="goToLogin">Se connecter</a>
        </p>
      </form>
    </div>
  </div>
  <Footer />
</template>

<script setup>
import { ref } from "vue"
import { useRouter } from "vue-router"
import Navbar from "./Navbar.vue"
import Footer from "./Footer.vue"
import { registerUser } from "../controller/controllerLogin.js"

const router = useRouter()

// Champs du formulaire
const name = ref("")
const surname = ref("")
const email = ref("")
const role = ref("")
const password = ref("")
const confirmerPassword = ref("")

// UI
const loading = ref(false)
const errorMessage = ref("")
const successMessage = ref("")
const showPassword = ref(false)
const showConfirm = ref(false)
const previewImage = ref(null)

// Navigation
const goToLogin = () => router.push("/login")

// Soumission
const handleRegister = async () => {
  errorMessage.value = ""
  successMessage.value = ""

  if (password.value !== confirmerPassword.value) {
    errorMessage.value = "Les mots de passe ne correspondent pas"
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
      router.push("/")
    }, 1500)

  } catch (error) {
    errorMessage.value = error.message
  } finally {
    loading.value = false
  }
}
</script>


<style scoped>
  .password-group {
  position: relative;
}

.toggle-password {
  cursor: pointer;
  font-size: 18px;
  color: #444;
  margin-left: 10px;
  transition: 0.2s;
}

.toggle-password:hover {
  color: #f1b800;
}

/* --- Fond principal --- */
.register-container {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background: #ffffff;
  padding: 20px;
  box-sizing: border-box;
}

/* --- Bloc d'inscription --- */
.register-box {
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(12px);
  border-radius: 20px;
  padding: 60px;
  width: 100%;
  max-width: 650px; /* plus large que la version initiale */
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
  text-align: center;
  color: #000;
  transition: all 0.3s ease;
}

/* --- Avatar --- */
.avatar img {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  margin-bottom: 30px;
  border: 2px solid #000;
  object-fit: cover;
  transition: all 0.3s ease;
}

/* --- Titre --- */
.site-title {
  font-size: 2.8em;
  margin-bottom: 20px;
  font-weight: 700;
}
.welcome-msg {
  font-size: 18px;
  margin-bottom: 35px;
}

/* --- Champs de saisie --- */
.input-group {
  border: 1px solid #000;
  display: flex;
  align-items: center;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 10px;
  margin: 18px 0;
  padding: 16px;
  transition: all 0.3s ease;
}

.input-group i {
  margin-right: 12px;
  color: #F1B800;
  font-size: 18px;
}

.input-group input,
.input-group select {
  flex: 1;
  border: none;
  background: transparent;
  outline: none;
  color: #000;
  font-size: 18px;
}

/* --- Bouton d'inscription --- */
.register-btn {
  width: 100%;
  padding: 16px;
  border: none;
  border-radius: 12px;
  background: #F1B800;
  color: #000;
  cursor: pointer;
  font-weight: bold;
  font-size: 18px;
  transition: 0.2s ease;
}
.register-btn:hover {
  background: #e0a700;
}

/* --- Lien connexion --- */
.login-msg {
  margin-top: 30px;
  font-size: 16px;
}
.login-msg a {
  color: #F1B800;
  font-weight: 700;
}

/* --- Animations --- */
.fade-in {
  animation: fadeIn 1s ease forwards;
}
.fade-in-delay {
  animation: fadeIn 1.3s ease forwards;
}
.fade-in-delay2 {
  animation: fadeIn 1.6s ease forwards;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes pop {
  0% { transform: scale(0.5); opacity: 0; }
  100% { transform: scale(1); opacity: 1; }
}
.animate-pop { animation: pop 0.8s ease; }

/* --- RESPONSIVE --- */
@media (max-width: 1024px) {
  .register-box { padding: 50px; }
  .avatar img { width: 100px; height: 100px; }
  .site-title { font-size: 2.4em; }
  .welcome-msg { font-size: 16px; }
  .input-group input, .input-group select { font-size: 16px; }
  .register-btn { font-size: 16px; padding: 14px; }
}

@media (max-width: 768px) {
  .register-box { padding: 40px; }
  .avatar img { width: 90px; height: 90px; }
  .site-title { font-size: 2em; }
  .welcome-msg { font-size: 15px; }
  .input-group input, .input-group select { font-size: 15px; }
  .register-btn { font-size: 15px; padding: 12px; }
}

@media (max-width: 480px) {
  .register-box { padding: 30px; width: 90%; }
  .avatar img { width: 80px; height: 80px; }
  .site-title { font-size: 1.8em; }
  .welcome-msg { font-size: 14px; }
  .input-group input, .input-group select { font-size: 14px; }
  .register-btn { font-size: 14px; padding: 10px; }
}
</style>

