<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import Navbar from "../Navbar.vue";
import Footer from "../Footer.vue";
import "@fortawesome/fontawesome-free/css/all.css";
import { fetchUserProfile, updateProfile, changePassword } from "../../controller/controllerLogin.js";
import { fetchMyAnouncements, deleteAnouncement, fetchMyRecoveredArticles } from "../../controller/controllerAnouncement.js";
import { fetchNotifications, markAsRead, markAllAsRead, deleteNotification } from "../../controller/controllerNotification.js";
import { URL_FOLDER_ANOUNCEMENT } from "../../server/config.js";

const router = useRouter();

const activeMenu = ref("editProfile");

const user = ref(null);
const loading = ref(true);

const formProfile = ref({ name: "", surname: "" });
const formEmail   = ref({ email: "" });
const formPwd     = ref({ oldPassword: "", newPassword: "", confirmPassword: "" });

const successMsg = ref("");
const errorMsg   = ref("");
const saving     = ref(false);

const myAnouncements     = ref([]);
const loadingAnouncements = ref(false);
const confirmDelId        = ref(null);

const STATUS_LABEL = { 1: "Disponible", 2: "Réservé", 3: "Donné" };
const helpTab = ref("visiteur");

const recoveredArticles      = ref([]);
const loadingRecovered       = ref(false);
const recoveredThisMonth     = ref(0);
const canContact             = ref(true);

const notifications      = ref([]);
const loadingNotifs      = ref(false);
const unreadCount        = ref(0);
const deletingNotifId    = ref(null);

const NOTIF_ICONS = {
  "new-article": { icon: "fas fa-bullhorn",       color: "#0054a6" },
  "message":     { icon: "fas fa-envelope",        color: "#0054a6" },
  "reservation": { icon: "fas fa-handshake",       color: "#d97706" },
  "credit":      { icon: "fas fa-coins",           color: "#16a34a" },
  "report":      { icon: "fas fa-flag",            color: "#dc2626" },
};

function notifIcon(type) {
  return NOTIF_ICONS[type]?.icon ?? "fas fa-bell";
}
function notifColor(type) {
  return NOTIF_ICONS[type]?.color ?? "#888";
}

function flash(type, msg) {
  successMsg.value = "";
  errorMsg.value   = "";
  if (type === "success") successMsg.value = msg;
  else errorMsg.value = msg;
  setTimeout(() => { successMsg.value = ""; errorMsg.value = ""; }, 4000);
}

onMounted(async () => {
  const token = localStorage.getItem("token");
  if (!token) { router.push("/login"); return; }
  try {
    const data = await fetchUserProfile(token);
    user.value = data;
    formProfile.value.name    = data.name    ?? "";
    formProfile.value.surname = data.surname ?? "";
    formEmail.value.email     = data.email   ?? "";
  } catch {
    errorMsg.value = "Impossible de charger le profil.";
  } finally {
    loading.value = false;
  }

  // Charger le badge de notifications
  try {
    const nd = await fetchNotifications(token);
    unreadCount.value = nd.unreadCount ?? 0;
  } catch { /* silencieux */ }
});

async function loadNotifications() {
  loadingNotifs.value = true;
  try {
    const token = localStorage.getItem("token");
    const data = await fetchNotifications(token);
    notifications.value = data.notifications ?? [];
    unreadCount.value   = data.unreadCount   ?? 0;
  } catch { /* silencieux */ }
  finally { loadingNotifs.value = false; }
}

async function handleMarkRead(id) {
  const token = localStorage.getItem("token");
  try {
    await markAsRead(id, token);
    const n = notifications.value.find(n => n.id === id);
    if (n) { n.isRead = true; unreadCount.value = Math.max(0, unreadCount.value - 1); }
  } catch { /* silencieux */ }
}

async function handleMarkAllRead() {
  const token = localStorage.getItem("token");
  try {
    await markAllAsRead(token);
    notifications.value.forEach(n => { n.isRead = true; });
    unreadCount.value = 0;
  } catch { /* silencieux */ }
}

async function handleDeleteNotif(id) {
  const token = localStorage.getItem("token");
  deletingNotifId.value = id;
  try {
    await deleteNotification(id, token);
    const n = notifications.value.find(n => n.id === id);
    if (n && !n.isRead) unreadCount.value = Math.max(0, unreadCount.value - 1);
    notifications.value = notifications.value.filter(n => n.id !== id);
  } catch { /* silencieux */ }
  finally { deletingNotifId.value = null; }
}

function formatNotifDate(str) {
  if (!str) return "";
  const d = new Date(str);
  if (isNaN(d)) return str;
  const now = new Date();
  const diff = Math.floor((now - d) / 1000);
  if (diff < 60)   return "À l'instant";
  if (diff < 3600) return `il y a ${Math.floor(diff / 60)} min`;
  if (diff < 86400) return `il y a ${Math.floor(diff / 3600)} h`;
  return d.toLocaleDateString("fr-BE", { day: "2-digit", month: "short", year: "numeric" });
}

async function loadRecoveredArticles() {
  if (recoveredArticles.value.length > 0) return;
  loadingRecovered.value = true;
  try {
    const token = localStorage.getItem("token");
    const data = await fetchMyRecoveredArticles(token);
    recoveredArticles.value  = data.data ?? [];
    recoveredThisMonth.value = data.recoveredThisMonth ?? 0;
    canContact.value         = data.canContact ?? true;
  } catch { /* silencieux */ }
  finally { loadingRecovered.value = false; }
}

async function loadMyAnouncements() {
  if (myAnouncements.value.length > 0) return;
  const token = localStorage.getItem("token");
  loadingAnouncements.value = true;
  try {
    const data = await fetchMyAnouncements(token);
    myAnouncements.value = data.data ?? [];
  } catch { /* silencieux */ }
  finally { loadingAnouncements.value = false; }
}

