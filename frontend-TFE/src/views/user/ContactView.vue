<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import Navbar from '../Navbar.vue'
import Footer from '../Footer.vue'
import '@fortawesome/fontawesome-free/css/all.css'
import { sendContactMessage } from '../../controller/controllerContact.js'

const router = useRouter()
const token  = localStorage.getItem('token')

const subject      = ref('')
const message      = ref('')
const loading      = ref(false)
const errorMessage = ref('')
const sent         = ref(false)
const anouncementId = ref(null)

/* --- CAPTCHA MATHÉMATIQUE --- */
function randInt(min, max) {
  return Math.floor(Math.random() * (max - min + 1)) + min
}
const numA       = ref(randInt(1, 12))
const numB       = ref(randInt(1, 12))
const captchaAnswer = ref('')
const captchaValid  = computed(() => parseInt(captchaAnswer.value) === numA.value + numB.value)

function refreshCaptcha() {
  numA.value = randInt(1, 12)
  numB.value = randInt(1, 12)
  captchaAnswer.value = ''
}

onMounted(() => {
  if (!token) router.push('/login')
})

async function submitContact() {
  errorMessage.value = ''

  if (!subject.value.trim() || !message.value.trim()) {
    errorMessage.value = 'Veuillez remplir tous les champs.'
    return
  }

  if (message.value.trim().length < 10) {
    errorMessage.value = 'Le message doit contenir au moins 10 caractères.'
    return
  }

  if (!captchaAnswer.value) {
    errorMessage.value = 'Veuillez répondre à la question de vérification.'
    return
  }

  if (!captchaValid.value) {
    errorMessage.value = 'Réponse incorrecte, réessayez.'
    refreshCaptcha()
    return
  }

  loading.value = true
  try {
    const data = await sendContactMessage({ subject: subject.value.trim(), message: message.value.trim() }, token)
    anouncementId.value = data.anouncementId ?? null
    sent.value = true
  } catch (err) {
    const msg = err?.response?.data?.error
    errorMessage.value = msg || 'Une erreur est survenue, veuillez réessayer.'
    refreshCaptcha()
  } finally {
    loading.value = false
  }
}

function goToMessages() {
  if (anouncementId.value) {
    router.push({ name: 'Message-View', query: { annonce: anouncementId.value } })
  } else {
    router.push('/MessageView')
  }
}
</script>

<template>
  <div class="page-container">
    <Navbar />

    <div class="form-wrapper">
      <div class="card pg-scale-in">

        <!-- SUCCÈS -->
        <div v-if="sent" class="success-state">
          <div class="success-icon"><i class="fas fa-check-circle"></i></div>
          <h2>Message envoyé !</h2>
          <p>Notre équipe a bien reçu votre message et vous répondra prochainement dans votre messagerie.</p>
          <button class="btn-msg" @click="goToMessages">
            <i class="fas fa-envelope"></i> Voir mes messages
          </button>
        </div>

        <!-- FORMULAIRE -->
        <template v-else>
          <h2><i class="fas fa-envelope"></i> Contactez-nous</h2>
          <p class="subtitle">Remplissez le formulaire ci-dessous, nous vous répondrons dans votre messagerie.</p>

          <form @submit.prevent="submitContact">

            <div class="input-group">
              <label>Sujet *</label>
              <input v-model="subject" type="text" placeholder="Ex: Problème avec une annonce" maxlength="120" />
            </div>

            <div class="input-group">
              <label>Message *</label>
              <textarea v-model="message" rows="6" placeholder="Décrivez votre demande en détail..." maxlength="2000"></textarea>
              <span class="char-count">{{ message.length }}/2000</span>
            </div>

            <!-- CAPTCHA MATHÉMATIQUE -->
            <div class="captcha-box">
              <i class="fas fa-shield-alt captcha-icon"></i>
              <span class="captcha-question">Combien font <strong>{{ numA }} + {{ numB }}</strong> ?</span>
              <input
                v-model="captchaAnswer"
                type="number"
                class="captcha-input"
                placeholder="?"
                min="0"
                max="99"
              />
            </div>

            <p v-if="errorMessage" class="error-msg"><i class="fas fa-exclamation-circle"></i> {{ errorMessage }}</p>

            <button class="btn-submit" type="submit" :disabled="loading">
              <i v-if="!loading" class="fas fa-paper-plane"></i>
              <i v-else class="fas fa-spinner fa-spin"></i>
              {{ loading ? 'Envoi en cours...' : 'Envoyer le message' }}
            </button>

          </form>
        </template>

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
  padding: 50px 20px 70px;
}

