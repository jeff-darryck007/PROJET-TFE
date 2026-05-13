<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import Navbar from "../Navbar.vue";
import Footer from "../Footer.vue";
import "@fortawesome/fontawesome-free/css/all.css";
import {
  fetchNotifications,
  markAsRead,
  markAllAsRead,
  deleteNotification,
} from "../../controller/controllerNotification.js";

const router = useRouter();
const token  = localStorage.getItem("token");

const notifications = ref([]);
const loading       = ref(true);
const unreadCount   = ref(0);

const TYPE_ICON = {
  "new-article":    "fas fa-bullhorn",
  "message":        "fas fa-envelope",
  "status-update":  "fas fa-sync-alt",
};

onMounted(async () => {
  if (!token) { router.push("/login"); return; }
  await load();
});

async function load() {
  loading.value = true;
  try {
    const data = await fetchNotifications(token);
    notifications.value = data.notifications ?? [];
    unreadCount.value   = data.unreadCount   ?? 0;
  } catch { /* silencieux */ }
  finally { loading.value = false; }
}

async function handleMarkRead(notif) {
  if (notif.isRead) return;
  try {
    await markAsRead(notif.id, token);
    notif.isRead = true;
    unreadCount.value = Math.max(0, unreadCount.value - 1);
    window.dispatchEvent(new CustomEvent("notif-count-changed"));
  } catch { /* silencieux */ }
}

async function handleMarkAll() {
  try {
    await markAllAsRead(token);
    notifications.value.forEach(n => { n.isRead = true; });
    unreadCount.value = 0;
    window.dispatchEvent(new CustomEvent("notif-count-changed"));
  } catch { /* silencieux */ }
}

async function handleDelete(id) {
  try {
    await deleteNotification(id, token);
    const n = notifications.value.find(n => n.id === id);
    if (n && !n.isRead) unreadCount.value = Math.max(0, unreadCount.value - 1);
    notifications.value = notifications.value.filter(n => n.id !== id);
    window.dispatchEvent(new CustomEvent("notif-count-changed"));
  } catch { /* silencieux */ }
}

function navigate(notif) {
  handleMarkRead(notif);
  if (notif.type === "message" && notif.anouncementId) {
    router.push({ name: "Message-View", query: { annonce: notif.anouncementId } });
  } else if (notif.anouncementId) {
    router.push("/");
  }
}

function formatDate(str) {
  const d = new Date(str);
  if (isNaN(d)) return str;
  const today = new Date();
  const isToday =
    d.getDate() === today.getDate() &&
    d.getMonth() === today.getMonth() &&
    d.getFullYear() === today.getFullYear();
  const time = d.toLocaleTimeString("fr-BE", { hour: "2-digit", minute: "2-digit" });
  return isToday
    ? "Aujourd'hui à " + time
    : d.toLocaleDateString("fr-BE", { day: "2-digit", month: "long" }) + " à " + time;
}
</script>