async function handleDelete(id) {
  if (confirmDelId.value !== id) { confirmDelId.value = id; return; }
  const token = localStorage.getItem("token");
  try {
    await deleteAnouncement(id, token);
    myAnouncements.value = myAnouncements.value.filter(a => a.id !== id);
    confirmDelId.value = null;
  } catch (e) {
    flash("error", e.message || "Erreur lors de la suppression.");
    confirmDelId.value = null;
  }
}

function pictureUrl(pictures) {
  if (!pictures || pictures.length === 0) return null;
  return `${URL_FOLDER_ANOUNCEMENT}/${pictures[0]}`;
}

function formatDate(str) {
  if (!str) return "";
  const d = new Date(str);
  return isNaN(d) ? str : d.toLocaleDateString("fr-BE", { day: "2-digit", month: "short", year: "numeric" });
}

async function submitProfile() {
  const token = localStorage.getItem("token");
  if (!formProfile.value.name || !formProfile.value.surname) {
    flash("error", "Le nom et le prénom sont obligatoires.");
    return;
  }
  saving.value = true;
  try {
    const res = await updateProfile(formProfile.value, token);
    user.value.name    = res.name;
    user.value.surname = res.surname;
    flash("success", "Profil mis à jour avec succès.");
  } catch (e) {
    flash("error", e.message);
  } finally {
    saving.value = false;
  }
}

async function submitEmail() {
  const token = localStorage.getItem("token");
  if (!formEmail.value.email) {
    flash("error", "L'adresse e-mail est obligatoire.");
    return;
  }
  saving.value = true;
  try {
    const res = await updateProfile({ email: formEmail.value.email }, token);
    user.value.email = res.email;
    flash("success", "Adresse e-mail mise à jour avec succès.");
  } catch (e) {
    flash("error", e.message);
  } finally {
    saving.value = false;
  }
}

async function submitPassword() {
  if (!formPwd.value.oldPassword || !formPwd.value.newPassword) {
    flash("error", "Veuillez remplir tous les champs.");
    return;
  }
  if (formPwd.value.newPassword !== formPwd.value.confirmPassword) {
    flash("error", "Les nouveaux mots de passe ne correspondent pas.");
    return;
  }
  const token = localStorage.getItem("token");
  saving.value = true;
  try {
    await changePassword(formPwd.value.oldPassword, formPwd.value.newPassword, token);
    formPwd.value = { oldPassword: "", newPassword: "", confirmPassword: "" };
    flash("success", "Mot de passe modifié avec succès.");
  } catch (e) {
    flash("error", e.message);
  } finally {
    saving.value = false;
  }
}

function logout() {
  localStorage.removeItem("token");
  localStorage.removeItem("idUser");
  localStorage.removeItem("roles");
  router.push("/login");
}

const menuItems = [
  {
    section: "Informations personnelles",
    items: [
      { key: "editProfile", label: "Modifier mon profil",  icon: "fas fa-user-edit" },
      { key: "changeEmail", label: "Modifier mon email",   icon: "fas fa-envelope"  },
    ],
  },
  {
    section: "Mes annonces",
    items: [
      { key: "myAnouncements",    label: "Mes annonces",        icon: "fas fa-bullhorn"  },
      { key: "recoveredArticles", label: "Articles récupérés",  icon: "fas fa-box-open"  },
    ],
  },
  {
    section: "Sécurité",
    items: [
      { key: "changePassword", label: "Changer le mot de passe", icon: "fas fa-lock"    },
      { key: "accountInfo",    label: "Informations du compte",  icon: "fas fa-id-card" },
    ],
  },
  {
    section: "Notifications",
    items: [
      { key: "notifications", label: "Mes notifications", icon: "fas fa-bell" },
    ],
  },
  {
    section: "Autre",
    items: [
      { key: "help", label: "Aide", icon: "fas fa-question-circle" },
    ],
  },
];
</script>

