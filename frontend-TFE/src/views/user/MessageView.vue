<script setup>
import { ref, onMounted, nextTick } from "vue";
import { useRouter, useRoute } from "vue-router";
import Navbar from "../Navbar.vue";
import Footer from "../Footer.vue";
import "@fortawesome/fontawesome-free/css/all.css";
import {
  fetchMyConversations,
  fetchComments,
  sendComment,
} from "../../controller/controllerComment.js";

const router = useRouter();
const route  = useRoute();

const token         = localStorage.getItem("token");
const currentUserId = parseInt(localStorage.getItem("idUser") ?? "0");

// Conversation sélectionnée
const selected = ref(null); // { anouncementId, threadUserId, anouncementTitle, donorId, isOwner, threadUserName, donorName }

const conversations     = ref([]);
const messages          = ref([]);
const newMessage        = ref("");
const loadingConvos     = ref(true);
const loadingMessages   = ref(false);
const sending           = ref(false);
const sendError         = ref('');
const messagesContainer = ref(null);

onMounted(async () => {
  if (!token) { router.push("/login"); return; }

  try {
    const data = await fetchMyConversations(token);
    conversations.value = data.conversations ?? [];
  } catch { /* silencieux */ }
  finally { loadingConvos.value = false; }

  // ?annonce=id → ouvre directement la conversation
  const queryId = route.query.annonce ? parseInt(route.query.annonce) : null;
  if (queryId) {
    // Cherche d'abord dans les conversations existantes (propriétaire ou demandeur)
    const existing = conversations.value.find(c => c.anouncementId === queryId);
    if (existing) {
      await openConversation(existing);
    } else {
      // Premier contact : récupérer les infos de l'annonce
      try {
        const data = await fetchComments(queryId, token);
        const isOwner = data.donorId === currentUserId;
        if (!isOwner) {
          const ghost = {
            anouncementId:    queryId,
            anouncementTitle: data.anouncementTitle ?? "Annonce",
            donorId:          data.donorId,
            donorName:        data.donorName ?? "Donneur",
            threadUserId:     currentUserId,
            threadUserName:   "",
            isOwner:          false,
            lastMessage:      "",
            lastDate:         "",
          };
          conversations.value.unshift(ghost);
          await openConversation(ghost, data.comments ?? []);
        }
        // Si l'utilisateur est le propriétaire et qu'il n'a pas encore de convo, rien à faire
      } catch { /* silencieux */ }
    }
  }
});

async function openConversation(convo, preloadedMessages = null) {
  selected.value  = convo;
  messages.value  = [];
  loadingMessages.value = true;

  try {
    if (preloadedMessages !== null) {
      messages.value = preloadedMessages;
    } else {
      const data = await fetchComments(
        convo.anouncementId,
        token,
        convo.isOwner ? convo.threadUserId : null
      );
      messages.value = data.comments ?? [];
    }
  } catch { /* silencieux */ }
  finally {
    loadingMessages.value = false;
    await scrollBottom();
  }
}

async function send() {
  const text = newMessage.value.trim();
  if (!text || !selected.value || sending.value) return;

  sending.value    = true;
  sendError.value  = '';
  newMessage.value = '';

  try {
    const threadUserId = selected.value.isOwner ? selected.value.threadUserId : null;
    const res = await sendComment(selected.value.anouncementId, text, token, threadUserId);
    messages.value.push(res);

    const convo = conversations.value.find(
      c => c.anouncementId === selected.value.anouncementId &&
           c.threadUserId  === selected.value.threadUserId
    );
    if (convo) { convo.lastMessage = text; convo.lastDate = res.date; }

    await scrollBottom();
  } catch (e) {
    newMessage.value = text;
    const msg = e?.response?.data?.error ?? e?.message ?? 'Erreur lors de l\'envoi.';
    sendError.value = msg;
    setTimeout(() => { sendError.value = ''; }, 4000);
  } finally {
    sending.value = false;
  }
}

async function scrollBottom() {
  await nextTick();
  if (messagesContainer.value)
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
}

function isMine(msg) {
  return msg.userId === currentUserId;
}

function formatDate(str) {
  if (!str) return "";
  const d = new Date(str);
  if (isNaN(d)) return str;
  const today = new Date();
  const isToday =
    d.getDate()     === today.getDate() &&
    d.getMonth()    === today.getMonth() &&
    d.getFullYear() === today.getFullYear();
  const time = d.toLocaleTimeString("fr-BE", { hour: "2-digit", minute: "2-digit" });
  return isToday
    ? time
    : d.toLocaleDateString("fr-BE", { day: "2-digit", month: "2-digit" }) + " " + time;
}

function initials(name = "") {
  return (name ?? "").split(" ").filter(Boolean).map(w => w[0]).join("").toUpperCase().slice(0, 2) || "?";
}

