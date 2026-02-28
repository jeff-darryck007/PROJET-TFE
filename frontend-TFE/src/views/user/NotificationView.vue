<script setup lang="ts">
import Navbar from "../Navbar.vue";
import "@fortawesome/fontawesome-free/css/all.css";
import { ref } from "vue";

/* SIMULATION DES NOTIFICATIONS */
const notifications = ref([
  {
    id: 1,
    type: "new-article",
    text: "Un nouvel article 'Chaise de bureau' a été publié.",
    date: "2026-02-25 09:00",
    read: false,
  },
  {
    id: 2,
    type: "status-update",
    text: "Le statut de l'article 'Bureau en bois' a été modifié en 'Réservé'.",
    date: "2026-02-24 18:30",
    read: true,
  },
  {
    id: 3,
    type: "message",
    text: "Vous avez un nouveau message sur l'article 'Table en bois'.",
    date: "2026-02-24 15:20",
    read: false,
  },
]);

/* MARQUER COMME LU */
function markAsRead(id: number) {
  const notif = notifications.value.find(n => n.id === id);
  if (notif) notif.read = true;
}

/* SUPPRIMER UNE NOTIFICATION */
function deleteNotification(id: number) {
  notifications.value = notifications.value.filter(n => n.id !== id);
}
</script>

<template>
  <div class="page-container">
    <Navbar />

    <div class="notifications-container">
      <h2>Mes Notifications</h2>

      <ul class="notifications-list">
        <li 
          v-for="notif in notifications" 
          :key="notif.id" 
          :class="{ unread: !notif.read }"
        >
          <div class="notif-info" @click="markAsRead(notif.id)">
            <i 
              :class="{
                'fas fa-bell': notif.type === 'new-article',
                'fas fa-envelope': notif.type === 'message',
                'fas fa-info-circle': notif.type === 'status-update'
              }"
            ></i>
            <span class="notif-text">{{ notif.text }}</span>
            <span class="notif-date">{{ notif.date }}</span>
          </div>
          <button class="delete-btn" @click="deleteNotification(notif.id)">
            <i class="fas fa-times"></i>
          </button>
        </li>
      </ul>

      <p v-if="notifications.length === 0" class="empty-msg">
        Aucune notification.
      </p>
    </div>
  </div>
</template>

<style scoped>
.page-container {
  background: #f4f6f9;
  min-height: 100vh;
  font-family: Arial, sans-serif;
  padding: 20px;
}

.notifications-container {
  max-width: 800px;
  margin: 20px auto;
  background: white;
  border-radius: 14px;
  padding: 20px;
  box-shadow: 0 6px 18px rgba(0,0,0,0.08);
}

.notifications-container h2 {
  color: #0054a6;
  margin-bottom: 15px;
}

/* LISTE DES NOTIFICATIONS */
.notifications-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.notifications-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 15px;
  border-bottom: 1px solid #eee;
  border-radius: 10px;
  margin-bottom: 10px;
  cursor: pointer;
  transition: 0.25s;
}

.notifications-list li.unread {
  background: #eef4ff;
  font-weight: 600;
}

.notifications-list li:hover {
  background: #f1f5f9;
}

/* INFO */
.notif-info {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
}

.notif-info i {
  font-size: 18px;
  color: #0054a6;
}

.notif-text {
  flex: 1;
  color: #222;
}

.notif-date {
  font-size: 12px;
  color: #777;
}

/* SUPPRIMER */
.delete-btn {
  background: transparent;
  border: none;
  color: #888;
  cursor: pointer;
  font-size: 14px;
  padding: 5px;
  border-radius: 6px;
  transition: 0.2s;
}

.delete-btn:hover {
  color: #f44336;
  background: #ffe5e5;
}

/* MESSAGE VIDE */
.empty-msg {
  text-align: center;
  color: #777;
  margin-top: 30px;
  font-size: 14px;
}

/* RESPONSIVE */
@media (max-width: 900px) {
  .notifications-container {
    width: 100%;
    padding: 15px;
  }
}
</style>