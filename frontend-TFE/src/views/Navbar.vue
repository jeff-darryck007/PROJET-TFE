<template>
  <nav class="main-navbar">
    <div class="nav-left">
      <span class="logo">PartageGratuit</span>
    </div>

    <div class="nav-center">
      <a href="/">Accueil</a>
      <a href="/PublierView">Publier</a>
      <a href="/MessageView">Messages</a>
      <a href="/FavorisView">Favoris</a>
    </div>

    <div class="nav-right">
      <a href="/NotificationView" class="link-nav-icon">
        <i class="fas fa-bell nav-icon"></i>
      </a>

      <a href="/MessageView" class="link-nav-icon">
        <i class="fas fa-envelope nav-icon"></i>
      </a>

      <a href="/ProfileView" class="link-nav-icon">
        <i class="fas fa-user-circle nav-icon"></i>
      </a>

      <i
        class="fas fa-bars nav-toggle"
        @click="menuOpen = !menuOpen"
      ></i>
    </div>

    <div class="mobile-menu" :class="{ open: menuOpen }">
      <a href="">Accueil</a>
      <a href="">Publier</a>
      <a href="#">Messages</a>
      <a href="#">Favoris</a>
      <a href="#">Profil</a>
      <a href="#">Déconnexion</a>
    </div>
  </nav>
</template>

<script>
export default {
  data() {
    return {
      menuOpen: false,
      isSticky: false,
    };
  },
  methods: {
    toggleMenu() {
      this.menuOpen = !this.menuOpen;
    },
    handleScroll() {
      this.isSticky = window.scrollY > 10;
    },
  },
  mounted() {
    window.addEventListener("scroll", this.handleScroll);
  },
  beforeUnmount() {
    window.removeEventListener("scroll", this.handleScroll);
  },
};
</script>

<style scoped>
.link-nav-icon {
  color: #333;
  text-decoration: none;
}

.main-navbar {
  background: white;
  padding: 12px 25px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 1000;
}

.logo {
  font-size: 20px;
  font-weight: bold;
  color: #f1b800;
}

.nav-center a {
  margin: 0 12px;
  color: #333;
  text-decoration: none;
  font-weight: 600;
}

.nav-right {
  display: flex;
  align-items: center;
  gap: 14px;
}

.nav-icon {
  font-size: 20px;
  cursor: pointer;
}

.nav-toggle {
  display: none;
  font-size: 22px;
  cursor: pointer;
}

/* Mobile */
.mobile-menu {
  display: none;
  flex-direction: column;
  background: white;
  position: absolute;
  top: 60px;
  left: 0;
  width: 100%;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.mobile-menu a {
  padding: 15px;
  border-bottom: 1px solid #eee;
  text-decoration: none;
  color: #333;
}

/* Responsive */
@media (max-width: 900px) {
  .nav-center {
    display: none;
  }

  .nav-toggle {
    display: block;
  }

  .mobile-menu {
    display: flex;
  }

  .mobile-menu:not(.open) {
    display: none;
  }
}
</style>

<style>
html,
body {
  overflow-x: hidden;
}
</style>