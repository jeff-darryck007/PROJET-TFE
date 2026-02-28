<script setup lang="ts">
import Navbar from "../Navbar.vue";
import "@fortawesome/fontawesome-free/css/all.css";
import { ref } from "vue";

/* ARTICLES SIMULÉS AVEC MESSAGES */
const conversations = ref([
  {
    articleId: 1,
    articleTitle: "Bureau en bois",
    messages: [
      { from: "Utilisateur", text: "Bonjour, ce bureau est-il toujours disponible ?", date: "2026-02-25 10:15" },
      { from: "Donateur", text: "Oui, il est toujours disponible.", date: "2026-02-25 10:20" },
    ],
  },
  {
    articleId: 2,
    articleTitle: "Chaise de bureau",
    messages: [
      { from: "Utilisateur", text: "Je suis intéressé par la chaise.", date: "2026-02-24 14:30" },
      { from: "Donateur", text: "Ok, elle est réservée pour vous.", date: "2026-02-24 14:35" },
    ],
  },
]);

const selectedConversation = ref<number | null>(null);
const newMessage = ref("");

/* ENVOYER UN MESSAGE */
function sendMessage() {
  if (selectedConversation.value === null || !newMessage.value.trim()) return;

  const convo = conversations.value.find(c => c.articleId === selectedConversation.value);
  if (convo) {
    convo.messages.push({
      from: "Utilisateur",
      text: newMessage.value,
      date: new Date().toISOString().slice(0, 16).replace("T", " "),
    });
    newMessage.value = "";
  }
}

/* SELECTIONNER UNE CONVERSATION */
function selectConversation(id: number) {
  selectedConversation.value = id;
}
</script>

<template>
  <div class="page-container">
    <Navbar />

    <div class="content-messages">
      <!-- LISTE DES ARTICLES -->
      <aside class="sidebar-messages">
        <h3>Mes Conversations</h3>
        <ul class="conversation-list">
          <li
            v-for="convo in conversations"
            :key="convo.articleId"
            :class="{ active: selectedConversation === convo.articleId }"
            @click="selectConversation(convo.articleId)"
          >
            <i class="fas fa-box"></i> {{ convo.articleTitle }}
          </li>
        </ul>
      </aside>

      <!-- MESSAGES -->
      <main class="messages-area" v-if="selectedConversation !== null">
        <div class="conversation-header">
          <h3>
            Conversation - {{
              conversations.find(c => c.articleId === selectedConversation)?.articleTitle
            }}
          </h3>
        </div>

        <div class="messages-list">
          <div
            v-for="(msg, index) in conversations.find(c => c.articleId === selectedConversation)?.messages"
            :key="index"
            :class="['message', msg.from === 'Utilisateur' ? 'from-user' : 'from-donor']"
          >
            <div class="message-header">
              <span class="sender">{{ msg.from }}</span>
              <span class="date">{{ msg.date }}</span>
            </div>
            <p>{{ msg.text }}</p>
          </div>
        </div>

        <div class="send-message">
          <input
            type="text"
            v-model="newMessage"
            placeholder="Écrire un message..."
            @keyup.enter="sendMessage"
          />
          <button @click="sendMessage"><i class="fas fa-paper-plane"></i> Envoyer</button>
        </div>
      </main>

      <main class="messages-area empty" v-else>
        <p>Sélectionnez un article pour voir la conversation.</p>
      </main>
    </div>
  </div>
</template>

<style scoped>
.page-container {
  background: #f4f6f9;
  min-height: 100vh;
  padding: 20px;
  font-family: Arial, sans-serif;
}

.content-messages {
  display: flex;
  gap: 20px;
}

/* SIDEBAR */
.sidebar-messages {
  width: 260px;
  background: white;
  border-radius: 14px;
  padding: 20px;
  box-shadow: 0 6px 18px rgba(0,0,0,0.08);
}

.sidebar-messages h3 {
  font-size: 14px;
  font-weight: bold;
  color: #0054a6;
  margin-bottom: 15px;
  text-transform: uppercase;
}

.conversation-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.conversation-list li {
  padding: 10px;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #555;
  transition: 0.2s;
}

.conversation-list li:hover {
  background: #f1f5f9;
}

.conversation-list li.active {
  background: #0054a6;
  color: white;
  font-weight: 600;
}

/* MESSAGES AREA */
.messages-area {
  flex: 1;
  background: white;
  border-radius: 14px;
  padding: 20px;
  box-shadow: 0 6px 18px rgba(0,0,0,0.08);
  display: flex;
  flex-direction: column;
  height: 80vh;
}

.messages-area.empty {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  color: #777;
}

.conversation-header h3 {
  color: #0054a6;
  margin-bottom: 15px;
}

.messages-list {
  flex: 1;
  overflow-y: auto;
  margin-bottom: 15px;
}

.message {
  margin-bottom: 10px;
  padding: 10px 12px;
  border-radius: 10px;
  max-width: 70%;
}

.from-user {
  background: #f1b800;
  color: white;
  align-self: flex-end;
}

.from-donor {
  background: #0054a6;
  color: white;
  align-self: flex-start;
}

.message-header {
  font-size: 12px;
  margin-bottom: 5px;
  display: flex;
  justify-content: space-between;
}

.sender {
  font-weight: bold;
}

.send-message {
  display: flex;
  gap: 10px;
}

.send-message input {
  flex: 1;
  padding: 10px;
  border-radius: 10px;
  border: 1px solid #ccc;
  outline: none;
}

.send-message button {
  background: #0054a6;
  color: white;
  border: none;
  padding: 10px 15px;
  border-radius: 10px;
  cursor: pointer;
  font-weight: bold;
  display: flex;
  align-items: center;
  gap: 5px;
}

.send-message button:hover {
  background: #003f7d;
}

/* RESPONSIVE */
@media (max-width: 900px) {
  .content-messages {
    flex-direction: column;
  }

  .sidebar-messages {
    width: 100%;
  }

  .messages-area {
    height: 60vh;
  }
}
</style>