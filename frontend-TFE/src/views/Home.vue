<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import Navbar from "./Navbar.vue";
import Footer from "./Footer.vue";
import { fetchAnouncements } from "../controller/controllerAnouncement.js";
import { toggleFavorite, fetchLikedIds } from "../controller/controllerFavorite.js";
import { URL_FOLDER_ANOUNCEMENT } from "../server/config.js";
import "@fortawesome/fontawesome-free/css/all.css";

const router = useRouter();

const anouncements  = ref([]);
const loading       = ref(true);
const errorMessage  = ref("");
const favError      = ref("");
const search        = ref("");
const city          = ref("");
const postalCode    = ref("");
const selectedCategory = ref("");
const likedIds      = ref(new Set());

const token      = localStorage.getItem("token");
const isLoggedIn = ref(!!token);
const roles      = (localStorage.getItem("roles") || "").split(",").map(r => r.trim());
const isAdmin    = roles.includes("admin");

const STATUS_LABEL = { 1: "Disponible", 2: "Réservé", 3: "Donné" };

const CATEGORY_ICONS = {
  "Meubles":       "fas fa-couch",
  "Électronique":  "fas fa-laptop",
  "Vêtements":     "fas fa-tshirt",
  "Livres":        "fas fa-book",
  "Sport":         "fas fa-futbol",
  "Jouets":        "fas fa-gamepad",
  "Cuisine":       "fas fa-utensils",
  "Jardinage":     "fas fa-seedling",
  "Bureau":        "fas fa-chair",
  "Décoration":    "fas fa-paint-brush",
  "Informatique":  "fas fa-desktop",
};

onMounted(async () => {
  try {
    const data = await fetchAnouncements(token);
    anouncements.value = data.data ?? [];
  } catch {
    errorMessage.value = "Impossible de charger les annonces.";
  } finally {
    loading.value = false;
  }

  if (token) {
    try {
      const ids = await fetchLikedIds(token);
      likedIds.value = new Set(ids);
    } catch { /* silencieux */ }
  }
});

const categories = computed(() => {
  const set = new Set(anouncements.value.map(a => a.categorie).filter(Boolean));
  return [...set].sort();
});

const totalAnnonces = computed(() => anouncements.value.length);

const filtered = computed(() => {
  return anouncements.value.filter(a => {
    const q = search.value.toLowerCase();
    const adress = (a.adress ?? "").toLowerCase();
    const matchSearch =
      !q ||
      a.title.toLowerCase().includes(q) ||
      (a.description ?? "").toLowerCase().includes(q) ||
      adress.includes(q);
    const matchCat     = !selectedCategory.value || a.categorie === selectedCategory.value;
    const matchCity    = !city.value || adress.includes(city.value.toLowerCase());
    const matchPostal  = !postalCode.value || adress.includes(postalCode.value.toLowerCase());
    return matchSearch && matchCat && matchCity && matchPostal;
  });
});

function pictureUrl(pictures) {
  if (!pictures || pictures.length === 0) return null;
  return `${URL_FOLDER_ANOUNCEMENT}/${pictures[0]}`;
}

function cityFromAdress(adress) {
  if (!adress) return "";
  const parts = adress.split(",");
  return parts[parts.length - 1].replace(/^\d+\s*/, "").trim();
}

function formatDate(str) {
  if (!str) return "";
  const d = new Date(str);
  if (isNaN(d)) return str;
  return d.toLocaleDateString("fr-BE", { day: "2-digit", month: "short", year: "numeric" });
}

function categoryIcon(cat) {
  return CATEGORY_ICONS[cat] ?? "fas fa-tag";
}

function selectCategory(cat) {
  selectedCategory.value = selectedCategory.value === cat ? "" : cat;
}

function goToDetail(id) {
  if (!token) { router.push('/login'); return; }
  router.push({ name: 'annonce-detail', params: { id } });
}

function contactDonor(id) {
  if (!token) { router.push("/login"); return; }
  router.push({ name: "Message-View", query: { annonce: id } });
}