<template>
  <div class="page">
    <Navbar />

    <div class="wrapper">
      <div class="panel pg-slide-up">

        <!-- HEADER -->
        <div class="panel-header">
          <div class="title-area">
            <h2><i class="fas fa-bell"></i> Mes notifications</h2>
            <span v-if="unreadCount > 0" class="badge-count">{{ unreadCount }} non lue{{ unreadCount > 1 ? 's' : '' }}</span>
          </div>
          <button
            v-if="unreadCount > 0"
            class="btn-mark-all"
            @click="handleMarkAll"
          >
            <i class="fas fa-check-double"></i> Tout marquer comme lu
          </button>
        </div>

        <!-- CHARGEMENT -->
        <div v-if="loading" class="state-box">
          <i class="fas fa-spinner fa-spin"></i>
          <p>Chargement...</p>
        </div>

        <!-- VIDE -->
        <div v-else-if="notifications.length === 0" class="state-box muted">
          <i class="fas fa-bell-slash"></i>
          <p>Aucune notification pour le moment.</p>
        </div>

        <!-- LISTE -->
        <ul v-else class="notif-list">
          <li
            v-for="n in notifications"
            :key="n.id"
            :class="['notif-item', { unread: !n.isRead }]"
          >
            <!-- ICÔNE TYPE -->
            <div :class="['notif-icon', `icon-${n.type}`]">
              <i :class="TYPE_ICON[n.type] ?? 'fas fa-info-circle'"></i>
            </div>

            <!-- CONTENU (cliquable) -->
            <div class="notif-body" @click="navigate(n)">
              <p class="notif-text">{{ n.text }}</p>
              <span class="notif-date">{{ formatDate(n.createAt) }}</span>
            </div>

            <!-- ACTIONS -->
            <div class="notif-actions">
              <button
                v-if="!n.isRead"
                class="btn-read"
                title="Marquer comme lu"
                @click.stop="handleMarkRead(n)"
              >
                <i class="fas fa-check"></i>
              </button>
              <button
                class="btn-delete"
                title="Supprimer"
                @click.stop="handleDelete(n.id)"
              >
                <i class="fas fa-times"></i>
              </button>
            </div>
          </li>
        </ul>

      </div>
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

.wrapper {
  max-width: 800px;
  margin: 0 auto;
  padding: 36px 20px 60px;
}

.panel {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.07);
  overflow: hidden;
}

/* HEADER */
.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding: 22px 24px;
  border-bottom: 1px solid #f0f0f0;
}

.title-area {
  display: flex;
  align-items: center;
  gap: 12px;
}

h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 800;
  color: #0054a6;
  display: flex;
  align-items: center;
  gap: 10px;
}

.badge-count {
  background: #fee2e2;
  color: #dc2626;
  font-size: 12px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 20px;
}

.btn-mark-all {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 8px 16px;
  border: 1.5px solid #0054a6;
  background: transparent;
  color: #0054a6;
  font-size: 13px;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s, color 0.2s;
  font-family: inherit;
}
.btn-mark-all:hover { background: #0054a6; color: white; }

/* ÉTATS */
.state-box {
  text-align: center;
  padding: 60px 20px;
  color: #aaa;
}
.state-box i { font-size: 40px; display: block; margin-bottom: 14px; }
.state-box p { font-size: 15px; }
.muted { color: #ccc; }

/* LISTE */
.notif-list { list-style: none; padding: 0; margin: 0; }

.notif-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 24px;
  border-bottom: 1px solid #f5f5f5;
  transition: background 0.15s;
}
.notif-item:last-child { border-bottom: none; }
.notif-item:hover { background: #fafbff; }
.notif-item.unread { background: #f0f6ff; }

/* ICÔNE */
.notif-icon {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
}
.icon-new-article   { background: #e8f0fb; color: #0054a6; }
.icon-message       { background: #fef9c3; color: #ca8a04; }
.icon-status-update { background: #dcfce7; color: #16a34a; }

/* CORPS */
.notif-body {
  flex: 1;
  cursor: pointer;
  min-width: 0;
}

.notif-text {
  margin: 0 0 4px 0;
  font-size: 14px;
  color: #1a1a2e;
  line-height: 1.5;
}

.unread .notif-text { font-weight: 600; }

.notif-date {
  font-size: 12px;
  color: #aaa;
}

/* ACTIONS */
.notif-actions {
  display: flex;
  gap: 6px;
  flex-shrink: 0;
}

.btn-read,
.btn-delete {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  transition: background 0.2s, color 0.2s;
}

.btn-read   { background: #dcfce7; color: #16a34a; }
.btn-delete { background: #f3f4f6; color: #999; }

.btn-read:hover   { background: #16a34a; color: white; }
.btn-delete:hover { background: #fee2e2; color: #dc2626; }

@media (max-width: 600px) {
  .panel-header { flex-direction: column; align-items: flex-start; }
  .notif-item   { padding: 14px 16px; gap: 12px; }
}
</style>
