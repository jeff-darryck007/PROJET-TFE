<template>
  <Navbar />

  <div class="reset-container">
    <div class="reset-box">

      <div class="avatar animate-pop">
        <img
          src="https://cdn-icons-png.flaticon.com/512/2889/2889676.png"
          alt="Reset password"
        />
      </div>

      <h2 class="site-title fade-in">Mot de passe oublié</h2>
      <p class="welcome-msg fade-in-delay">
        Entrez votre email pour recevoir un lien de réinitialisation 
      </p>

      <form @submit.prevent="handleReset" class="fade-in-delay2">
        <div class="input-group">
          <i class="fas fa-envelope"></i>
          <input
            type="email"
            v-model="email"
            placeholder="Votre email"
            required
          />
        </div>

        <button type="submit" class="login-btn">Envoyer le lien</button>

        <p class="register-msg">
          <button type="button" class="back-btn" @click="goToLogin">
            ← Retour à la connexion
          </button>
        </p>
      </form>

    </div>
  </div>

  <Footer />
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import Navbar from "./Navbar.vue";
import Footer from "./Footer.vue";

const router = useRouter();
const email = ref("");

const handleReset = () => {
  alert("Un lien de réinitialisation a été envoyé à votre adresse email.");
};

const goToLogin = () => router.push("/");
</script>

<style scoped>
/* --- Fond principal --- */
.reset-container {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background: #ffffff;
  padding: 20px;
  box-sizing: border-box;
}

/* --- Bloc --- */
.reset-box {
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(12px);
  border-radius: 20px;
  padding: 60px;
  width: 100%;
  max-width: 650px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
  text-align: center;
  transition: all 0.3s ease;
}

/* --- Avatar --- */
.avatar img {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  border: 2px solid #000;
  margin-bottom: 30px;
}

/* --- Titres --- */
.site-title {
  font-size: 2.8em;
  margin-bottom: 20px;
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
}

.input-group i {
  margin-right: 12px;
  color: #F1B800;
  font-size: 18px;
}

.input-group input {
  flex: 1;
  border: none;
  background: transparent;
  outline: none;
  font-size: 18px;
}

/* --- Bouton principal --- */
.login-btn {
  width: 100%;
  padding: 16px;
  background: #F1B800;
  color: #000;
  border-radius: 12px;
  font-weight: bold;
  font-size: 18px;
  cursor: pointer;
  transition: 0.2s ease;
}
.login-btn:hover {
  background: #e0a700;
}

/* --- Bouton retour --- */
.back-btn {
  background: none;
  border: none;
  color: #F1B800;
  font-weight: 700;
  font-size: 16px;
  cursor: pointer;
  padding: 0;
}
.back-btn:hover {
  text-decoration: underline;
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

/* --- Responsive --- */
@media (max-width: 1024px) {
  .reset-box { padding: 50px; }
  .avatar img { width: 100px; height: 100px; }
  .site-title { font-size: 2.4em; }
  .welcome-msg { font-size: 16px; }
  .input-group input { font-size: 16px; }
  .login-btn { font-size: 16px; padding: 14px; }
}

@media (max-width: 768px) {
  .reset-box { padding: 40px; }
  .avatar img { width: 90px; height: 90px; }
  .site-title { font-size: 2em; }
  .welcome-msg { font-size: 15px; }
  .input-group input { font-size: 15px; }
  .login-btn { font-size: 15px; padding: 12px; }
}

@media (max-width: 480px) {
  .reset-box { padding: 30px; width: 90%; }
  .avatar img { width: 80px; height: 80px; }
  .site-title { font-size: 1.8em; }
  .welcome-msg { font-size: 14px; }
  .input-group input { font-size: 14px; }
  .login-btn { font-size: 14px; padding: 10px; }
  .back-btn { font-size: 14px; }
}
</style>
