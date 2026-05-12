<template>
  <nav class="main-navbar" :class="{ sticky: isSticky }">
    <div class="nav-left">
      <router-link class="logo" to="/">
        <i class="fas fa-hands-helping"></i> PartageGratuit
      </router-link>
    </div>

    <div class="nav-center">
      <router-link to="/">Accueil</router-link>
      <router-link to="/PublierView">Publier</router-link>
      <router-link to="/MessageView">Messages</router-link>
      <router-link to="/FavorisView">Favoris</router-link>
      <router-link to="/ContactView">Contact</router-link>
    </div>

    <div class="nav-right">
      <router-link to="/NotificationView" class="link-nav-icon hide-xs">
        <i class="fas fa-bell nav-icon"></i>
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
      <router-link to="/PublierView" @click="closeMenu">
        <i class="fas fa-plus-circle"></i><span>Publier</span>
      </router-link>
      <router-link to="/MessageView" @click="closeMenu">
        <i class="fas fa-envelope"></i><span>Messages</span>
      </router-link>
      <router-link to="/FavorisView" @click="closeMenu">
        <i class="fas fa-heart"></i><span>Favoris</span>
      </router-link>
      <router-link to="/NotificationView" @click="closeMenu">
        <i class="fas fa-bell"></i><span>Notifications</span>
      </router-link>
      <router-link to="/ProfileView" @click="closeMenu">
        <i class="fas fa-user-circle"></i><span>Profil</span>
      </router-link>
      <router-link to="/ContactView" @click="closeMenu">
        <i class="fas fa-envelope-open-text"></i><span>Contact</span>
      </router-link>
      <a href="#" @click.prevent="toggleDark; closeMenu()">
        <i class="fas" :class="darkMode ? 'fa-sun' : 'fa-moon'"></i>
        <span>{{ darkMode ? 'Mode clair' : 'Mode sombre' }}</span>
      </a>
      <a href="#" class="logout-link" @click.prevent="handleLogout">
        <i class="fas fa-sign-out-alt"></i><span>Déconnexion</span>
      </a>
    </div>
  </nav>

  <Teleport to="body">
    <div v-if="menuOpen" class="menu-backdrop" @click="closeMenu"></div>
  </Teleport>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const menuOpen = ref(false)
const isSticky = ref(false)
const darkMode = ref(false)

const toggleMenu = () => { menuOpen.value = !menuOpen.value }
const closeMenu = () => { menuOpen.value = false }
const handleScroll = () => { isSticky.value = window.scrollY > 10 }

function toggleDark() {
  darkMode.value = !darkMode.value
  document.documentElement.classList.toggle('dark-mode', darkMode.value)
  localStorage.setItem('darkMode', darkMode.value ? '1' : '0')
}

function handleLogout() {
  localStorage.removeItem('token')
  localStorage.removeItem('idUser')
  localStorage.removeItem('roles')
  closeMenu()
  router.push('/login')
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
  if (localStorage.getItem('darkMode') === '1') {
    darkMode.value = true
    document.documentElement.classList.add('dark-mode')
  }
})
onBeforeUnmount(() => window.removeEventListener('scroll', handleScroll))
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
  font-weight: bold;
  color: #f1b800;
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
  max-height: 560px;
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
