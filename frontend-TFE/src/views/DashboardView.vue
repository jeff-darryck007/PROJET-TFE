<script setup lang="ts">
import Navbar from './Navbar.vue'
import Footer from './Footer.vue'
import '@fortawesome/fontawesome-free/css/all.css';
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const isLoggedIn = ref(!!localStorage.getItem('token'));
const roles = ref(localStorage.getItem('roles') || '');

function checkIsAdmin(roleString: string) {
  return roleString.split(',').includes('admin');
}

const isAdmin = checkIsAdmin(roles.value);

function logout() {
  localStorage.removeItem('token');
  localStorage.removeItem('idUser');
  localStorage.removeItem('roles');
  isLoggedIn.value = false;
  router.push('/login');
}
</script>

<template>
  <div class="page">
    <Navbar />

    <div class="main-layout">

      <!-- SIDEBAR -->
      <aside class="sidebar">

        <div class="sidebar-section">
          <p class="section-label">Communauté</p>
          <div class="stat-item">
            <span class="stat-icon"><i class="fas fa-users"></i></span>
            <span class="stat-text">Utilisateurs</span>
            <span class="stat-badge">102</span>
          </div>
          <div class="stat-item">
            <span class="stat-icon"><i class="fas fa-hand-holding-heart"></i></span>
            <span class="stat-text">Donnateurs</span>
            <span class="stat-badge">35</span>
          </div>
          <div class="stat-item">
            <span class="stat-icon"><i class="fas fa-search"></i></span>
            <span class="stat-text">Demandeurs</span>
            <span class="stat-badge">12</span>
          </div>
        </div>

        <div class="sidebar-section">
          <p class="section-label">Catégories</p>
          <ul class="cat-list">
            <li class="cat-item"><i class="fas fa-chair"></i> Bureau</li>
            <li class="cat-item"><i class="fas fa-laptop"></i> Électronique</li>
            <li class="cat-item"><i class="fas fa-couch"></i> Meubles</li>
            <li class="cat-item see-all"><i class="fas fa-th-list"></i> Toutes les catégories</li>
          </ul>
        </div>

        <div class="sidebar-section">
          <p class="section-label">Compte</p>
          <ul class="action-list">
            <li v-if="!isLoggedIn" class="action-item">
              <a href="/login"><i class="fas fa-sign-in-alt"></i> Connexion</a>
            </li>
            <li v-if="!isLoggedIn" class="action-item">
              <a href="/register"><i class="fas fa-user-plus"></i> S'inscrire</a>
            </li>
            <li v-if="isLoggedIn" class="action-item action-item--logout" @click="logout">
              <i class="fas fa-sign-out-alt"></i> Déconnexion
            </li>
          </ul>
        </div>

        <div v-if="isLoggedIn && isAdmin" class="sidebar-section sidebar-section--admin">
          <p class="section-label">Administration</p>
          <ul class="action-list">
            <li class="action-item"><i class="fas fa-users-cog"></i> Utilisateurs</li>
            <li class="action-item"><i class="fas fa-comments"></i> Commentaires</li>
            <li class="action-item"><i class="fas fa-bullhorn"></i> Annonces</li>
            <li class="action-item"><i class="fas fa-chart-line"></i> Statistiques</li>
            <li class="action-item"><i class="fas fa-credit-card"></i> Abonnements</li>
          </ul>
        </div>

      </aside>

      <!-- CONTENU -->
      <main class="listing">

        <!-- RECHERCHE -->
        <div class="search-block">
          <div class="search-main">
            <i class="fas fa-search search-icon"></i>
            <input
              type="text"
              placeholder="Rechercher un article, une catégorie..."
              class="search-input"
            />
          </div>
          <div class="search-secondary">
            <input type="text" placeholder="Code postal" class="postal-input" />
            <select class="distance-select">
              <option>Toutes les distances</option>
              <option>&lt; 3 km</option>
              <option>&lt; 5 km</option>
              <option>&lt; 10 km</option>
              <option>&lt; 20 km</option>
              <option>&lt; 30 km</option>
              <option>&lt; 50 km</option>
            </select>
            <button class="search-btn">
              <i class="fas fa-search"></i> Rechercher
            </button>
          </div>
        </div>

        <h2 class="section-title">Pour vous</h2>

        <div class="grid">
          <div class="card" v-for="n in 12" :key="n">
            <div class="card-img-wrap">
              <img src="../image/0ddc5b14a02b31812e9a41ea30dad92c.jpg" alt="Article" />
              <button class="fav-btn" title="Ajouter aux favoris">
                <i class="fas fa-heart"></i>
              </button>
            </div>
            <div class="card-body">
              <p class="card-title">Bureau de réunion</p>
              <p class="card-location">
                <i class="fas fa-map-marker-alt"></i> Paris, 75015
              </p>
              <span class="card-badge">À donner</span>
              <button class="card-btn">Voir l'article</button>
            </div>
          </div>
        </div>

      </main>
    </div>

    <Footer />
  </div>
</template>

<style scoped>
/* === LAYOUT === */
.page {
  background: #f4f6f9;
  min-height: 100vh;
  font-family: Arial, sans-serif;
}