async function handleToggleFavorite(id) {
  if (!token) { router.push("/login"); return; }
  try {
    const res = await toggleFavorite(id, token);
    if (res.liked) {
      likedIds.value = new Set([...likedIds.value, id]);
    } else {
      const next = new Set(likedIds.value);
      next.delete(id);
      likedIds.value = next;
    }
  } catch (e) {
    const msg = e?.response?.data?.error ?? e?.message ?? "Erreur inconnue";
    favError.value = "Impossible d'ajouter aux favoris : " + msg;
    setTimeout(() => { favError.value = ""; }, 4000);
  }
}

function logout() {
  localStorage.removeItem("token");
  localStorage.removeItem("idUser");
  localStorage.removeItem("roles");
  isLoggedIn.value = false;
  router.push("/login");
}

function doSearch() { /* le computed se met à jour automatiquement */ }
</script>

<template>
  <div class="page">
    <Navbar />

    <div class="main-layout">

      <!-- ===== SIDEBAR ===== -->
      <aside class="sidebar">

        <!-- Statistiques communauté -->
        <div class="sidebar-section">
          <p class="section-label">Communauté</p>
          <div class="stat-item">
            <span class="stat-icon"><i class="fas fa-bullhorn"></i></span>
            <span class="stat-text">Annonces actives</span>
            <span class="stat-badge">{{ totalAnnonces }}</span>
          </div>
          <div class="stat-item">
            <span class="stat-icon"><i class="fas fa-hand-holding-heart"></i></span>
            <span class="stat-text">Donnateurs</span>
            <span class="stat-badge">{{ categories.length }}</span>
          </div>
          <div class="stat-item">
            <span class="stat-icon"><i class="fas fa-th-list"></i></span>
            <span class="stat-text">Catégories</span>
            <span class="stat-badge">{{ categories.length }}</span>
          </div>
        </div>

        <!-- Filtre catégories -->
        <div class="sidebar-section">
          <p class="section-label">Catégories</p>
          <ul class="cat-list">
            <li
              v-for="cat in categories"
              :key="cat"
              class="cat-item"
              :class="{ active: selectedCategory === cat }"
              @click="selectCategory(cat)"
            >
              <i :class="categoryIcon(cat)"></i> {{ cat }}
            </li>
            <li
              class="cat-item see-all"
              :class="{ active: !selectedCategory }"
              @click="selectedCategory = ''"
            >
              <i class="fas fa-th-list"></i> Toutes les catégories
            </li>
          </ul>
        </div>

        <!-- Compte -->
        <div class="sidebar-section">
          <p class="section-label">Compte</p>
          <ul class="action-list">
            <li v-if="!isLoggedIn" class="action-item">
              <router-link to="/login"><i class="fas fa-sign-in-alt"></i> Connexion</router-link>
            </li>
            <li v-if="!isLoggedIn" class="action-item">
              <router-link to="/register"><i class="fas fa-user-plus"></i> S'inscrire</router-link>
            </li>
            <li v-if="isLoggedIn" class="action-item">
              <router-link to="/ProfileView"><i class="fas fa-user-circle"></i> Mon profil</router-link>
            </li>
            <li v-if="isLoggedIn" class="action-item action-item--logout" @click="logout">
              <i class="fas fa-sign-out-alt"></i> Déconnexion
            </li>
          </ul>
        </div>

        <!-- Administration -->
        <div v-if="isLoggedIn && isAdmin" class="sidebar-section sidebar-section--admin">
          <p class="section-label">Administration</p>
          <ul class="action-list">
            <li class="action-item"><i class="fas fa-users-cog"></i> Utilisateurs</li>
            <li class="action-item"><i class="fas fa-comments"></i> Commentaires</li>
            <li class="action-item"><i class="fas fa-bullhorn"></i> Annonces</li>
            <li class="action-item"><i class="fas fa-chart-line"></i> Statistiques</li>
          </ul>
        </div>

      </aside>

      <!-- ===== CONTENU PRINCIPAL ===== -->
      <main class="listing">

        <!-- BARRE DE RECHERCHE -->
        <div class="search-block">
          <div class="search-main">
            <i class="fas fa-search search-icon"></i>
            <input
              v-model="search"
              type="text"
              placeholder="Rechercher un article, une catégorie..."
              class="search-input"
            />
          </div>
          <div class="search-secondary">
            <input v-model="city" type="text" placeholder="Ville" class="postal-input" />
            <input v-model="postalCode" type="text" placeholder="Code postal" class="country-select" maxlength="10" />
            <select v-model="selectedCategory" class="distance-select">
              <option value="">Toutes les catégories</option>
              <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
            </select>
            <button class="search-btn" @click="doSearch">
              <i class="fas fa-search"></i> Rechercher
            </button>
          </div>
        </div>

        <!-- TITRE + COMPTEUR -->
        <div class="listing-header">
          <h2 class="section-title">Pour vous</h2>
          <span v-if="!loading" class="result-count">
            {{ filtered.length }} annonce{{ filtered.length !== 1 ? "s" : "" }}
          </span>
        </div>

        <!-- TOAST ERREUR FAVORI -->
        <div v-if="favError" class="toast-error">
          <i class="fas fa-exclamation-circle"></i> {{ favError }}
        </div>

        <!-- LOADING -->
        <div v-if="loading" class="state-box">
          <i class="fas fa-spinner fa-spin"></i>
          <p>Chargement des annonces...</p>
        </div>

        <!-- ERREUR -->
        <div v-else-if="errorMessage" class="state-box error">
          <i class="fas fa-exclamation-circle"></i>
          <p>{{ errorMessage }}</p>
        </div>

        <!-- VIDE -->
        <div v-else-if="filtered.length === 0" class="state-box">
          <i class="fas fa-box-open"></i>
          <p>Aucune annonce ne correspond à votre recherche.</p>
        </div>

        <!-- GRILLE -->
        <div v-else class="grid">
          <div v-for="a in filtered" :key="a.id" class="card">

            <!-- IMAGE -->
            <div class="card-img-wrap">
              <img v-if="pictureUrl(a.pictures)" :src="pictureUrl(a.pictures)" :alt="a.title" />
              <div v-else class="no-img"><i class="fas fa-image"></i></div>
              <span class="status-badge" :class="`badge-${a.status}`">
                {{ STATUS_LABEL[a.status] ?? "Inconnu" }}
              </span>
              <button
                class="fav-btn"
                :class="{ active: likedIds.has(a.id) }"
                :title="likedIds.has(a.id) ? 'Retirer des favoris' : 'Ajouter aux favoris'"
                @click.stop="handleToggleFavorite(a.id)"
              >
                <i :class="likedIds.has(a.id) ? 'fas fa-heart' : 'far fa-heart'"></i>
              </button>
            </div>

            <!-- CORPS -->
            <div class="card-body">
              <span class="tag">{{ a.categorie }}</span>
              <p class="card-title">{{ a.title }}</p>
              <p class="card-location">
                <i class="fas fa-map-marker-alt"></i> {{ cityFromAdress(a.adress) }}
              </p>
              <p class="card-date">
                <i class="fas fa-calendar-alt"></i> {{ formatDate(a.createAt) }}
              </p>
              <p class="card-donor">
                <i class="fas fa-user"></i> {{ a.donorName }}
              </p>
              <button
                class="card-btn"
                @click="goToDetail(a.id)"
              >
                Voir l'article
              </button>
            </div>

          </div>
        </div>

      </main>
    </div>

    <Footer />
  </div>
