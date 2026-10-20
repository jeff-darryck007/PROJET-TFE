<template>
  <nav class="main-navbar" :class="{ sticky: isSticky }">
    <div class="nav-left">
      <router-link class="logo" to="/">
        <i class="fas fa-hands-helping"></i>
        <strong>TFE JEFF</strong>
      </router-link>
    </div>

    <div class="nav-center">
      <router-link to="/">Accueil</router-link>
      <router-link v-if="canPublier" to="/PublierView">Publier</router-link>
      <button v-if="isVisiteur" class="btn-donor" @click="openDonorModal">
        <i class="fas fa-hand-holding-heart"></i> Devenir donateur
      </button>
      <router-link to="/MessageView">Messages</router-link>
      <router-link to="/FavorisView">Favoris</router-link>
      <router-link v-if="canContact" to="/ContactView">Contact</router-link>
    </div>

    <div class="nav-right">
      <router-link to="/NotificationView" class="link-nav-icon hide-xs notif-wrap">
        <i class="fas fa-bell nav-icon"></i>
        <span v-if="unreadCount > 0" class="notif-badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
      </router-link>
      <router-link to="/MessageView" class="link-nav-icon hide-xs">
        <i class="fas fa-envelope nav-icon"></i>
      </router-link>
      <router-link to="/ProfileView" class="link-nav-icon hide-xs">
        <i class="fas fa-user-circle nav-icon"></i>
      </router-link>
      <button class="dark-toggle hide-xs" @click="toggleDark" :title="darkMode ? 'Mode clair' : 'Mode sombre'">
        <i class="fas" :class="darkMode ? 'fa-sun' : 'fa-moon'"></i>
      </button>
      <i
        class="fas nav-toggle"
        :class="menuOpen ? 'fa-times' : 'fa-bars'"
        @click="toggleMenu"
      ></i>
    </div>

    <div class="mobile-menu" :class="{ open: menuOpen }">
      <router-link to="/" @click="closeMenu">
        <i class="fas fa-home"></i><span>Accueil</span>
      </router-link>
      <router-link v-if="canPublier" to="/PublierView" @click="closeMenu">
        <i class="fas fa-plus-circle"></i><span>Publier</span>
      </router-link>
      <a v-if="isVisiteur" href="#" class="donor-link" @click.prevent="closeMenu(); openDonorModal()">
        <i class="fas fa-hand-holding-heart"></i><span>Devenir donateur</span>
      </a>
      <template v-if="isLoggedIn">
        <router-link to="/MessageView" @click="closeMenu">
          <i class="fas fa-envelope"></i><span>Messages</span>
        </router-link>
        <router-link to="/FavorisView" @click="closeMenu">
          <i class="fas fa-heart"></i><span>Favoris</span>
        </router-link>
        <router-link to="/NotificationView" @click="closeMenu" class="mobile-notif">
          <i class="fas fa-bell"></i>
          <span>Notifications</span>
          <span v-if="unreadCount > 0" class="notif-badge-mobile">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
        </router-link>
        <router-link to="/ProfileView" @click="closeMenu">
          <i class="fas fa-user-circle"></i><span>Profil</span>
        </router-link>
        <router-link v-if="canContact" to="/ContactView" @click="closeMenu">
          <i class="fas fa-envelope-open-text"></i><span>Contact</span>
        </router-link>
      </template>
      <template v-else>
        <router-link to="/login" @click="closeMenu">
          <i class="fas fa-sign-in-alt"></i><span>Connexion</span>
        </router-link>
        <router-link to="/register" @click="closeMenu">
          <i class="fas fa-user-plus"></i><span>S'inscrire</span>
        </router-link>
      </template>
      <a href="#" @click.prevent="toggleDark(); closeMenu()">
        <i class="fas" :class="darkMode ? 'fa-sun' : 'fa-moon'"></i>
        <span>{{ darkMode ? 'Mode clair' : 'Mode sombre' }}</span>
      </a>
      <a v-if="isLoggedIn" href="#" class="logout-link" @click.prevent="handleLogout">
        <i class="fas fa-sign-out-alt"></i><span>Déconnexion</span>
      </a>
    </div>
  </nav>

  <Teleport to="body">
    <div v-if="menuOpen" class="menu-backdrop" @click="closeMenu"></div>

    <!-- Confirmation : devenir donateur -->
    <div v-if="donorModal" class="donor-overlay" @click.self="donorModal = false">
      <div class="donor-modal" role="dialog" aria-labelledby="donor-title">
        <h3 id="donor-title"><i class="fas fa-hand-holding-heart"></i> Devenir donateur</h3>
        <p>
          En devenant donateur, vous pourrez <strong>publier des annonces</strong> pour donner vos objets.
          Vous gardez votre compte, vos messages et vos favoris.
        </p>
        <p v-if="donorError" class="donor-error">
          <i class="fas fa-exclamation-circle"></i> {{ donorError }}
        </p>
        <div class="donor-actions">
          <button class="donor-cancel" :disabled="becomingDonor" @click="donorModal = false">Annuler</button>
          <button class="donor-confirm" :disabled="becomingDonor" @click="confirmBecomeDonor">
            <i :class="becomingDonor ? 'fas fa-spinner fa-spin' : 'fas fa-check'"></i>
            {{ becomingDonor ? 'Changement...' : 'Oui, devenir donateur' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { fetchUnreadCount } from '../controller/controllerNotification.js'
import { becomeDonor } from '../controller/controllerLogin.js'

const router   = useRouter()
const menuOpen  = ref(false)
const isSticky  = ref(false)
const darkMode  = ref(false)
const unreadCount = ref(0)
// Session ouverte ? (la navbar est recréée à chaque page, la lecture au montage suffit)
const isLoggedIn  = ref(!!localStorage.getItem('token'))

// ref (et non computed) : le menu doit se mettre à jour dès que le rôle change
const readRoles = () => {
  const raw = localStorage.getItem('roles')
  return raw ? raw.split(',').map(r => r.trim()) : []
}
const userRoles = ref(readRoles())

// --- Devenir donateur (visiteurs uniquement) ---
const isVisiteur    = computed(() => userRoles.value.includes('visiteur'))
const donorModal    = ref(false)
const becomingDonor = ref(false)
const donorError    = ref('')

function openDonorModal() {
  donorError.value = ''
  donorModal.value = true
}

async function confirmBecomeDonor() {
  becomingDonor.value = true
  donorError.value = ''
  try {
    const res = await becomeDonor(localStorage.getItem('token'))
    // Même format qu'à la connexion ("donateur,ROLE_USER")
    localStorage.setItem('roles', res.roles)
    userRoles.value = readRoles()
    donorModal.value = false
    router.push('/PublierView')
  } catch (e) {
    donorError.value = e.message
  } finally {
    becomingDonor.value = false
  }
}

const canPublier = computed(() =>
  userRoles.value.some(r => r === 'donateur' || r === 'admin')
)
const canContact = computed(() =>
  userRoles.value.some(r => r === 'visiteur' || r === 'donateur')
)

let pollInterval = null

const toggleMenu = () => { menuOpen.value = !menuOpen.value }
const closeMenu  = () => { menuOpen.value = false }
const handleScroll = () => { isSticky.value = window.scrollY > 10 }

async function loadUnreadCount() {
  const token = localStorage.getItem('token')
  if (!token) { unreadCount.value = 0; return }
  try {
    unreadCount.value = await fetchUnreadCount(token)
  } catch { /* silencieux */ }
}

function toggleDark() {
  darkMode.value = !darkMode.value
  document.documentElement.classList.toggle('dark-mode', darkMode.value)
  localStorage.setItem('darkMode', darkMode.value ? '1' : '0')
}

function handleLogout() {
  localStorage.removeItem('token')
  localStorage.removeItem('idUser')
  localStorage.removeItem('roles')
  userRoles.value = []
  isLoggedIn.value = false
  unreadCount.value = 0
  closeMenu()
  router.push('/login')
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
  // Écoute les mises à jour depuis NotificationView
  window.addEventListener('notif-count-changed', loadUnreadCount)

  if (localStorage.getItem('darkMode') === '1') {
    darkMode.value = true
    document.documentElement.classList.add('dark-mode')
  }

  loadUnreadCount()
  // Polling toutes les 30 secondes
  pollInterval = setInterval(loadUnreadCount, 30_000)
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', handleScroll)
  window.removeEventListener('notif-count-changed', loadUnreadCount)
  clearInterval(pollInterval)
})
</script>

<style scoped>
.main-navbar {
  background: white;
  padding: 12px 28px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 1000;
  transition: box-shadow 0.3s, padding 0.3s;
}

.main-navbar.sticky {
  box-shadow: 0 4px 18px rgba(0, 0, 0, 0.18);
}

.logo {
  font-size: 20px;
  font-weight: 800;
  color: #111; /* blanc en mode sombre (voir dark-mode.css) */
  text-decoration: none;
  white-space: nowrap;
  display: flex;
  align-items: center;
  gap: 8px;
}

.nav-center a {
  margin: 0 10px;
  color: #333;
  text-decoration: none;
  font-weight: 600;
  font-size: 15px;
  transition: color 0.2s;
}

.nav-center a:hover,
.nav-center a.router-link-active {
  color: #f1b800;
}

.nav-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.link-nav-icon {
  color: #333;
  text-decoration: none;
  transition: color 0.2s;
}

.link-nav-icon:hover {
  color: #f1b800;
}

.nav-icon {
  font-size: 20px;
  cursor: pointer;
}

.nav-toggle {
  display: none;
  font-size: 22px;
  cursor: pointer;
  color: #333;
  transition: color 0.2s;
}

.nav-toggle:hover {
  color: #f1b800;
}

.mobile-menu {
  display: none;
  flex-direction: column;
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.35s ease;
  z-index: 1001;
}

.mobile-menu.open {
  max-height: 640px;
}

.mobile-menu a {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 22px;
  border-bottom: 1px solid #f0f0f0;
  text-decoration: none;
  color: #333;
  font-weight: 500;
  font-size: 15px;
  transition: background 0.2s, color 0.2s;
}

.mobile-menu a:last-child {
  border-bottom: none;
}

.mobile-menu a:hover {
  background: #fffbea;
  color: #f1b800;
}

.mobile-menu a.router-link-active {
  color: #f1b800;
  background: #fffbea;
}

.mobile-menu i {
  width: 20px;
  text-align: center;
  font-size: 16px;
  color: #f1b800;
}

/* --- Devenir donateur --- */
.btn-donor {
  margin: 0 10px;
  padding: 7px 14px;
  border: none;
  border-radius: 20px;
  background: #111;
  color: white;
  font-family: inherit;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: background 0.2s;
}

.btn-donor:hover {
  background: #333;
}

.mobile-menu a.donor-link,
.mobile-menu a.donor-link i {
  color: #111;
  font-weight: 600;
}

.donor-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  z-index: 2000;
}

