import { createApp } from 'vue'
import axios from 'axios'
import App from './App.vue'
import router from './router'
import { clearSession, ensureValidSession, scheduleExpiry } from './utils/auth.js'
import './assets/dark-mode.css'
import './assets/animations.css'

// Session expirée → retour à la page de connexion avec un message
const goToLoginExpired = () => {
  if (router.currentRoute.value.path !== '/login') router.push({ path: '/login', query: { expired: '1' } })
}

// Au démarrage : on efface une session expirée (navigateur fermé puis rouvert après plus d'1 h)
ensureValidSession()

// À chaque changement de page : même vérification, puis déconnexion programmée à l'expiration
router.beforeEach(() => { ensureValidSession() })
router.afterEach(() => { scheduleExpiry(goToLoginExpired) })

// Filet de sécurité : si le serveur refuse le jeton (401), on efface la session au lieu d'échouer en silence
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    const headers = error.config?.headers
    const sentToken = !!(headers?.get?.('Authorization') ?? headers?.Authorization)
    const isLogin = (error.config?.url || '').includes('/api/login')
    if (error.response?.status === 401 && sentToken && !isLogin) {
      clearSession()
      goToLoginExpired()
    }
    return Promise.reject(error)
  }
)

const app = createApp(App)

app.use(router) // <-- on l'ajoute à l'application
app.mount('#app')