// Nom affiché dans le header de la conversation
function chatPartnerName(convo) {
  return convo.isOwner ? convo.threadUserName : convo.donorName;
}

// Label dans la sidebar
function sidebarLabel(convo) {
  return convo.isOwner
    ? `${convo.anouncementTitle} · ${convo.threadUserName}`
    : convo.anouncementTitle;
}

function isSelectedConvo(convo) {
  return selected.value?.anouncementId === convo.anouncementId &&
         selected.value?.threadUserId  === convo.threadUserId;
}
</script>

<template>
  <div class="page">
    <Navbar />

    <div class="layout">

      <!-- SIDEBAR -->
      <aside class="sidebar">
        <div class="sidebar-header">
          <i class="fas fa-comments"></i>
          <span>Mes conversations</span>
        </div>

        <div v-if="loadingConvos" class="sidebar-state">
          <i class="fas fa-spinner fa-spin"></i>
        </div>

        <div v-else-if="conversations.length === 0" class="sidebar-state muted">
          Aucune conversation.<br>Contactez un donneur depuis une annonce.
        </div>

        <ul v-else class="convo-list">
          <li
            v-for="c in conversations"
            :key="`${c.anouncementId}-${c.threadUserId}`"
            :class="['convo-item', { active: isSelectedConvo(c) }]"
            @click="openConversation(c)"
          >
            <div class="convo-avatar" :class="c.isOwner ? 'avatar-donor' : 'avatar-user'">
              {{ initials(sidebarLabel(c)) }}
            </div>
            <div class="convo-info">
              <span class="convo-title">{{ sidebarLabel(c) }}</span>
              <span class="convo-last">{{ c.lastMessage || 'Démarrer la conversation' }}</span>
            </div>
            <span v-if="c.lastDate" class="convo-date">{{ formatDate(c.lastDate) }}</span>
          </li>
        </ul>
      </aside>

      <!-- ZONE CHAT -->
      <main class="chat-area">

        <!-- RIEN DE SÉLECTIONNÉ -->
        <div v-if="!selected" class="chat-empty">
          <i class="fas fa-comment-dots"></i>
          <p>Sélectionnez une conversation ou contactez un donneur depuis une annonce.</p>
          <button class="btn-browse" @click="router.push('/')">
            <i class="fas fa-search"></i> Parcourir les annonces
          </button>
        </div>

        <template v-else>

          <!-- HEADER -->
          <div class="chat-header">
            <div class="chat-avatar">{{ initials(chatPartnerName(selected)) }}</div>
            <div>
              <div class="chat-title">{{ chatPartnerName(selected) }}</div>
              <div class="chat-sub">
                <i class="fas fa-tag"></i>
                {{ selected.anouncementTitle }}
                <span v-if="selected.isOwner" class="badge-owner">Vous êtes le donneur</span>
              </div>
            </div>
          </div>

          <!-- MESSAGES -->
          <div class="messages" ref="messagesContainer">

            <div v-if="loadingMessages" class="chat-state">
              <i class="fas fa-spinner fa-spin"></i>
            </div>

            <div v-else-if="messages.length === 0" class="chat-state muted">
              <i class="fas fa-comment-slash"></i>
              <p>Aucun message. Dites bonjour !</p>
            </div>

            <div
              v-for="msg in messages"
              :key="msg.id"
              :class="['bubble-row', isMine(msg) ? 'mine' : 'theirs']"
            >
              <div v-if="!isMine(msg)" class="bubble-avatar">
                {{ initials(msg.userName) }}
              </div>

              <div class="bubble-wrap">
                <span v-if="!isMine(msg)" class="bubble-name">{{ msg.userName }}</span>
                <div :class="['bubble', isMine(msg) ? 'bubble-mine' : 'bubble-theirs']">
                  {{ msg.contenue }}
                </div>
                <span class="bubble-time">{{ formatDate(msg.date) }}</span>
              </div>
            </div>

          </div>

          <!-- ERREUR ENVOI -->
          <div v-if="sendError" class="send-error">
            <i class="fas fa-exclamation-circle"></i> {{ sendError }}
          </div>

          <!-- SAISIE -->
          <div class="input-bar">
            <input
              v-model="newMessage"
              type="text"
              placeholder="Écrire un message..."
              @keyup.enter="send"
              :disabled="sending"
            />
            <button
              class="btn-send"
              @click="send"
              :disabled="sending || !newMessage.trim()"
            >
              <i :class="sending ? 'fas fa-spinner fa-spin' : 'fas fa-paper-plane'"></i>
            </button>
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
  display: flex;
  flex-direction: column;
}

.layout {
  display: flex;
  flex: 1;
  max-width: 1100px;
  width: 100%;
  margin: 28px auto;
  padding: 0 20px 40px;
  gap: 20px;
}