.donor-modal {
  background: white;
  border-radius: 14px;
  padding: 24px;
  max-width: 420px;
  width: 100%;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25);
}

.donor-modal h3 {
  margin: 0 0 12px;
  font-size: 18px;
  color: #111;
  display: flex;
  align-items: center;
  gap: 8px;
}

.donor-modal p {
  margin: 0 0 16px;
  font-size: 14px;
  line-height: 1.5;
  color: #444;
}

.donor-modal .donor-error {
  color: #dc2626;
}

.donor-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  flex-wrap: wrap;
}

.donor-actions button {
  padding: 10px 16px;
  border-radius: 10px;
  border: none;
  font-family: inherit;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.donor-cancel {
  background: #eee;
  color: #333;
}

.donor-confirm {
  background: #111;
  color: white;
}

.donor-confirm:hover:not(:disabled) {
  background: #333;
}

.donor-actions button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.logout-link {
  color: #e53e3e !important;
}

.logout-link i {
  color: #e53e3e !important;
}

.logout-link:hover {
  background: #fff5f5 !important;
  color: #c53030 !important;
}

@media (max-width: 1100px) {
  .nav-center a {
    margin: 0 7px;
    font-size: 14px;
  }
}

@media (max-width: 900px) {
  .nav-center { display: none; }
  .nav-toggle { display: block; }
  .mobile-menu { display: flex; max-height: 0; }
}

@media (max-width: 600px) {
  .main-navbar { padding: 10px 16px; }
  .logo { font-size: 17px; }
  .hide-xs { display: none; }
  .nav-icon { font-size: 18px; }
}

/* BADGE NOTIFICATION */
.notif-wrap {
  position: relative;
}

.notif-badge {
  position: absolute;
  top: -7px;
  right: -9px;
  background: #dc2626;
  color: white;
  font-size: 10px;
  font-weight: 700;
  min-width: 18px;
  height: 18px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
  border: 2px solid white;
  line-height: 1;
}

.notif-badge-mobile {
  margin-left: auto;
  background: #dc2626;
  color: white;
  font-size: 11px;
  font-weight: 700;
  min-width: 20px;
  height: 20px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 5px;
}

.dark-toggle {
  background: none;
  border: none;
  cursor: pointer;
  color: #333;
  font-size: 18px;
  padding: 4px;
  display: flex;
  align-items: center;
  transition: color 0.2s;
}

.dark-toggle:hover {
  color: #f1b800;
}

@media (max-width: 400px) {
  .main-navbar { padding: 8px 12px; }
  .logo { font-size: 15px; }
  .nav-toggle { font-size: 20px; }
}
</style>

<style>
html, body { overflow-x: hidden; }

.menu-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.35);
  z-index: 999;
}
</style>