</template>

<style scoped>
.page {
  background: #f4f6f9;
  min-height: 100vh;
  font-family: Arial, sans-serif;
}

/* === LAYOUT === */
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
.stat-item:last-child { border-bottom: none; }

.stat-icon { color: #0054a6; width: 20px; text-align: center; }
.stat-text { flex: 1; }

.stat-badge {
  background: #eef4ff;
  color: #0054a6;
  font-size: 12px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 20px;
}

.cat-list, .action-list {
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
.cat-item:hover, .cat-item.active { background: #f1f5f9; color: #0054a6; }
.cat-item.active { font-weight: 600; }
.cat-item.see-all { color: #0054a6; font-weight: 600; margin-top: 4px; }

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
.action-item:hover { background: #f1f5f9; color: #0054a6; }
.action-item--logout:hover { color: #e53e3e; background: #fff5f5; }

/* === LISTING === */
.listing { flex: 1; min-width: 0; }

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
.search-input:focus { border-color: #0054a6; }

.search-secondary {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.postal-input {
  flex: 1;
  min-width: 110px;
  padding: 10px 14px;
  border: 2px solid #e8ecf0;
  border-radius: 10px;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s;
  font-family: inherit;
}
.postal-input:focus { border-color: #0054a6; }

.country-select {
  flex: 1;
  min-width: 130px;
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
.country-select:focus { border-color: #0054a6; }

.distance-select {
  flex: 2;
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
.distance-select:focus { border-color: #0054a6; }

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
.search-btn:hover { background: #003f7d; }

/* TITRE SECTION */
.listing-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 18px;
}

.section-title {
  font-size: 20px;
  font-weight: 700;
  color: #222;
  margin: 0;
}

.result-count {
  font-size: 13px;
  color: #888;
}

/* TOAST */
.toast-error {
  background: #fee2e2;
  color: #dc2626;
  border: 1px solid #fca5a5;
  border-radius: 10px;
  padding: 12px 18px;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
}

/* ÉTATS */
.state-box {
  text-align: center;
  padding: 60px 20px;
  color: #888;
}
.state-box i { font-size: 40px; margin-bottom: 14px; display: block; }
.state-box p { font-size: 15px; }
.state-box.error { color: #dc2626; }

/* GRILLE */
.grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 18px;
}

/* CARTE */
.card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  transition: transform 0.2s, box-shadow 0.2s;
  display: flex;
  flex-direction: column;
}
.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 28px rgba(0, 0, 0, 0.12);
}

.card-img-wrap {
  position: relative;
  height: 160px;
  overflow: hidden;
  background: #eef2f7;
}
.card-img-wrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.35s;
}
.card:hover .card-img-wrap img { transform: scale(1.05); }

.no-img {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ccc;
  font-size: 36px;
}

.status-badge {
  position: absolute;
  top: 10px;
  left: 10px;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}
.badge-1 { background: #dcfce7; color: #16a34a; }
.badge-2 { background: #fef9c3; color: #ca8a04; }
.badge-3 { background: #fee2e2; color: #dc2626; }

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
.fav-btn:hover { color: #e53e3e; transform: scale(1.1); }
.fav-btn.active { color: #e53e3e; }

.card-body {
  padding: 12px 14px 14px;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.tag {
  display: inline-block;
  background: #eef4ff;
  color: #0054a6;
  font-size: 11px;
  font-weight: 700;
  padding: 2px 9px;
  border-radius: 20px;
  text-transform: uppercase;
  margin-bottom: 2px;
  align-self: flex-start;
}

.card-title {
  font-weight: 600;
  font-size: 15px;
  color: #222;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.card-location,
.card-date,
.card-donor {
  font-size: 12px;
  color: #888;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 5px;
}
.card-location i,
.card-date i,
.card-donor i { color: #0054a6; font-size: 11px; }

.card-btn {
  margin-top: auto;
  padding-top: 10px;
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
.card-btn:hover:not(:disabled) { background: #dca600; transform: translateY(-1px); }
.card-btn:disabled { background: #ccc; cursor: not-allowed; color: #fff; }

/* === RESPONSIVE === */
@media (max-width: 1100px) {
  .grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 900px) {
  .main-layout { flex-direction: column; }
  .sidebar { width: 100%; }
  .grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 600px) {
  .main-layout { padding: 16px; gap: 16px; }
  .search-secondary { flex-direction: column; }
  .grid { grid-template-columns: 1fr; }
}
</style>