<template>
  <div class="page">
    <Navbar />

    <div class="main-layout">

      <!-- SIDEBAR -->
      <aside class="sidebar">
        <div v-for="group in menuItems" :key="group.section" class="sidebar-section">
          <p class="section-label">{{ group.section }}</p>
          <ul class="menu-list">
            <li
              v-for="item in group.items"
              :key="item.key"
              :class="['menu-item', { active: activeMenu === item.key }]"
              @click="activeMenu = item.key; successMsg = ''; errorMsg = ''; if(item.key === 'myAnouncements') loadMyAnouncements(); if(item.key === 'notifications') loadNotifications(); if(item.key === 'recoveredArticles') loadRecoveredArticles()"
            >
              <i :class="item.icon"></i>
              {{ item.label }}
              <span v-if="item.key === 'notifications' && unreadCount > 0" class="notif-badge-menu">
                {{ unreadCount }}
              </span>
            </li>
          </ul>
        </div>

        <div class="sidebar-section">
          <ul class="menu-list">
            <li class="menu-item menu-item--logout" @click="logout">
              <i class="fas fa-sign-out-alt"></i> Déconnexion
            </li>
          </ul>
        </div>
      </aside>

      <!-- CONTENU -->
      <main class="content-area">

        <!-- CHARGEMENT -->
        <div v-if="loading" class="content-card state-box">
          <i class="fas fa-spinner fa-spin"></i>
          <p>Chargement du profil...</p>
        </div>

        <template v-else>

          <!-- MESSAGES GLOBAUX -->
          <div v-if="successMsg" class="alert alert-success">
            <i class="fas fa-check-circle"></i> {{ successMsg }}
          </div>
          <div v-if="errorMsg" class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> {{ errorMsg }}
          </div>

          <!-- MODIFIER LE PROFIL -->
          <div v-if="activeMenu === 'editProfile'" class="content-card">
            <div class="profile-header">
              <div class="cover-bg"></div>
              <div class="avatar-wrapper">
                <div class="avatar-initials">
                  {{ (user?.name?.[0] ?? '') }}{{ (user?.surname?.[0] ?? '') }}
                </div>
              </div>
            </div>

            <div class="form-section">
              <h2><i class="fas fa-user-edit"></i> Modifier mon profil</h2>

              <form @submit.prevent="submitProfile">
                <div class="form-row">
                  <div class="input-group">
                    <label>Nom</label>
                    <input v-model="formProfile.name" type="text" placeholder="Votre nom" required />
                  </div>
                  <div class="input-group">
                    <label>Prénom</label>
                    <input v-model="formProfile.surname" type="text" placeholder="Votre prénom" required />
                  </div>
                </div>

                <button class="btn-submit" type="submit" :disabled="saving">
                  <i :class="saving ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
                  {{ saving ? "Enregistrement..." : "Enregistrer les modifications" }}
                </button>
              </form>
            </div>
          </div>

          <!-- MODIFIER EMAIL -->
          <div v-if="activeMenu === 'changeEmail'" class="content-card">
            <div class="profile-header">
              <div class="cover-bg"></div>
              <div class="avatar-wrapper">
                <div class="avatar-initials">
                  {{ (user?.name?.[0] ?? '') }}{{ (user?.surname?.[0] ?? '') }}
                </div>
              </div>
            </div>

            <div class="form-section">
              <h2><i class="fas fa-envelope"></i> Modifier mon email</h2>

              <form @submit.prevent="submitEmail">
                <div class="input-group">
                  <label>Adresse e-mail actuelle</label>
                  <input :value="user?.email" type="email" disabled class="input-disabled" />
                </div>

                <div class="input-group">
                  <label>Nouvelle adresse e-mail</label>
                  <input v-model="formEmail.email" type="email" placeholder="nouvel@email.com" required />
                </div>

                <button class="btn-submit" type="submit" :disabled="saving">
                  <i :class="saving ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
                  {{ saving ? "Enregistrement..." : "Modifier mon adresse e-mail" }}
                </button>
              </form>
            </div>
          </div>

          <!-- CHANGER MOT DE PASSE -->
          <div v-if="activeMenu === 'changePassword'" class="content-card">
            <div class="form-section" style="padding-top: 30px;">
              <h2><i class="fas fa-lock"></i> Changer le mot de passe</h2>

              <form @submit.prevent="submitPassword">
                <div class="input-group">
                  <label>Ancien mot de passe</label>
                  <input type="password" v-model="formPwd.oldPassword" placeholder="••••••••" required />
                </div>
                <div class="input-group">
                  <label>Nouveau mot de passe</label>
                  <input type="password" v-model="formPwd.newPassword" placeholder="••••••••" required />
                </div>
                <div class="input-group">
                  <label>Confirmer le nouveau mot de passe</label>
                  <input type="password" v-model="formPwd.confirmPassword" placeholder="••••••••" required />
                </div>

                <button class="btn-submit" type="submit" :disabled="saving">
                  <i :class="saving ? 'fas fa-spinner fa-spin' : 'fas fa-key'"></i>
                  {{ saving ? "Modification en cours..." : "Modifier le mot de passe" }}
                </button>
              </form>
            </div>
          </div>

          <!-- INFORMATIONS DU COMPTE -->
          <div v-if="activeMenu === 'accountInfo'" class="content-card">
            <div class="form-section" style="padding-top: 30px;">
              <h2><i class="fas fa-id-card"></i> Informations du compte</h2>

              <div class="account-info">
                <div class="info-row">
                  <span class="info-label"><i class="fas fa-user"></i> Nom complet</span>
                  <span class="info-value">{{ user?.name }} {{ user?.surname }}</span>
                </div>
                <div class="info-row">
                  <span class="info-label"><i class="fas fa-envelope"></i> Adresse email</span>
                  <span class="info-value">{{ user?.email }}</span>
                </div>
                <div class="info-row">
                  <span class="info-label"><i class="fas fa-tag"></i> Rôle(s)</span>
                  <span class="info-value">
                    <span
                      v-for="role in user?.roles?.filter(r => r !== 'ROLE_USER')"
                      :key="role"
                      class="role-badge"
                    >{{ role }}</span>
                    <span v-if="!user?.roles?.filter(r => r !== 'ROLE_USER').length" class="text-muted">—</span>
                  </span>
                </div>
                <div class="info-row">
                  <span class="info-label"><i class="fas fa-coins"></i> Crédit</span>
                  <span class="info-value credit">{{ user?.credit ?? 0 }} pts</span>
                </div>
                <div class="info-row">
                  <span class="info-label"><i class="fas fa-box-open"></i> Articles récupérés</span>
                  <span class="info-value recovered">
                    <i class="fas fa-check-circle"></i> {{ user?.recoveredCount ?? 0 }} article{{ (user?.recoveredCount ?? 0) !== 1 ? 's' : '' }}
                  </span>
                </div>
                <div class="info-row">
                  <span class="info-label"><i class="fas fa-calendar-alt"></i> Compte créé le</span>
                  <span class="info-value">{{ user?.createAt ?? '—' }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- MES ANNONCES -->
          <div v-if="activeMenu === 'myAnouncements'" class="content-card">
            <div class="form-section" style="padding-top: 30px;">
              <div class="anouncements-header">
                <h2><i class="fas fa-bullhorn"></i> Mes annonces</h2>
                <router-link to="/PublierView" class="btn-publish">
                  <i class="fas fa-plus"></i> Publier une annonce
                </router-link>
              </div>

              <!-- LOADING -->
              <div v-if="loadingAnouncements" class="state-box">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Chargement...</p>
              </div>

              <!-- VIDE -->
              <div v-else-if="myAnouncements.length === 0" class="state-box">
                <i class="fas fa-box-open"></i>
                <p>Vous n'avez pas encore publié d'annonce.</p>
                <router-link to="/PublierView" class="btn-publish-empty">
                  <i class="fas fa-plus-circle"></i> Publier ma première annonce
                </router-link>
              </div>

              <!-- GRILLE -->
              <div v-else class="my-grid">
                <div v-for="a in myAnouncements" :key="a.id" class="my-card">

                  <!-- IMAGE -->
                  <div class="my-card-img">
                    <img v-if="pictureUrl(a.pictures)" :src="pictureUrl(a.pictures)" :alt="a.title" />
                    <div v-else class="my-no-img"><i class="fas fa-image"></i></div>
                    <span class="my-badge" :class="`badge-${a.status}`">
                      {{ STATUS_LABEL[a.status] ?? '—' }}
                    </span>
                  </div>

                  <!-- INFOS -->
                  <div class="my-card-body">
                    <span class="my-tag">{{ a.categorie }}</span>
                    <p class="my-title">{{ a.title }}</p>
                    <p class="my-date"><i class="fas fa-calendar-alt"></i> {{ formatDate(a.createAt) }}</p>
                  </div>

                  <!-- ACTIONS -->
                  <div class="my-card-footer">
                    <router-link :to="`/annonce/${a.id}`" class="btn-view">
                      <i class="fas fa-eye"></i> Voir
                    </router-link>
                    <button
                      class="btn-del"
                      :class="{ confirm: confirmDelId === a.id }"
                      @click="handleDelete(a.id)"
                    >
                      <i class="fas fa-trash-alt"></i>
                      {{ confirmDelId === a.id ? 'Confirmer ?' : 'Supprimer' }}
                    </button>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <!-- ARTICLES RÉCUPÉRÉS -->
          <div v-if="activeMenu === 'recoveredArticles'" class="content-card">
            <div class="form-section" style="padding-top: 30px;">
              <div class="anouncements-header">
                <h2><i class="fas fa-box-open"></i> Articles récupérés</h2>
              </div>

              <!-- Barre de quota mensuel -->
              <div class="quota-bar-wrap">
                <div class="quota-bar-header">
                  <span class="quota-label">
                    <i class="fas fa-calendar-alt"></i>
                    Récupérations ce mois-ci
                  </span>
                  <span class="quota-count" :class="{ 'quota-full': recoveredThisMonth >= 5 }">
                    {{ recoveredThisMonth }} / 5
                  </span>
                </div>
                <div class="quota-track">
                  <div
                    class="quota-fill"
                    :style="{ width: Math.min(recoveredThisMonth / 5 * 100, 100) + '%' }"
                    :class="{ 'quota-fill-full': recoveredThisMonth >= 5 }"
                  ></div>
                </div>
                <p v-if="recoveredThisMonth >= 5" class="quota-warning">
                  <i class="fas fa-lock"></i>
                  Limite mensuelle atteinte. Vous pourrez contacter des donneurs à nouveau dans 30 jours.
                </p>
                <p v-else class="quota-ok">
                  <i class="fas fa-check-circle"></i>
                  Il vous reste {{ 5 - recoveredThisMonth }} récupération{{ 5 - recoveredThisMonth > 1 ? 's' : '' }} disponible{{ 5 - recoveredThisMonth > 1 ? 's' : '' }} ce mois-ci.
                </p>
              </div>

              <!-- LOADING -->
              <div v-if="loadingRecovered" class="state-box">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Chargement...</p>
              </div>

              <!-- VIDE -->
              <div v-else-if="recoveredArticles.length === 0" class="state-box">
                <i class="fas fa-box-open"></i>
                <p>Vous n'avez pas encore récupéré d'article.</p>
              </div>

              <!-- GRILLE -->
              <div v-else class="my-grid">
                <div v-for="a in recoveredArticles" :key="a.id" class="my-card recovered-card">

                  <!-- IMAGE -->
                  <div class="my-card-img">
                    <img
                      v-if="a.pictures && a.pictures.length > 0"
                      :src="`${URL_FOLDER_ANOUNCEMENT}/${a.pictures[0]}`"
                      :alt="a.title"
                    />
                    <div v-else class="my-no-img"><i class="fas fa-image"></i></div>
                    <span class="my-badge badge-recovered">
                      <i class="fas fa-check"></i> Récupéré
                    </span>
                  </div>

                  <!-- INFOS -->
                  <div class="my-card-body">
                    <span class="my-tag">{{ a.categorie }}</span>
                    <p class="my-title">{{ a.title }}</p>
                    <p class="my-date">
                      <i class="fas fa-user"></i> {{ a.donorName }}
                    </p>
                    <p v-if="a.recoveredAt" class="my-date">
                      <i class="fas fa-calendar-check"></i> {{ formatDate(a.recoveredAt) }}
                    </p>
                  </div>

                  <!-- ACTION -->
                  <div class="my-card-footer">
                    <router-link :to="`/annonce/${a.id}`" class="btn-view">
                      <i class="fas fa-eye"></i> Voir
                    </router-link>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <!-- NOTIFICATIONS -->
          <div v-if="activeMenu === 'notifications'" class="content-card">
            <div class="form-section" style="padding-top: 30px;">
              <div class="notif-header">
                <h2><i class="fas fa-bell"></i> Mes notifications</h2>
                <button
                  v-if="notifications.some(n => !n.isRead)"
                  class="btn-mark-all"
                  @click="handleMarkAllRead"
                >
                  <i class="fas fa-check-double"></i> Tout marquer comme lu
                </button>
              </div>

              <div v-if="loadingNotifs" class="state-box">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Chargement...</p>
              </div>

              <div v-else-if="notifications.length === 0" class="state-box">
                <i class="fas fa-bell-slash"></i>
                <p>Aucune notification pour le moment.</p>
              </div>

              <div v-else class="notif-list">
                <div
                  v-for="n in notifications"
                  :key="n.id"
                  class="notif-item"
                  :class="{ unread: !n.isRead }"
                >
                  <div class="notif-icon-wrap" :style="{ background: notifColor(n.type) + '18', color: notifColor(n.type) }">
                    <i :class="notifIcon(n.type)"></i>
                  </div>
                  <div class="notif-body">
                    <p class="notif-text">{{ n.text }}</p>
                    <div class="notif-meta">
                      <span class="notif-time">{{ formatNotifDate(n.createAt) }}</span>
                      <router-link v-if="n.anouncementId" :to="`/annonce/${n.anouncementId}`" class="notif-link">
                        Voir l'annonce
                      </router-link>
                    </div>
                  </div>
                  <div class="notif-actions">
                    <button
                      v-if="!n.isRead"
                      class="btn-read"
                      title="Marquer comme lu"
                      @click="handleMarkRead(n.id)"
                    >
                      <i class="fas fa-check"></i>
                    </button>
                    <span v-else class="notif-read-dot" title="Lu"><i class="fas fa-check-double"></i></span>
                    <button
                      class="btn-del-notif"
                      :disabled="deletingNotifId === n.id"
                      title="Supprimer"
                      @click="handleDeleteNotif(n.id)"
                    >
                      <i :class="deletingNotifId === n.id ? 'fas fa-spinner fa-spin' : 'fas fa-times'"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- AIDE -->
          <div v-if="activeMenu === 'help'" class="content-card">
            <div class="form-section" style="padding-top: 30px;">
              <h2><i class="fas fa-question-circle"></i> Centre d'aide</h2>

              <!-- Onglets rôles -->
              <div class="help-tabs">
                <button
                  class="help-tab-btn"
                  :class="{ active: helpTab === 'visiteur' }"
                  @click="helpTab = 'visiteur'"
                >
                  <i class="fas fa-eye"></i> Guide Visiteur
                </button>
                <button
                  class="help-tab-btn"
                  :class="{ active: helpTab === 'donateur' }"
                  @click="helpTab = 'donateur'"
                >
                  <i class="fas fa-hand-holding-heart"></i> Guide Donateur
                </button>
              </div>

              <!-- GUIDE VISITEUR -->
              <div v-if="helpTab === 'visiteur'" class="help-guide">
                <div class="guide-intro">
                  <i class="fas fa-eye guide-intro-icon"></i>
                  <div>
                    <p class="guide-intro-title">Vous êtes visiteur</p>
                    <p class="guide-intro-sub">En tant que visiteur, vous pouvez consulter les annonces et contacter l'équipe. Pour profiter de toutes les fonctionnalités, créez un compte donateur.</p>
                  </div>
                </div>

                <div class="guide-steps">
                  <div class="guide-step">
                    <div class="step-num">1</div>
                    <div class="step-content">
                      <h4><i class="fas fa-search"></i> Parcourir les annonces</h4>
                      <p>Depuis la page d'accueil, consultez toutes les annonces disponibles. Utilisez la barre de recherche pour filtrer par mot-clé, ville, code postal ou catégorie.</p>
                    </div>
                  </div>
                  <div class="guide-step">
                    <div class="step-num">2</div>
                    <div class="step-content">
                      <h4><i class="fas fa-info-circle"></i> Voir le détail d'une annonce</h4>
                      <p>Cliquez sur le bouton <strong>"Voir l'article"</strong> d'une annonce pour accéder à sa page de détail : photos, description complète, localisation et informations sur le donneur.</p>
                    </div>
                  </div>
                  <div class="guide-step">
                    <div class="step-num">3</div>
                    <div class="step-content">
                      <h4><i class="fas fa-filter"></i> Filtrer par catégorie</h4>
                      <p>Utilisez la barre latérale gauche de la page d'accueil pour filtrer les annonces par catégorie (Meubles, Électronique, Vêtements, etc.).</p>
                    </div>
                  </div>
                  <div class="guide-step">
                    <div class="step-num">4</div>
                    <div class="step-content">
                      <h4><i class="fas fa-envelope"></i> Contacter l'équipe</h4>
                      <p>Vous avez une question ou signalez un problème ? Utilisez la page <strong>Contact</strong> accessible depuis le menu de navigation. Votre message arrivera directement dans notre messagerie.</p>
                    </div>
                  </div>
                  <div class="guide-step">
                    <div class="step-num">5</div>
                    <div class="step-content">
                      <h4><i class="fas fa-user-plus"></i> Créer un compte donateur</h4>
                      <p>Pour pouvoir publier des annonces, envoyer des messages aux donneurs et ajouter des articles en favoris, inscrivez-vous en cliquant sur <strong>"S'inscrire"</strong> dans le menu. Vous obtiendrez le rôle <em>Donateur</em> automatiquement.</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- GUIDE DONATEUR -->
              <div v-if="helpTab === 'donateur'" class="help-guide">
                <div class="guide-intro guide-intro--donateur">
                  <i class="fas fa-hand-holding-heart guide-intro-icon"></i>
                  <div>
                    <p class="guide-intro-title">Vous êtes donateur</p>
                    <p class="guide-intro-sub">En tant que donateur, vous pouvez publier des annonces, discuter avec d'autres membres et gérer vos dons. Merci pour votre générosité !</p>
                  </div>
                </div>

                <div class="guide-steps">
                  <div class="guide-step">
                    <div class="step-num step-num--donateur">1</div>
                    <div class="step-content">
                      <h4><i class="fas fa-bullhorn"></i> Publier une annonce</h4>
                      <p>Cliquez sur <strong>"Publier"</strong> dans le menu de navigation. Remplissez le titre, la catégorie, la description et l'adresse. Ajoutez une ou plusieurs photos pour attirer l'attention. Validez pour mettre votre annonce en ligne immédiatement.</p>
                    </div>
                  </div>
                  <div class="guide-step">
                    <div class="step-num step-num--donateur">2</div>
                    <div class="step-content">
                      <h4><i class="fas fa-heart"></i> Ajouter en favoris</h4>
                      <p>Sur chaque annonce, cliquez sur l'icône <strong>cœur</strong> pour l'ajouter à vos favoris. Retrouvez vos articles favoris depuis votre profil. Pratique pour suivre les objets qui vous intéressent !</p>
                    </div>
                  </div>
                  <div class="guide-step">
                    <div class="step-num step-num--donateur">3</div>
                    <div class="step-content">
                      <h4><i class="fas fa-comments"></i> Contacter un donneur</h4>
                      <p>Sur la page de détail d'une annonce qui ne vous appartient pas, cliquez sur <strong>"Contacter le donneur"</strong>. Un fil de discussion privé s'ouvrira dans votre messagerie, lié à cette annonce.</p>
                    </div>
                  </div>
                  <div class="guide-step">
                    <div class="step-num step-num--donateur">4</div>
                    <div class="step-content">
                      <h4><i class="fas fa-inbox"></i> Gérer vos messages</h4>
                      <p>Accédez à votre messagerie depuis le menu. Vous y verrez toutes vos conversations, triées par annonce. Cliquez sur une conversation pour lire et répondre aux messages.</p>
                    </div>
                  </div>
                  <div class="guide-step">
                    <div class="step-num step-num--donateur">5</div>
                    <div class="step-content">
                      <h4><i class="fas fa-bell"></i> Recevoir des notifications</h4>
                      <p>À chaque nouvelle annonce publiée sur la plateforme, vous recevez une notification. Consultez-les en cliquant sur l'icône cloche dans le menu. Les notifications non lues apparaissent en badge rouge.</p>
                    </div>
                  </div>
                  <div class="guide-step">
                    <div class="step-num step-num--donateur">6</div>
                    <div class="step-content">
                      <h4><i class="fas fa-trash-alt"></i> Supprimer une annonce</h4>
                      <p>Dans la section <strong>"Mes annonces"</strong> de votre profil, ou directement sur la page de détail de votre annonce, cliquez sur le bouton rouge <strong>"Supprimer"</strong>. Une confirmation est demandée avant la suppression définitive.</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Contact support -->
              <div class="help-support">
                <i class="fas fa-headset"></i>
                <span>Besoin d'une assistance personnalisée ? Contactez-nous via la page <router-link to="/ContactView">Contact</router-link>.</span>
              </div>

            </div>
          </div>

        </template>
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

.main-layout {
  display: flex;
  gap: 24px;
  padding: 28px 24px;
  max-width: 1200px;
  margin: 0 auto;
}

/* SIDEBAR */
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
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.section-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: #999;
  margin: 0 0 10px 0;
}

