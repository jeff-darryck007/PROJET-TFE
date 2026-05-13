<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import Navbar from "./Navbar.vue";
import Footer from "./Footer.vue";
import { fetchAnouncements, fetchPendingRecoveries, validateRecovery } from "../controller/controllerAnouncement.js";
import { toggleFavorite, fetchLikedIds } from "../controller/controllerFavorite.js";
import { fetchReportedComments, deleteAdminComment } from "../controller/controllerComment.js";
import { fetchUserProfile, fetchAdminUsers, fetchAdminStats, banUser, unbanUser } from "../controller/controllerLogin.js";
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

const pendingRecoveries = ref([]);
const validating        = ref(null);
const validateError     = ref("");
const userCredit        = ref(null);

const adminView         = ref("");
const adminUsers        = ref([]);
const loadingAdmin      = ref(false);

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
      const [ids, pending, profile] = await Promise.all([
        fetchLikedIds(token),
        fetchPendingRecoveries(token),
        fetchUserProfile(token),
      ]);
      likedIds.value = new Set(ids);
      pendingRecoveries.value = pending.data ?? [];
      userCredit.value = profile.credit ?? 0;
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

async function openAdminStats() {
  adminView.value = "stats";
  if (adminStats.value) return;
  loadingStats.value = true;
  try {
    adminStats.value = await fetchAdminStats(token);
  } catch { /* silencieux */ }
  finally { loadingStats.value = false; }
}

async function openAdminComments() {
  adminView.value = "comments";
  if (reportedComments.value.length > 0) return;
  loadingComments.value = true;
  try {
    const data = await fetchReportedComments(token);
    reportedComments.value = data.comments ?? [];
  } catch { /* silencieux */ }
  finally { loadingComments.value = false; }
}

async function handleDeleteComment(id) {
  deletingCommentId.value = id;
  commentAdminError.value = "";
  try {
    await deleteAdminComment(id, token);
    reportedComments.value = reportedComments.value.filter(c => c.id !== id);
  } catch (e) {
    commentAdminError.value = e.message;
    setTimeout(() => { commentAdminError.value = ""; }, 4000);
  } finally {
    deletingCommentId.value = null;
  }
}

async function openAdminUsers() {
  adminView.value = "users";
  if (adminUsers.value.length > 0) return;
  loadingAdmin.value = true;
  try {
    const data = await fetchAdminUsers(token);
    adminUsers.value = data.users ?? [];
  } catch { /* silencieux */ }
  finally { loadingAdmin.value = false; }
}

const STATUS_USER   = { 1: "Actif", 0: "Banni", 2: "Supprimé", 3: "En vérification" };
const banActionId   = ref(null);
const banError      = ref("");

const adminStats         = ref(null);
const loadingStats       = ref(false);
const reportedComments   = ref([]);
const loadingComments    = ref(false);
const deletingCommentId  = ref(null);
const commentAdminError  = ref("");

async function handleBan(id) {
  if (banActionId.value === id) return;
  banActionId.value = id;
  banError.value = "";
  try {
    const res = await banUser(id, token);
    const u = adminUsers.value.find(u => u.id === id);
    if (u) u.status = res.status;
  } catch (e) {
    banError.value = e.message;
    setTimeout(() => { banError.value = ""; }, 4000);
  } finally {
    banActionId.value = null;
  }
}

async function handleUnban(id) {
  banActionId.value = id;
  banError.value = "";
  try {
    const res = await unbanUser(id, token);
    const u = adminUsers.value.find(u => u.id === id);
    if (u) u.status = res.status;
  } catch (e) {
    banError.value = e.message;
    setTimeout(() => { banError.value = ""; }, 4000);
  } finally {
    banActionId.value = null;
  }
}

