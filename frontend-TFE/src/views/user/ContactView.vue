<script setup lang="ts">
import Navbar from "../Navbar.vue"
import Footer from "../Footer.vue"
import "@fortawesome/fontawesome-free/css/all.css"
import { ref, onMounted } from "vue"

/* DECLARATION GLOBAL WINDOW (TS FIX) */
declare global {
  interface Window {
    onCaptchaSuccess: (token: string) => void
    grecaptcha: any
  }
}

/* FORMULAIRE */
const contact = ref({
  name: "",
  email: "",
  subject: "",
  message: "",
})

const loading = ref(false)
const errorMessage = ref("")
const successMessage = ref("")
const captchaToken = ref("")

onMounted(() => {
  window.onCaptchaSuccess = (token: string) => {
    captchaToken.value = token
  }
})

/* VALIDATION EMAIL */
function isValidEmail(email: string) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
}


/* SUBMIT */   
async function submitContact() {
  errorMessage.value = ""
  successMessage.value = ""

  if (!contact.value.name ||
      !contact.value.email ||
      !contact.value.subject ||
      !contact.value.message) {
    errorMessage.value = "Veuillez remplir tous les champs."
    return
  }

  if (!isValidEmail(contact.value.email)) {
    errorMessage.value = "Adresse email invalide."
    return
  }

  if (contact.value.message.length < 10) {
    errorMessage.value = "Le message doit contenir au moins 10 caractères."
    return
  }

  if (!captchaToken.value) {
    errorMessage.value = "Veuillez valider le captcha."
    return
  }

  loading.value = true

  try {
    // 👉 Remplacer par ton appel API Symfony
    console.log("Contact envoyé :", {
      ...contact.value,
      captcha: captchaToken.value
    })

    successMessage.value = "Message envoyé avec succès 🎉"

    // Reset formulaire
    contact.value = { name: "", email: "", subject: "", message: "" }
    captchaToken.value = ""

    // Reset visuel du captcha
    if (window.grecaptcha) {
      window.grecaptcha.reset()
    }

  } catch (error) {
    errorMessage.value = "Une erreur est survenue."
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="page-container">
    <Navbar />

    <div class="form-wrapper">
      <div class="card">
        <h2><i class="fas fa-envelope"></i> Contactez-nous</h2>

        <form @submit.prevent="submitContact">

          <div class="input-group">
            <label>Nom *</label>
            <input v-model="contact.name" type="text" />
          </div>

          <div class="input-group">
            <label>Email *</label>
            <input v-model="contact.email" type="email" />
          </div>

          <div class="input-group">
            <label>Sujet *</label>
            <input v-model="contact.subject" type="text" />
          </div>

          <div class="input-group">
            <label>Message *</label>
            <textarea v-model="contact.message" rows="5"></textarea>
          </div>

          <!-- CAPTCHA -->
          <div class="captcha-container">
            <div
              class="g-recaptcha"
              data-sitekey="TA_CLE_SITE_ICI"
              data-callback="onCaptchaSuccess">
            </div>
          </div>

          <p v-if="errorMessage" class="error-msg">{{ errorMessage }}</p>
          <p v-if="successMessage" class="success-msg">{{ successMessage }}</p>

          <button class="btn-submit" :disabled="loading">
            {{ loading ? "Envoi..." : "Envoyer le message" }}
          </button>

        </form>
      </div>
    </div>

    <Footer />
  </div>
</template>

<style scoped>
.page-container {
  background: #f4f6f9;
  min-height: 100vh;
  font-family: Arial, sans-serif;
}

.form-wrapper {
  display: flex;
  justify-content: center;
  padding: 50px 20px;
}

.card {
  background: white;
  padding: 35px;
  border-radius: 14px;
  box-shadow: 0 8px 25px rgba(0,0,0,0.08);
  width: 100%;
  max-width: 600px;
}

.card h2 {
  margin-bottom: 25px;
  color: #0054a6;
  display: flex;
  align-items: center;
  gap: 10px;
}

.input-group {
  display: flex;
  flex-direction: column;
  margin-bottom: 18px;
}

.input-group label {
  font-weight: 600;
  margin-bottom: 6px;
  color: #444;
}

.input-group input,
.input-group textarea {
  padding: 10px;
  border-radius: 8px;
  border: 1px solid #ccc;
  font-size: 14px;
  font-family: inherit;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.input-group input:focus,
.input-group textarea:focus {
  border-color: #0054a6;
  outline: none;
  box-shadow: 0 0 0 2px rgba(0,84,166,0.15);
}

.captcha-container {
  margin: 20px 0;
}

.btn-submit {
  width: 100%;
  padding: 12px;
  border: none;
  background: #0054a6;
  color: white;
  font-weight: bold;
  border-radius: 10px;
  cursor: pointer;
  font-family: inherit;
  transition: background 0.2s;
}

.btn-submit:hover {
  background: #003f7d;
}

.btn-submit:disabled {
  background: #999;
  cursor: not-allowed;
}

.error-msg {
  color: #dc2626;
  margin-bottom: 15px;
  font-size: 14px;
}

.success-msg {
  color: #16a34a;
  margin-bottom: 15px;
  font-size: 14px;
}
</style>