.menu-list { list-style: none; padding: 0; margin: 0; }

.menu-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 10px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  color: #555;
  transition: background 0.2s, color 0.2s;
}

.menu-item i { width: 18px; text-align: center; color: #0054a6; }
.menu-item:hover { background: #f1f5f9; color: #0054a6; }
.menu-item.active { background: #0054a6; color: white; font-weight: 600; }
.menu-item.active i { color: white; }
.menu-item--logout:hover { color: #e53e3e; background: #fff5f5; }
.menu-item--logout i { color: #e53e3e; }

/* CONTENU */
.content-area { flex: 1; min-width: 0; }

.content-card {
  background: white;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0,0,0,0.07);
}

/* ALERTES */
.alert {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 13px 18px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 16px;
}

.alert-success { background: #dcfce7; color: #16a34a; border: 1px solid #86efac; }
.alert-error   { background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; }

/* ÉTAT CHARGEMENT */
.state-box {
  text-align: center;
  padding: 60px 20px;
  color: #888;
}
.state-box i { font-size: 36px; margin-bottom: 14px; display: block; }

/* HEADER PROFIL */
.profile-header {
  position: relative;
  height: 140px;
}

.cover-bg {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #0054a6, #1a72cc);
}

.avatar-wrapper {
  position: absolute;
  left: 50%;
  bottom: -46px;
  transform: translateX(-50%);
}

.avatar-initials {
  width: 92px;
  height: 92px;
  border-radius: 50%;
  background: white;
  border: 4px solid white;
  box-shadow: 0 4px 16px rgba(0,0,0,0.18);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  font-weight: 800;
  color: #0054a6;
  text-transform: uppercase;
}

/* FORM SECTION */
.form-section { padding: 70px 30px 30px; }

.form-section h2 {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 20px;
  color: #0054a6;
  margin: 0 0 24px 0;
}

.form-row { display: flex; gap: 16px; }
.form-row .input-group { flex: 1; }

.input-group {
  display: flex;
  flex-direction: column;
  margin-bottom: 18px;
}

.input-group label {
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 6px;
  color: #444;
}

.input-group input {
  padding: 10px 12px;
  border-radius: 8px;
  border: 2px solid #e8ecf0;
  font-size: 14px;
  font-family: inherit;
  transition: border-color 0.2s;
  outline: none;
}

.input-group input:focus { border-color: #0054a6; box-shadow: 0 0 0 3px rgba(0,84,166,0.1); }

.input-disabled { background: #f5f5f5; color: #888; cursor: not-allowed; }

/* BOUTON */
.btn-submit {
  width: 100%;
  padding: 13px;
  border: none;
  background: #0054a6;
  color: white;
  font-weight: 600;
  font-size: 15px;
  border-radius: 10px;
  cursor: pointer;
  transition: background 0.2s;
  font-family: inherit;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-submit:hover:not(:disabled) { background: #003f7d; }
.btn-submit:disabled { background: #999; cursor: not-allowed; }

/* INFOS DU COMPTE */
.account-info { margin-top: 8px; }

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 0;
  border-bottom: 1px solid #f0f0f0;
  font-size: 14px;
  gap: 16px;
}

.info-row:last-child { border-bottom: none; }

.info-label {
  font-weight: 600;
  color: #555;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.info-label i { color: #0054a6; width: 16px; }
.info-value { color: #222; text-align: right; }
.text-muted { color: #aaa; }

.role-badge {
  display: inline-block;
  background: #eef4ff;
  color: #0054a6;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  margin-left: 4px;
}

.credit { font-weight: 700; color: #0a7d2c; }

/* AIDE - ONGLETS */
.help-tabs {
  display: flex;
  gap: 10px;
  margin-bottom: 24px;
}

.help-tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border: 2px solid #e8ecf0;
  border-radius: 10px;
  background: white;
  font-size: 14px;
  font-weight: 600;
  color: #666;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.2s;
}
.help-tab-btn:hover { border-color: #0054a6; color: #0054a6; }
.help-tab-btn.active { background: #0054a6; border-color: #0054a6; color: white; }

/* AIDE - INTRO */
.guide-intro {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  background: #eef4ff;
  border-radius: 12px;
  padding: 18px 20px;
  margin-bottom: 24px;
  border-left: 4px solid #0054a6;
}

.guide-intro--donateur {
  background: #f0fdf4;
  border-left-color: #16a34a;
}

.guide-intro-icon {
  font-size: 28px;
  color: #0054a6;
  margin-top: 2px;
  flex-shrink: 0;
}

.guide-intro--donateur .guide-intro-icon { color: #16a34a; }

.guide-intro-title {
  font-size: 16px;
  font-weight: 700;
  color: #0054a6;
  margin: 0 0 5px 0;
}
.guide-intro--donateur .guide-intro-title { color: #16a34a; }

.guide-intro-sub {
  font-size: 13px;
  color: #555;
  margin: 0;
  line-height: 1.6;
}

/* AIDE - ÉTAPES */
.guide-steps { display: flex; flex-direction: column; gap: 14px; }

.guide-step {
  display: flex;
  gap: 16px;
  align-items: flex-start;
  background: #fafbff;
  border: 1px solid #e8edf5;
  border-radius: 12px;
  padding: 16px 18px;
  transition: box-shadow 0.2s, transform 0.2s;
}
.guide-step:hover { box-shadow: 0 4px 12px rgba(0,84,166,0.08); transform: translateX(3px); }

.step-num {
  min-width: 32px;
  height: 32px;
  background: #0054a6;
  color: white;
  font-weight: 800;
  font-size: 14px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-top: 1px;
}

.step-num--donateur { background: #16a34a; }

.step-content h4 {
  margin: 0 0 6px 0;
  font-size: 14px;
  font-weight: 700;
  color: #222;
  display: flex;
  align-items: center;
  gap: 8px;
}
.step-content h4 i { color: #0054a6; font-size: 13px; }
.guide-intro--donateur ~ .guide-steps .step-content h4 i { color: #16a34a; }

.step-content p {
  margin: 0;
  font-size: 13px;
  color: #555;
  line-height: 1.7;
}

/* AIDE - SUPPORT */
.help-support {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 24px;
  padding: 14px 18px;
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 10px;
  font-size: 14px;
  color: #92400e;
}
.help-support i { font-size: 18px; color: #d97706; flex-shrink: 0; }
.help-support a { color: #0054a6; font-weight: 600; text-decoration: none; }
.help-support a:hover { text-decoration: underline; }

/* MES ANNONCES */
.anouncements-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 24px;
}

.anouncements-header h2 { margin: 0; }

.btn-publish {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 9px 18px;
  background: #0054a6;
  color: white;
  border-radius: 9px;
  font-size: 13px;
  font-weight: 700;
  text-decoration: none;
  transition: background 0.2s;
}
.btn-publish:hover { background: #003f7d; }

.btn-publish-empty {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-top: 16px;
  padding: 11px 22px;
  background: #0054a6;
  color: white;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 700;
  text-decoration: none;
  transition: background 0.2s;
}
.btn-publish-empty:hover { background: #003f7d; }

.my-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 18px;
}

.my-card {
  border: 1px solid #eef0f5;
  border-radius: 12px;
  overflow: hidden;
  background: #fafbff;
  transition: box-shadow 0.2s, transform 0.2s;
}
.my-card:hover { box-shadow: 0 6px 18px rgba(0,0,0,0.09); transform: translateY(-2px); }

.my-card-img {
  position: relative;
  height: 130px;
  background: #eef2f7;
}
.my-card-img img { width: 100%; height: 100%; object-fit: cover; }

.my-no-img {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ccc;
  font-size: 30px;
}

.my-badge {
  position: absolute;
  top: 8px;
  left: 8px;
  font-size: 10px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 20px;
  text-transform: uppercase;
}
.badge-1 { background: #dcfce7; color: #16a34a; }
.badge-2 { background: #fef9c3; color: #ca8a04; }
.badge-3 { background: #fee2e2; color: #dc2626; }

.my-card-body { padding: 10px 12px 6px; }

.my-tag {
  display: inline-block;
  background: #eef4ff;
  color: #0054a6;
  font-size: 10px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 20px;
  text-transform: uppercase;
  margin-bottom: 5px;
}

.my-title {
  font-size: 14px;
  font-weight: 700;
  color: #1a1a2e;
  margin: 0 0 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.my-date {
  font-size: 11px;
  color: #aaa;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 5px;
}
.my-date i { color: #0054a6; }

.my-card-footer {
  display: flex;
  gap: 6px;
  padding: 8px 12px 12px;
}

.btn-view {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  padding: 7px;
  background: #0054a6;
  color: white;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
  transition: background 0.2s;
}
.btn-view:hover { background: #003f7d; }

.btn-del {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  padding: 7px;
  background: #fee2e2;
  color: #dc2626;
  border: none;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
  transition: background 0.2s, color 0.2s;
}
.btn-del:hover, .btn-del.confirm { background: #dc2626; color: white; }

/* ARTICLES RÉCUPÉRÉS - valeur dans accountInfo */
.recovered {
  font-weight: 700;
  color: #16a34a;
  display: flex;
  align-items: center;
  gap: 6px;
}
.recovered i { color: #16a34a; }

/* BADGE "Récupéré" sur les cartes */
.badge-recovered {
  background: #dcfce7;
  color: #16a34a;
}

/* CARTE RÉCUPÉRÉE */
.recovered-card { border-color: #bbf7d0; }

/* QUOTA MENSUEL */
.quota-bar-wrap {
  background: #f8faff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px 20px;
  margin-bottom: 24px;
}

.quota-bar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.quota-label {
  font-size: 14px;
  font-weight: 600;
  color: #555;
  display: flex;
  align-items: center;
  gap: 7px;
}
.quota-label i { color: #0054a6; }

.quota-count {
  font-size: 15px;
  font-weight: 800;
  color: #0054a6;
}
.quota-count.quota-full { color: #dc2626; }

.quota-track {
  width: 100%;
  height: 10px;
  background: #e2e8f0;
  border-radius: 999px;
  overflow: hidden;
  margin-bottom: 10px;
}

.quota-fill {
  height: 100%;
  background: #0054a6;
  border-radius: 999px;
  transition: width 0.4s ease;
}
.quota-fill-full { background: #dc2626; }

.quota-warning {
  font-size: 13px;
  color: #b91c1c;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 7px;
}
.quota-warning i { color: #dc2626; }

.quota-ok {
  font-size: 13px;
  color: #15803d;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 7px;
}
.quota-ok i { color: #16a34a; }

/* NOTIFICATIONS - BADGE MENU */
.notif-badge-menu {
  margin-left: auto;
  background: #dc2626;
  color: white;
  font-size: 10px;
  font-weight: 700;
  padding: 1px 6px;
  border-radius: 20px;
  min-width: 18px;
  text-align: center;
  line-height: 16px;
}

/* NOTIFICATIONS - EN-TÊTE */
.notif-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 20px;
}
.notif-header h2 { margin: 0; }

.btn-mark-all {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 8px 16px;
  background: #eef4ff;
  color: #0054a6;
  border: 1px solid #c7d9f5;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
  transition: background 0.2s, color 0.2s;
}
.btn-mark-all:hover { background: #0054a6; color: white; border-color: #0054a6; }

/* NOTIFICATIONS - LISTE */
.notif-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.notif-item {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 14px 16px;
  border-radius: 12px;
  background: #fafbff;
  border: 1px solid #eef0f5;
  transition: box-shadow 0.2s;
}
.notif-item:hover { box-shadow: 0 3px 10px rgba(0,84,166,0.07); }
.notif-item.unread {
  background: #f0f6ff;
  border-color: #c7d9f5;
  border-left: 3px solid #0054a6;
}

/* NOTIFICATIONS - ICÔNE */
.notif-icon-wrap {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
}

/* NOTIFICATIONS - CORPS */
.notif-body {
  flex: 1;
  min-width: 0;
}

.notif-text {
  margin: 0 0 6px 0;
  font-size: 14px;
  color: #222;
  line-height: 1.5;
}

.notif-meta {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.notif-time {
  font-size: 12px;
  color: #aaa;
}

.notif-link {
  font-size: 12px;
  color: #0054a6;
  font-weight: 600;
  text-decoration: none;
}
.notif-link:hover { text-decoration: underline; }

/* NOTIFICATIONS - ACTIONS */
.notif-actions {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}

.btn-read {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  border: 1px solid #c7d9f5;
  background: white;
  color: #0054a6;
  font-size: 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s, color 0.2s;
}
.btn-read:hover { background: #0054a6; color: white; }

.notif-read-dot {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: #dcfce7;
  color: #16a34a;
  font-size: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-del-notif {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  border: 1px solid #fca5a5;
  background: white;
  color: #dc2626;
  font-size: 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s, color 0.2s;
}
.btn-del-notif:hover:not(:disabled) { background: #dc2626; color: white; }
.btn-del-notif:disabled { opacity: 0.5; cursor: not-allowed; }

/* RESPONSIVE */
@media (max-width: 900px) {
  .main-layout { flex-direction: column; }
  .sidebar { width: 100%; }
}

@media (max-width: 600px) {
  .main-layout { padding: 16px; gap: 16px; }
  .form-row { flex-direction: column; }
  .form-section { padding: 70px 18px 20px; }
}
</style>