.card {
  background: white;
  padding: 38px 40px;
  border-radius: 16px;
  box-shadow: 0 8px 28px rgba(0,0,0,0.08);
  width: 100%;
  max-width: 600px;
}

h2 {
  margin: 0 0 8px;
  color: #0054a6;
  font-size: 22px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.subtitle {
  color: #777;
  font-size: 14px;
  margin: 0 0 26px;
}

.input-group {
  display: flex;
  flex-direction: column;
  margin-bottom: 20px;
  position: relative;
}

.input-group label {
  font-weight: 600;
  margin-bottom: 6px;
  color: #444;
  font-size: 14px;
}

.input-group input,
.input-group textarea {
  padding: 11px 12px;
  border-radius: 9px;
  border: 1.5px solid #ddd;
  font-size: 14px;
  font-family: inherit;
  transition: border-color 0.2s, box-shadow 0.2s;
  resize: vertical;
}

.input-group input:focus,
.input-group textarea:focus {
  border-color: #0054a6;
  outline: none;
  box-shadow: 0 0 0 3px rgba(0,84,166,0.12);
}

.char-count {
  text-align: right;
  font-size: 11px;
  color: #aaa;
  margin-top: 4px;
}

/* CAPTCHA */
.captcha-box {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #f0f6ff;
  border: 1.5px solid #c7dcf5;
  border-radius: 10px;
  padding: 14px 16px;
  margin-bottom: 22px;
}

.captcha-icon {
  color: #0054a6;
  font-size: 18px;
  flex-shrink: 0;
}

.captcha-question {
  flex: 1;
  font-size: 15px;
  color: #333;
}

.captcha-input {
  width: 68px;
  padding: 8px 10px;
  border-radius: 8px;
  border: 1.5px solid #aac8e8;
  font-size: 15px;
  font-family: inherit;
  text-align: center;
  transition: border-color 0.2s;
}

.captcha-input:focus {
  border-color: #0054a6;
  outline: none;
}

/* BOUTON */
.btn-submit {
  width: 100%;
  padding: 13px;
  border: none;
  background: #0054a6;
  color: white;
  font-weight: 700;
  font-size: 15px;
  border-radius: 10px;
  cursor: pointer;
  font-family: inherit;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  transition: background 0.2s;
}

.btn-submit:hover:not(:disabled) {
  background: #003f7d;
}

.btn-submit:disabled {
  background: #9ab3d4;
  cursor: not-allowed;
}

/* MESSAGES */
.error-msg {
  color: #dc2626;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 14px;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* ÉTAT SUCCÈS */
.success-state {
  text-align: center;
  padding: 20px 10px 10px;
}

.success-icon {
  font-size: 64px;
  color: #16a34a;
  margin-bottom: 18px;
}

.success-state h2 {
  justify-content: center;
  color: #16a34a;
  font-size: 24px;
  margin-bottom: 12px;
}

.success-state p {
  color: #555;
  font-size: 15px;
  margin-bottom: 28px;
  line-height: 1.6;
}

.btn-msg {
  padding: 12px 28px;
  background: #0054a6;
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  font-size: 15px;
  cursor: pointer;
  font-family: inherit;
  display: inline-flex;
  align-items: center;
  gap: 9px;
  transition: background 0.2s;
}

.btn-msg:hover {
  background: #003f7d;
}

@media (max-width: 600px) {
  .card { padding: 26px 20px; }
  .captcha-box { flex-wrap: wrap; }
}
</style>
