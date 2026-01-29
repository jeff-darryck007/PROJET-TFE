<template>
  <div class="login-container">
    <div class="login-box">

      <div class="avatar animate-pop">
        <img
          src="https://cdn-icons-png.flaticon.com/512/847/847969.png"
          alt="User avatar"
        />
      </div>

      <h2 class="site-title fade-in">Partage Gratuit</h2>
      <p class="welcome-msg fade-in-delay">
        Donnez, et trouvez des objets gratuits près de chez vous 
      </p>

      <form @submit.prevent="handleLogin" class="fade-in-delay2">
        <div class="input-group">
          <i class="fas fa-user"></i>
          <input type="email" v-model="email" placeholder="Email" required />
        </div>

        <div class="input-group password-group">
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

        <div class="options">
          <label class="remember">
            <input type="checkbox" v-model="rememberMe" />
            Se souvenir de moi
          </label>
          <a class="forgot" @click="goForgot">Mot de passe oublié ?</a>
        </div>

        <button type="submit" class="login-btn">
          Se connecter
        </button>

        <p class="register-msg">
          Pas encore inscrit ?
          <a @click="goToRegister">Créer un compte</a>
        </p>
      </form>
    </div>
  </div>

  <Footer />
</template>

<script setup>
import { ref } from "vue"
import { useRouter } from "vue-router"
import { loginUser } from "../controller/controllerLogin.js"
import Footer from "./Footer.vue"

const showPassword = ref(false)

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const router = useRouter()

const email = ref("")
const password = ref("")
const rememberMe = ref(false)

const goToRegister = () => router.push("/register")
const goForgot = () => router.push("/forgot-password")

const handleLogin = async () => {
  try {
    const data = await loginUser(email.value, password.value)
    // stocker le token dans le localStorage et rediriger vers le dashboard
    localStorage.setItem("token", data.token)
    localStorage.setItem("idUser", data.id)
    localStorage.setItem("roles", data.roles)
    router.push("/") 
  } catch (error) {
    console.error(error.message)
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


/* Fix overflow horizontal */
:global(html, body) {
  overflow-x: hidden !important;
  width: 100%;
}

/* --- Fond principal --- */
.login-container {
  min-height: calc(100vh - 120px);
  display: flex;
  justify-content: center;
  align-items: center;
  background: #ffffff;
  padding: 20px;
  box-sizing: border-box;
}

/* --- Bloc login (AGRANDI) --- */
.login-box {
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(12px);
  border-radius: 25px;

  width: 100%;
  max-width: 650px;     /* Largeur augmentée */
  padding: 50px;        /* Padding plus grand */

  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
  text-align: center;
  color: #000;
  box-sizing: border-box;
}

/* --- Avatar (agrandi) --- */
.avatar img {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  margin-bottom: 30px;
  border: 2px solid #000;
  object-fit: cover;
}

/* --- Titres --- */
.site-title {
  font-size: 2.6em;
  margin-bottom: 15px;
  font-weight: 700;
}

.welcome-msg {
  font-size: 18px;
  margin-bottom: 35px;
}

/* --- Champs --- */
.input-group {
  border: 1px solid #000;
  display: flex;
  align-items: center;
  background: rgba(255, 255, 255, 0.4);
  border-radius: 12px;
  margin: 16px 0;
  padding: 18px;
}

.input-group i {
  margin-right: 14px;
  font-size: 20px;
  color: #f1b800;
}

.input-group input {
  flex: 1;
  border: none;
  background: transparent;
  outline: none;
  font-size: 18px;
}

/* --- Options --- */
.options {
  display: flex;
  justify-content: space-between;
  font-size: 15px;
  margin-bottom: 30px;
}

.remember {
  display: flex;
  align-items: center;
  gap: 6px;
}

.forgot {
  color: #002a66;
  font-weight: 600;
  cursor: pointer;
}

/* --- Bouton connexion (agrandi) --- */
.login-btn {
  width: 100%;
  padding: 18px;
  border-radius: 12px;
  background: #f1b800;
  font-size: 18px;
  font-weight: bold;
  cursor: pointer;
}

/* --- Inscription --- */
.register-msg {
  margin-top: 30px;
  font-size: 16px;
}

.register-msg a {
  color: #f1b800;
  cursor: pointer;
  font-weight: 700;
}

/* --- Responsive --- */
@media (max-width: 480px) {
  .login-box {
    padding: 35px;
    width: 92%;
  }
  .site-title {
    font-size: 2em;
  }
  .input-group input {
    font-size: 16px;
  }
  .login-btn {
    font-size: 16px;
    padding: 14px;
  }
}
</style>