/* ── SIDEBAR ── */
.sidebar {
  width: 300px;
  flex-shrink: 0;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.07);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.sidebar-header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 18px 20px;
  font-weight: 700;
  font-size: 15px;
  color: #0054a6;
  border-bottom: 1px solid #f0f0f0;
}

.sidebar-state {
  padding: 30px 20px;
  text-align: center;
  color: #aaa;
  font-size: 13px;
  line-height: 1.6;
}
.sidebar-state i { font-size: 22px; display: block; margin-bottom: 8px; }
.muted { color: #bbb; }

.convo-list { list-style: none; padding: 8px; margin: 0; overflow-y: auto; flex: 1; }

.convo-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 10px;
  border-radius: 12px;
  cursor: pointer;
  transition: background 0.15s;
}
.convo-item:hover  { background: #f5f7fa; }
.convo-item.active { background: #eef4ff; }

.convo-avatar {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 700;
  color: white;
  flex-shrink: 0;
}
.avatar-user  { background: #0054a6; }
.avatar-donor { background: #16a34a; }

.convo-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 2px; }

.convo-title {
  font-size: 13px;
  font-weight: 700;
  color: #222;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.convo-last {
  font-size: 12px;
  color: #999;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.convo-date { font-size: 11px; color: #bbb; flex-shrink: 0; }

/* ── CHAT ── */
.chat-area {
  flex: 1;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.07);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  min-height: 70vh;
}

.chat-empty {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #bbb;
  gap: 14px;
  padding: 40px;
  text-align: center;
}
.chat-empty i { font-size: 48px; color: #dde3ee; }
.chat-empty p { font-size: 15px; color: #aaa; max-width: 280px; }

.btn-browse {
  background: #0054a6;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: background 0.2s;
}
.btn-browse:hover { background: #003f7d; }

/* Header */
.chat-header {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 20px;
  border-bottom: 1px solid #f0f0f0;
}

.chat-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: linear-gradient(135deg, #0054a6, #1a72cc);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
  flex-shrink: 0;
}

.chat-title { font-size: 15px; font-weight: 700; color: #1a1a2e; }

.chat-sub {
  font-size: 12px;
  color: #999;
  margin-top: 3px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.badge-owner {
  background: #dcfce7;
  color: #16a34a;
  font-size: 11px;
  font-weight: 700;
  padding: 1px 8px;
  border-radius: 20px;
}

/* Messages */
.messages {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.chat-state {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #ccc;
  gap: 10px;
  font-size: 14px;
  padding: 40px;
  text-align: center;
}
.chat-state i { font-size: 32px; }

/* Bulles */
.bubble-row {
  display: flex;
  align-items: flex-end;
  gap: 8px;
}
.bubble-row.mine   { flex-direction: row-reverse; }
.bubble-row.theirs { flex-direction: row; }

.bubble-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #e2e8f0;
  color: #555;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  flex-shrink: 0;
}

.bubble-wrap { display: flex; flex-direction: column; gap: 3px; max-width: 62%; }
.mine .bubble-wrap   { align-items: flex-end; }
.theirs .bubble-wrap { align-items: flex-start; }

.bubble-name { font-size: 11px; color: #999; margin-left: 2px; }

.bubble {
  padding: 10px 14px;
  border-radius: 18px;
  font-size: 14px;
  line-height: 1.5;
  word-break: break-word;
}

.bubble-mine   { background: #0054a6; color: white; border-bottom-right-radius: 4px; }
.bubble-theirs { background: #f1f5f9; color: #1a1a2e; border-bottom-left-radius: 4px; }

.bubble-time { font-size: 11px; color: #bbb; margin: 0 4px; }

/* Erreur envoi */
.send-error {
  margin: 0 20px 8px;
  padding: 9px 14px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 10px;
  font-size: 13px;
  color: #dc2626;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Saisie */
.input-bar {
  display: flex;
  gap: 10px;
  padding: 14px 20px;
  border-top: 1px solid #f0f0f0;
}

.input-bar input {
  flex: 1;
  padding: 11px 16px;
  border-radius: 24px;
  border: 2px solid #e8ecf0;
  font-size: 14px;
  font-family: inherit;
  outline: none;
  transition: border-color 0.2s;
}
.input-bar input:focus    { border-color: #0054a6; }
.input-bar input:disabled { background: #f9f9f9; }

.btn-send {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  border: none;
  background: #0054a6;
  color: white;
  font-size: 16px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: background 0.2s, transform 0.1s;
}
.btn-send:hover:not(:disabled) { background: #003f7d; transform: scale(1.05); }
.btn-send:disabled { background: #ccc; cursor: not-allowed; }

/* ── RESPONSIVE ── */
@media (max-width: 768px) {
  .layout { flex-direction: column; padding: 0 12px 24px; margin-top: 16px; gap: 14px; }
  .sidebar { width: 100%; max-height: 220px; }
  .chat-area { min-height: 55vh; }
}
</style>