.main-layout {
  display: flex;
  gap: 24px;
  padding: 28px 24px;
  max-width: 1400px;
  margin: 0 auto;
}

/* === SIDEBAR === */
.sidebar {
  width: 240px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.sidebar-section {
  background: white;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.sidebar-section--admin {
  border: 1px solid #e3eefc;
  background: #f8fbff;
}

.section-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: #999;
  margin: 0 0 12px 0;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 7px 0;
  border-bottom: 1px solid #f5f5f5;
  font-size: 14px;
  color: #444;
}

.stat-item:last-child {
  border-bottom: none;
}

.stat-icon {
  color: #0054a6;
  width: 20px;
  text-align: center;
}

.stat-text {
  flex: 1;
}

.stat-badge {
  background: #eef4ff;
  color: #0054a6;
  font-size: 12px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 20px;
}

.cat-list,
.action-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.cat-item {
  padding: 8px 10px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  color: #555;
  transition: background 0.2s, color 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
}

.cat-item:hover {
  background: #f1f5f9;
  color: #0054a6;
}

.cat-item.see-all {
  color: #0054a6;
  font-weight: 600;
  margin-top: 4px;
}

.action-item {
  padding: 8px 10px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  color: #555;
  transition: background 0.2s, color 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
  list-style: none;
}

.action-item a {
  color: inherit;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
}

.action-item:hover {
  background: #f1f5f9;
  color: #0054a6;
}

.action-item--logout:hover {
  color: #e53e3e;
  background: #fff5f5;
}

/* === LISTING === */
.listing {
  flex: 1;
  min-width: 0;
}

/* === RECHERCHE === */
.search-block {
  background: white;
  border-radius: 14px;
  padding: 20px;
  margin-bottom: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.search-main {
  position: relative;
  margin-bottom: 12px;
}

.search-icon {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: #aaa;
  font-size: 16px;
}

.search-input {
  width: 100%;
  padding: 13px 16px 13px 44px;
  border: 2px solid #e8ecf0;
  border-radius: 10px;
  font-size: 15px;
  outline: none;
  transition: border-color 0.2s;
  box-sizing: border-box;
  font-family: inherit;
}

.search-input:focus {
  border-color: #0054a6;
}

.search-secondary {
  display: flex;
  gap: 10px;
}

.postal-input {
  flex: 1;
  padding: 10px 14px;
  border: 2px solid #e8ecf0;
  border-radius: 10px;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s;
  font-family: inherit;
}

.postal-input:focus {
  border-color: #0054a6;
}

.distance-select {
  padding: 10px 14px;
  border: 2px solid #e8ecf0;
  border-radius: 10px;
  font-size: 14px;
  background: white;
  outline: none;
  cursor: pointer;
  transition: border-color 0.2s;
  font-family: inherit;
}

.distance-select:focus {
  border-color: #0054a6;
}

.search-btn {
  background: #0054a6;
  color: white;
  border: none;
  padding: 10px 22px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
  white-space: nowrap;
  font-family: inherit;
  display: flex;
  align-items: center;
  gap: 8px;
}

.search-btn:hover {
  background: #003f7d;
}

/* === TITRE SECTION === */
.section-title {
  font-size: 20px;
  font-weight: 700;
  color: #222;
  margin: 0 0 18px 0;
}

/* === GRID === */
.grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 18px;
}

/* === CARD === */
.card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 28px rgba(0, 0, 0, 0.12);
}

.card-img-wrap {
  position: relative;
  height: 160px;
  overflow: hidden;
}

.card-img-wrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.35s;
}

.card:hover .card-img-wrap img {
  transform: scale(1.05);
}

.fav-btn {
  position: absolute;
  top: 10px;
  right: 10px;
  background: white;
  border: none;
  border-radius: 50%;
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #ccc;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  transition: color 0.2s, transform 0.2s;
}

.fav-btn:hover {
  color: #e53e3e;
  transform: scale(1.1);
}

.card-body {
  padding: 12px 14px 14px;
}

.card-title {
  font-weight: 600;
  font-size: 15px;
  color: #222;
  margin: 0 0 4px 0;
}

.card-location {
  font-size: 12px;
  color: #888;
  margin: 0 0 10px 0;
  display: flex;
  align-items: center;
  gap: 4px;
}

.card-badge {
  display: inline-block;
  background: #e8f4e8;
  color: #0a7d2c;
  font-size: 12px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 20px;
  margin-bottom: 10px;
}

.card-btn {
  width: 100%;
  background: #f1b800;
  border: none;
  border-radius: 8px;
  padding: 9px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s, transform 0.1s;
  color: #222;
  font-family: inherit;
}

.card-btn:hover {
  background: #dca600;
  transform: translateY(-1px);
}

/* === RESPONSIVE === */
@media (max-width: 1100px) {
  .grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 900px) {
  .main-layout { flex-direction: column; }
  .sidebar { width: 100%; }
}

@media (max-width: 600px) {
  .main-layout { padding: 16px; gap: 16px; }
  .search-secondary { flex-direction: column; }
  .grid { grid-template-columns: 1fr; }
}
</style>