async function handleValidate(id) {
  validating.value = id;
  validateError.value = "";
  try {
    const res = await validateRecovery(id, token);
    pendingRecoveries.value = pendingRecoveries.value.filter(r => r.id !== id);
    if (res.newCredit !== undefined) userCredit.value = res.newCredit;
    const a = anouncements.value.find(a => a.id === id);
    if (a) a.status = 3;
  } catch (e) {
    validateError.value = e.message;
    setTimeout(() => { validateError.value = ""; }, 4000);
  } finally {
    validating.value = null;
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
            <li v-if="isLoggedIn && userCredit !== null" class="action-item action-item--credit">
              <i class="fas fa-coins"></i>
              <span>Crédits</span>
              <span class="credit-badge">{{ userCredit }}</span>
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
            <li class="action-item" :class="{ 'admin-active': adminView === 'users' }" @click="openAdminUsers">
              <i class="fas fa-users-cog"></i> Utilisateurs
            </li>
            <li class="action-item" :class="{ 'admin-active': adminView === '' }" @click="adminView = ''">
              <i class="fas fa-bullhorn"></i> Annonces
            </li>
            <li class="action-item" :class="{ 'admin-active': adminView === 'comments' }" @click="openAdminComments">
              <i class="fas fa-comments"></i> Commentaires
            </li>
            <li class="action-item" :class="{ 'admin-active': adminView === 'stats' }" @click="openAdminStats">
              <i class="fas fa-chart-line"></i> Statistiques
            </li>
          </ul>
        </div>

      </aside>

      <!-- ===== CONTENU PRINCIPAL ===== -->
      <main class="listing">

        <!-- RÉCUPÉRATIONS EN ATTENTE -->
        <div v-if="pendingRecoveries.length > 0" class="pending-block">
          <h3 class="pending-title">
            <i class="fas fa-handshake"></i>
            Récupération(s) en attente de votre confirmation
          </h3>
          <div v-if="validateError" class="pending-error">
            <i class="fas fa-exclamation-circle"></i> {{ validateError }}
          </div>
          <div class="pending-list">
            <div v-for="r in pendingRecoveries" :key="r.id" class="pending-card">
              <div class="pending-img">
                <img v-if="r.pictures && r.pictures.length" :src="`${URL_FOLDER_ANOUNCEMENT}/${r.pictures[0]}`" :alt="r.title" />
                <div v-else class="pending-no-img"><i class="fas fa-image"></i></div>
              </div>
              <div class="pending-info">
                <p class="pending-name">{{ r.title }}</p>
                <p class="pending-donor"><i class="fas fa-user"></i> Proposé par {{ r.donorName }}</p>
                <p class="pending-adress"><i class="fas fa-map-marker-alt"></i> {{ r.adress }}</p>
              </div>
              <div class="pending-actions">
                <p class="pending-msg">Avez-vous bien récupéré cet objet ?</p>
                <button
                  class="btn-validate"
                  :disabled="validating === r.id"
                  @click="handleValidate(r.id)"
                >
                  <i :class="validating === r.id ? 'fas fa-spinner fa-spin' : 'fas fa-check-circle'"></i>
                  {{ validating === r.id ? 'Validation...' : 'Oui, je confirme la récupération' }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- BARRE DE RECHERCHE (cachée en vue admin) -->
        <div class="search-block" v-if="!adminView">
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

        <!-- VUE ADMIN : LISTE DES UTILISATEURS -->
        <template v-if="isAdmin && adminView === 'users'">
          <div class="admin-panel">
            <div class="admin-panel-header">
              <h2><i class="fas fa-users-cog"></i> Utilisateurs — classés par crédits</h2>
              <button class="btn-close-admin" @click="adminView = ''">
                <i class="fas fa-times"></i> Fermer
              </button>
            </div>

            <div v-if="loadingAdmin" class="state-box">
              <i class="fas fa-spinner fa-spin"></i>
              <p>Chargement des utilisateurs...</p>
            </div>

            <template v-else>
              <div v-if="banError" class="ban-error">
                <i class="fas fa-exclamation-circle"></i> {{ banError }}
              </div>

              <div class="admin-users-table-wrap">
                <table class="admin-users-table">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Nom</th>
                      <th>Email</th>
                      <th>Rôle(s)</th>
                      <th>Statut</th>
                      <th>Inscrit le</th>
                      <th><i class="fas fa-coins"></i> Crédits</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(u, i) in adminUsers" :key="u.id" :class="{ 'row-top': i === 0, 'row-banned': u.status === 0 }">
                      <td class="td-rank">
                        <span v-if="i === 0" class="medal gold"><i class="fas fa-trophy"></i></span>
                        <span v-else-if="i === 1" class="medal silver"><i class="fas fa-medal"></i></span>
                        <span v-else-if="i === 2" class="medal bronze"><i class="fas fa-medal"></i></span>
                        <span v-else class="rank-num">{{ i + 1 }}</span>
                      </td>
                      <td class="td-name">
                        <div class="user-initials">{{ u.name.split(' ').map(n => n[0]).join('').substring(0,2).toUpperCase() }}</div>
                        {{ u.name }}
                      </td>
                      <td>{{ u.email }}</td>
                      <td>
                        <span v-for="r in u.roles" :key="r" class="role-chip">{{ r }}</span>
                      </td>
                      <td>
                        <span class="status-chip" :class="`status-${u.status}`">
                          {{ STATUS_USER[u.status] ?? '—' }}
                        </span>
                      </td>
                      <td>{{ u.createAt ?? '—' }}</td>
                      <td class="td-credit">
                        <span class="credit-val">
                          <i class="fas fa-coins"></i> {{ u.credit }}
                        </span>
                      </td>
                      <td class="td-action">
                        <template v-if="!u.roles.includes('admin')">
                          <button
                            v-if="u.status !== 0"
                            class="btn-ban"
                            :disabled="banActionId === u.id"
                            @click="handleBan(u.id)"
                          >
                            <i :class="banActionId === u.id ? 'fas fa-spinner fa-spin' : 'fas fa-ban'"></i>
                            Bannir
                          </button>
                          <button
                            v-else
                            class="btn-unban"
                            :disabled="banActionId === u.id"
                            @click="handleUnban(u.id)"
                          >
                            <i :class="banActionId === u.id ? 'fas fa-spinner fa-spin' : 'fas fa-check-circle'"></i>
                            Débannir
                          </button>
                        </template>
                        <span v-else class="td-admin-label"><i class="fas fa-shield-alt"></i> Admin</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </template>
          </div>
        </template>

        <!-- VUE ADMIN : COMMENTAIRES SIGNALÉS -->
        <template v-if="isAdmin && adminView === 'comments'">
          <div class="admin-panel">
            <div class="admin-panel-header">
              <h2><i class="fas fa-flag"></i> Commentaires signalés (≥ 3 signalements)</h2>
              <button class="btn-close-admin" @click="adminView = ''">
                <i class="fas fa-times"></i> Fermer
              </button>
            </div>

            <div v-if="loadingComments" class="state-box">
              <i class="fas fa-spinner fa-spin"></i>
              <p>Chargement...</p>
            </div>

            <template v-else>
              <div v-if="commentAdminError" class="ban-error">
                <i class="fas fa-exclamation-circle"></i> {{ commentAdminError }}
              </div>

              <div v-if="reportedComments.length === 0" class="state-box">
                <i class="fas fa-check-circle" style="color:#16a34a"></i>
                <p>Aucun commentaire signalé 3 fois ou plus.</p>
              </div>

              <div v-else class="reported-list">
                <div v-for="c in reportedComments" :key="c.id" class="reported-card">
                  <div class="reported-meta">
                    <span class="reported-badge">
                      <i class="fas fa-flag"></i> {{ c.reportCount }} signalement{{ c.reportCount > 1 ? 's' : '' }}
                    </span>
                    <span class="reported-author">
                      <i class="fas fa-user"></i> {{ c.userName }}
                    </span>
                    <span class="reported-annonce">
                      <i class="fas fa-bullhorn"></i>
                      <router-link :to="`/annonce/${c.anouncementId}`">{{ c.anouncement }}</router-link>
                    </span>
                    <span class="reported-date">{{ c.date }}</span>
                  </div>
                  <p class="reported-text">{{ c.contenue }}</p>
                  <div class="reported-actions">
                    <button
                      class="btn-delete-comment"
                      :disabled="deletingCommentId === c.id"
                      @click="handleDeleteComment(c.id)"
                    >
                      <i :class="deletingCommentId === c.id ? 'fas fa-spinner fa-spin' : 'fas fa-trash-alt'"></i>
                      {{ deletingCommentId === c.id ? 'Suppression...' : 'Supprimer le commentaire' }}
                    </button>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </template>

        <!-- VUE ADMIN : STATISTIQUES -->
        <template v-if="isAdmin && adminView === 'stats'">
          <div class="admin-panel">
            <div class="admin-panel-header">
              <h2><i class="fas fa-chart-line"></i> Statistiques de la plateforme</h2>
              <button class="btn-close-admin" @click="adminView = ''">
                <i class="fas fa-times"></i> Fermer
              </button>
            </div>

            <div v-if="loadingStats" class="state-box">
              <i class="fas fa-spinner fa-spin"></i>
              <p>Chargement des statistiques...</p>
            </div>

            <template v-else-if="adminStats">

              <!-- KPI CARDS -->
              <div class="stats-grid">
                <div class="stat-kpi blue">
                  <i class="fas fa-users"></i>
                  <div class="kpi-body">
                    <p class="kpi-val">{{ adminStats.users.total }}</p>
                    <p class="kpi-label">Utilisateurs</p>
                    <p class="kpi-sub">{{ adminStats.users.active }} actifs · {{ adminStats.users.banned }} bannis</p>
                  </div>
                </div>
                <div class="stat-kpi green">
                  <i class="fas fa-bullhorn"></i>
                  <div class="kpi-body">
                    <p class="kpi-val">{{ adminStats.anouncements.total }}</p>
                    <p class="kpi-label">Annonces</p>
                    <p class="kpi-sub">{{ adminStats.anouncements.donne }} données</p>
                  </div>
                </div>
                <div class="stat-kpi yellow">
                  <i class="fas fa-coins"></i>
                  <div class="kpi-body">
                    <p class="kpi-val">{{ adminStats.users.credits }}</p>
                    <p class="kpi-label">Crédits distribués</p>
                    <p class="kpi-sub">{{ adminStats.anouncements.donne }} dons validés</p>
                  </div>
                </div>
                <div class="stat-kpi red">
                  <i class="fas fa-flag"></i>
                  <div class="kpi-body">
                    <p class="kpi-val">{{ adminStats.comments.reported }}</p>
                    <p class="kpi-label">Commentaires signalés</p>
                    <p class="kpi-sub">{{ adminStats.comments.total }} commentaires au total</p>
                  </div>
                </div>
              </div>

              <div class="stats-row">

                <!-- STATUTS ANNONCES -->
                <div class="stats-card">
                  <h4><i class="fas fa-layer-group"></i> Statuts des annonces</h4>
                  <div class="bar-item">
                    <div class="bar-label">
                      <span>Disponible</span>
                      <span class="bar-count">{{ adminStats.anouncements.disponible }}</span>
                    </div>
                    <div class="bar-track">
                      <div class="bar-fill green"
                        :style="{ width: adminStats.anouncements.total ? (adminStats.anouncements.disponible / adminStats.anouncements.total * 100) + '%' : '0%' }">
                      </div>
                    </div>
                  </div>
                  <div class="bar-item">
                    <div class="bar-label">
                      <span>Réservé</span>
                      <span class="bar-count">{{ adminStats.anouncements.reserve }}</span>
                    </div>
                    <div class="bar-track">
                      <div class="bar-fill yellow"
                        :style="{ width: adminStats.anouncements.total ? (adminStats.anouncements.reserve / adminStats.anouncements.total * 100) + '%' : '0%' }">
                      </div>
                    </div>
                  </div>
                  <div class="bar-item">
                    <div class="bar-label">
                      <span>Donné</span>
                      <span class="bar-count">{{ adminStats.anouncements.donne }}</span>
                    </div>
                    <div class="bar-track">
                      <div class="bar-fill blue"
                        :style="{ width: adminStats.anouncements.total ? (adminStats.anouncements.donne / adminStats.anouncements.total * 100) + '%' : '0%' }">
                      </div>
                    </div>
                  </div>
                </div>

                <!-- TOP DONNEURS -->
                <div class="stats-card">
                  <h4><i class="fas fa-trophy"></i> Top 5 donneurs</h4>
                  <div v-if="adminStats.topDonors.length === 0" class="stats-empty">Aucun don validé.</div>
                  <div v-for="(d, i) in adminStats.topDonors" :key="i" class="donor-rank-item">
                    <span class="donor-rank-pos" :class="['pos-' + (i+1)]">{{ i + 1 }}</span>
                    <span class="donor-rank-name">{{ d.name }}</span>
                    <span class="donor-rank-total">{{ d.total }} don{{ d.total > 1 ? 's' : '' }}</span>
                    <span class="donor-rank-credit"><i class="fas fa-coins"></i> {{ d.credit }}</span>
                  </div>
                </div>

                <!-- CATÉGORIES -->
                <div class="stats-card">
                  <h4><i class="fas fa-th-list"></i> Catégories populaires</h4>
                  <div v-if="Object.keys(adminStats.categories).length === 0" class="stats-empty">Aucune donnée.</div>
                  <div v-for="(count, cat) in adminStats.categories" :key="cat" class="bar-item">
                    <div class="bar-label">
                      <span>{{ cat }}</span>
                      <span class="bar-count">{{ count }}</span>
                    </div>
                    <div class="bar-track">
                      <div class="bar-fill purple"
                        :style="{ width: adminStats.anouncements.total ? (count / adminStats.anouncements.total * 100) + '%' : '0%' }">
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </template>
          </div>
        </template>

        <template v-if="!adminView">

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

        </template><!-- fin v-if="!adminView" -->

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

/* === RÉCUPÉRATIONS EN ATTENTE === */
.pending-block {
  background: white;
  border-radius: 14px;
  padding: 20px 22px;
  margin-bottom: 24px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  border-left: 4px solid #16a34a;
}

.pending-title {
  font-size: 16px;
  font-weight: 700;
  color: #16a34a;
  margin: 0 0 16px 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.pending-error {
  background: #fee2e2;
  color: #dc2626;
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 13px;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.pending-list { display: flex; flex-direction: column; gap: 14px; }

.pending-card {
  display: flex;
  gap: 16px;
  align-items: center;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 12px;
  padding: 14px 16px;
  flex-wrap: wrap;
}

.pending-img {
  width: 80px;
  height: 68px;
  border-radius: 8px;
  overflow: hidden;
  flex-shrink: 0;
  background: #d1fae5;
}
.pending-img img { width: 100%; height: 100%; object-fit: cover; }
.pending-no-img {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6ee7b7;
  font-size: 24px;
}

.pending-info { flex: 1; min-width: 140px; }
.pending-name { font-size: 15px; font-weight: 700; color: #166534; margin: 0 0 4px; }
.pending-donor, .pending-adress {
  font-size: 12px;
  color: #4ade80;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 5px;
}
.pending-donor i, .pending-adress i { color: #16a34a; }

.pending-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; }
.pending-msg { font-size: 13px; color: #166534; font-weight: 600; margin: 0; }

.btn-validate {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  background: #16a34a;
  color: white;
  border: none;
  border-radius: 9px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  transition: background 0.2s;
  white-space: nowrap;
}
.btn-validate:hover:not(:disabled) { background: #15803d; }
.btn-validate:disabled { background: #86efac; cursor: not-allowed; }

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
.action-item--credit { justify-content: space-between; cursor: default; }
.action-item--credit i { color: #d97706; }
.action-item--credit:hover { background: #fffbeb; color: #92400e; }

.credit-badge {
  background: #fef3c7;
  color: #d97706;
  font-size: 12px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 20px;
}

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

/* === ADMIN PANEL === */
.admin-active { background: #eef4ff !important; color: #0054a6 !important; font-weight: 700; }

.admin-panel {
  background: white;
  border-radius: 14px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.07);
  margin-bottom: 24px;
}

.admin-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 12px;
}

.admin-panel-header h2 {
  font-size: 18px;
  font-weight: 700;
  color: #0054a6;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-close-admin {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  background: #f1f5f9;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  color: #555;
  cursor: pointer;
  font-family: inherit;
  transition: background 0.2s;
}
.btn-close-admin:hover { background: #e2e8f0; }

.admin-users-table-wrap { overflow-x: auto; }

.admin-users-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.admin-users-table thead tr {
  background: #f8faff;
  border-bottom: 2px solid #e8ecf0;
}

.admin-users-table th {
  padding: 12px 14px;
  text-align: left;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #888;
  white-space: nowrap;
}

.admin-users-table td {
  padding: 12px 14px;
  border-bottom: 1px solid #f0f0f0;
  vertical-align: middle;
  color: #333;
}

.admin-users-table tbody tr:hover { background: #fafbff; }
.admin-users-table tbody tr:last-child td { border-bottom: none; }
.row-top td { background: #fffbeb !important; }

.td-rank { width: 48px; text-align: center; }

.medal { font-size: 18px; }
.medal.gold  { color: #d97706; }
.medal.silver { color: #9ca3af; }
.medal.bronze { color: #b45309; }
.rank-num { font-size: 13px; color: #aaa; font-weight: 600; }

.td-name {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
  color: #222;
  white-space: nowrap;
}

.user-initials {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: linear-gradient(135deg, #0054a6, #1a72cc);
  color: white;
  font-size: 12px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.role-chip {
  display: inline-block;
  background: #eef4ff;
  color: #0054a6;
  font-size: 11px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 20px;
  margin-right: 4px;
  text-transform: capitalize;
}

.status-chip {
  display: inline-block;
  font-size: 11px;
  font-weight: 700;
  padding: 3px 9px;
  border-radius: 20px;
}
.status-chip.status-1 { background: #dcfce7; color: #16a34a; }
.status-chip.status-0 { background: #fee2e2; color: #dc2626; }
.status-chip.status-2 { background: #f3f4f6; color: #6b7280; }
.status-chip.status-3 { background: #fef9c3; color: #ca8a04; }

.td-credit { text-align: right; }
.credit-val {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-weight: 700;
  font-size: 15px;
  color: #d97706;
}
.credit-val i { color: #f59e0b; }

.ban-error {
  background: #fee2e2;
  color: #dc2626;
  border-radius: 8px;
  padding: 10px 16px;
  font-size: 13px;
  margin-bottom: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.row-banned td { opacity: 0.6; background: #fef2f2 !important; }

.td-action { white-space: nowrap; }

.btn-ban {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: #fee2e2;
  color: #dc2626;
  border: 1px solid #fca5a5;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.2s;
}
.btn-ban:hover:not(:disabled) { background: #dc2626; color: white; }
.btn-ban:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-unban {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: #dcfce7;
  color: #16a34a;
  border: 1px solid #86efac;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.2s;
}
.btn-unban:hover:not(:disabled) { background: #16a34a; color: white; }
.btn-unban:disabled { opacity: 0.6; cursor: not-allowed; }

/* ===== STATISTIQUES ===== */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.stat-kpi {
  border-radius: 12px;
  padding: 18px 20px;
  display: flex;
  align-items: flex-start;
  gap: 16px;
}
.stat-kpi i { font-size: 28px; flex-shrink: 0; margin-top: 2px; }
.stat-kpi.blue  { background: #eef4ff; }
.stat-kpi.blue i { color: #0054a6; }
.stat-kpi.green { background: #f0fdf4; }
.stat-kpi.green i { color: #16a34a; }
.stat-kpi.yellow { background: #fffbeb; }
.stat-kpi.yellow i { color: #d97706; }
.stat-kpi.red   { background: #fff5f5; }
.stat-kpi.red i { color: #dc2626; }

.kpi-val {
  font-size: 28px;
  font-weight: 800;
  color: #1a1a2e;
  margin: 0;
  line-height: 1;
}
.kpi-label {
  font-size: 13px;
  font-weight: 700;
  color: #555;
  margin: 4px 0 2px;
}
.kpi-sub { font-size: 11px; color: #aaa; margin: 0; }

.stats-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.stats-card {
  background: #fafbff;
  border: 1px solid #e8ecf0;
  border-radius: 12px;
  padding: 18px 20px;
}
.stats-card h4 {
  font-size: 13px;
  font-weight: 700;
  color: #555;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 0 0 16px 0;
  display: flex;
  align-items: center;
  gap: 8px;
}
.stats-card h4 i { color: #0054a6; }

.bar-item { margin-bottom: 12px; }
.bar-item:last-child { margin-bottom: 0; }

.bar-label {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  color: #444;
  margin-bottom: 5px;
  font-weight: 500;
}
.bar-count { font-weight: 700; color: #0054a6; }

.bar-track {
  height: 8px;
  background: #e8ecf0;
  border-radius: 20px;
  overflow: hidden;
}
.bar-fill {
  height: 100%;
  border-radius: 20px;
  transition: width 0.6s ease;
}
.bar-fill.green  { background: #16a34a; }
.bar-fill.yellow { background: #d97706; }
.bar-fill.blue   { background: #0054a6; }
.bar-fill.purple { background: #7c3aed; }

.stats-empty { font-size: 13px; color: #bbb; text-align: center; padding: 10px 0; }

.donor-rank-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
  font-size: 13px;
}
.donor-rank-item:last-child { border-bottom: none; }

.donor-rank-pos {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 800;
  flex-shrink: 0;
  background: #e8ecf0;
  color: #666;
}
.donor-rank-pos.pos-1 { background: #fef3c7; color: #d97706; }
.donor-rank-pos.pos-2 { background: #f3f4f6; color: #6b7280; }
.donor-rank-pos.pos-3 { background: #fef3c7; color: #b45309; }

.donor-rank-name { flex: 1; font-weight: 600; color: #222; }
.donor-rank-total { color: #16a34a; font-weight: 700; white-space: nowrap; }
.donor-rank-credit {
  display: flex;
  align-items: center;
  gap: 4px;
  color: #d97706;
  font-weight: 700;
  font-size: 12px;
  white-space: nowrap;
}
.donor-rank-credit i { color: #f59e0b; font-size: 11px; }

@media (max-width: 1100px) {
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .stats-row  { grid-template-columns: 1fr; }
}

/* COMMENTAIRES SIGNALÉS */
.reported-list { display: flex; flex-direction: column; gap: 14px; }

.reported-card {
  border: 1px solid #fca5a5;
  border-left: 4px solid #dc2626;
  border-radius: 10px;
  padding: 16px 18px;
  background: #fff5f5;
}

.reported-meta {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 10px;
  font-size: 12px;
  color: #888;
}

.reported-badge {
  background: #dc2626;
  color: white;
  font-size: 11px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 20px;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.reported-author, .reported-annonce, .reported-date {
  display: inline-flex;
  align-items: center;
  gap: 5px;
}
.reported-annonce a { color: #0054a6; text-decoration: none; font-weight: 600; }
.reported-annonce a:hover { text-decoration: underline; }

.reported-text {
  font-size: 14px;
  color: #333;
  margin: 0 0 12px 0;
  line-height: 1.6;
  background: white;
  border-radius: 8px;
  padding: 10px 14px;
  border: 1px solid #fecaca;
}

.reported-actions { display: flex; justify-content: flex-end; }

.btn-delete-comment {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 8px 16px;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  transition: background 0.2s;
}
.btn-delete-comment:hover:not(:disabled) { background: #b91c1c; }
.btn-delete-comment:disabled { opacity: 0.6; cursor: not-allowed; }

.td-admin-label {
  font-size: 12px;
  color: #0054a6;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

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
