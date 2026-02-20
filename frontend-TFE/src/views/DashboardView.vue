<script setup lang="ts">
import Navbar from './Navbar.vue'
import '@fortawesome/fontawesome-free/css/all.css';
import { ref } from 'vue';

// Vérifier si le token existe dans localStorage
const isLoggedIn = ref(!!localStorage.getItem('token'));
const roles = ref(localStorage.getItem('roles') || 'null');

console.log("Roles from localStorage:", roles.value);

function checkIsAdmin(roles) {
  const rolesArray = roles.split(",");
  return Array.isArray(rolesArray) && rolesArray.includes('admin')
}

const isAdmin = checkIsAdmin(roles.value);

console.log("Roles from localStorage:", isAdmin);


// Fonction de déconnexion
function logout() {
  localStorage.removeItem('token');
  isLoggedIn.value = false;
}
</script>

<template>
  <div class="page-container">

    <Navbar /> 

    <div class="spacer"></div>

    <div class="content">

      <aside class="sidebar">
        <h3 class="sidebar-title">Utilisateurs (102)</h3>
        <ul>
          <li><i class="fas fa-arrow-right"></i> Donnateurs (35)</li>
          <li><i class="fas fa-arrow-right"></i> Demandeurs (12)</li>
        </ul>

        <h3 class="sidebar-title sidebar-section">Catégories (25)</h3>
        <ul>
          <li>Bureau</li>
          <li>Appareil électroniques</li>
          <li>Meubles</li>
          <li><a href="http://">Toutes les catégories ...</a></li>
        </ul>
        
        <h3 class="sidebar-title sidebar-section">Autre</h3>
        <ul>
          <li>
            <i class="fas fa-question-circle"></i> Aide
          </li>
          <li v-if="!isLoggedIn">
            <i class="fas fa-sign-in-alt"></i> <a href="/login">Connexion</a>
          </li>
          <li v-if="!isLoggedIn">
            <i class="fas fa-user-plus"></i> <a href="/register">Ouvrir un compte</a>
          </li>
          <li v-else>
            <i class="fas fa-sign-out-alt"></i> <a href="" @click.prevent="logout">Déconnexion</a>
          </li>
        </ul>

        <!-- ADMIN -->
        <div v-if="isLoggedIn && isAdmin">
          <h3 class="sidebar-title sidebar-section">Administration</h3>
          <ul>
            <li><i class="fas fa-users"></i> Gestion des utilisateurs</li>
            <li><i class="fas fa-comments"></i> Gestion des commentaires</li>
            <li><i class="fas fa-bullhorn"></i> Gestion des annonces</li>
            <li><i class="fas fa-chart-line"></i> Statistiques globales</li>
            <li><i class="fas fa-credit-card"></i> Gestion des plans d’abonnement</li>
          </ul>
        </div>

      </aside>

      <!-- LISTING -->
      <main class="listing">

        <!-- BARRE DE RECHERCHE -->
        <header class="header-search">
          <div class="search-row">
            <input type="text" placeholder="Toutes les catégories..." />
          </div>

          <div class="search-row2">
            <input type="text" placeholder="Code postal" />
            <select class="distance-select">
              <option selected>Toutes les distances</option>
              <option>< 3 km</option>
              <option>< 5 km</option>
              <option>< 10 km</option>
              <option>< 20 km</option>
              <option>< 30 km</option>
              <option>< 40 km</option>
              <option>< 50 km</option>
            </select>
            <button>Rechercher</button>
          </div>
        </header>

        <h2 class="section-title">Pour vous</h2>

        <div class="grid">
          <div class="card" v-for="n in 12" :key="n">
            <img src="../image/0ddc5b14a02b31812e9a41ea30dad92c.jpg" />
            <p class="card-title">Bureau de réunion</p>
            <p class="card-price">À donner</p>
            <button class="voir-plus-btn">Voir plus</button>
          </div>
        </div>

      </main>
    </div>
  </div>
</template>

<style>
ul {
  list-style-type: none;
  padding: 0;
}

li {
  margin: 10px 0;
  font-size: 17px;
}

a {
  text-decoration: none;
  color: #007bff; /* Couleur par défaut des liens */
}

i {
  margin-right: 8px; /* Espacement entre l'icône et le texte */
  font-size: 20px;
}

.page-container {
  background: #f3f3f3;
  min-height: 100vh;
  padding: 20px;
  font-family: Arial, sans-serif;
}

.spacer {
  height: 40px;
}

/* LAYOUT */
.content {
  display: flex;
  gap: 20px;
}

/* SIDEBAR */
.sidebar {
  width: 250px;
  background: white;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 2px 5px rgba(0,0,0,.1);
}

.sidebar-title {
  font-weight: bold;
  font-size: 20px;
}

.sidebar-section {
  margin-top: 25px;
}

.sidebar ul {
  list-style: none;
  padding: 0;
}

.sidebar li {
  padding: 6px 0;
  color: #555;
}

/* SEARCH */
.header-search {
  max-width: 900px;
  margin-bottom: 25px;
}

.search-row input,
.search-row2 input {
  width: 100%;
  padding: 10px;
  border: 1px solid #bbb;
  border-radius: 6px;
}

.search-row2 {
  display: flex;
  gap: 10px;
  margin-top: 10px;
}

.search-row2 button {
  background: #0054a6;
  color: white;
  border: none;
  padding: 10px 18px;
  border-radius: 6px;
}

/* LISTING */
.listing {
  flex: 1;
}

.section-title {
  font-size: 22px;
  font-weight: bold;
  margin-bottom: 15px;
}

/* GRID */
.grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

/* CARD */
.card {
  background: white;
  border-radius: 10px;
  padding: 10px;
  box-shadow: 0 2px 5px rgba(0,0,0,.1);
}

.card img {
  width: 100%;
  height: 160px;
  object-fit: cover;
  border-radius: 8px;
}

.card-title {
  margin-top: 8px;
  font-weight: 600;
}

.card-price {
  color: #F1B800;
  font-weight: bold;
}

.voir-plus-btn {
  background: transparent;
  padding: 10px 24px;
  border-radius: 10px;
  border: none;
  cursor: pointer;
  box-shadow: 0 4px 10px rgba(241,184,0,.6);
  margin-top: 8px;
}

/* RESPONSIVE */
@media (max-width: 900px) {
  .content {
    flex-direction: column;
  }

  .sidebar {
    width: 100%;
  }

  .grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 600px) {
  .search-row2 {
    flex-direction: column;
  }

  .grid {
    grid-template-columns: 1fr;
  }

  .page-container {
    padding: 10px;
  }
}
</style>
