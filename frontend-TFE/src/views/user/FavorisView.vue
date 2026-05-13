<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import Navbar from "../Navbar.vue";
import Footer from "../Footer.vue";
import "@fortawesome/fontawesome-free/css/all.css";
import { fetchFavorites, toggleFavorite } from "../../controller/controllerFavorite.js";
import { URL_FOLDER_ANOUNCEMENT } from "../../server/config.js";

const router = useRouter();

const favorites = ref([]);
const loading = ref(true);
const errorMessage = ref("");
const removing = ref(new Set());

const STATUS_LABEL = { 1: "Disponible", 2: "Réservé", 3: "Donné" };
const STATUS_CLASS = { 1: "status-available", 2: "status-reserved", 3: "status-given" };

onMounted(async () => {
  const token = localStorage.getItem("token");
  if (!token) {
    router.push("/login");
    return;
  }
  try {
    const data = await fetchFavorites(token);
    favorites.value = data.favorites ?? [];
  } catch {
    errorMessage.value = "Impossible de charger vos favoris.";
  } finally {
    loading.value = false;
  }
});

function pictureUrl(pictures) {
  if (!pictures || pictures.length === 0) return null;
  return `${URL_FOLDER_ANOUNCEMENT}/${pictures[0]}`;
}

function cityFromAdress(adress) {
  if (!adress) return "";
  const parts = adress.split(",");
  return parts.length >= 2
    ? parts[parts.length - 1].replace(/^\d+\s*/, "").trim()
    : adress;
}

async function removeFavorite(id) {
  const token = localStorage.getItem("token");
  if (!token) { router.push("/login"); return; }
  removing.value = new Set([...removing.value, id]);
  try {
    await toggleFavorite(id, token);
    favorites.value = favorites.value.filter(f => f.id !== id);
  } catch {
    errorMessage.value = "Erreur lors du retrait des favoris.";
    setTimeout(() => { errorMessage.value = ""; }, 4000);
  } finally {
    const next = new Set(removing.value);
    next.delete(id);
    removing.value = next;
  }
}
</script>

<template>
  <div class="page-container">
    <Navbar />

    <div class="content-wrapper">
      <div class="panel">
        <h2><i class="fas fa-heart"></i> Mes Favoris</h2>

        <!-- CHARGEMENT -->
        <div v-if="loading" class="state-box">
          <i class="fas fa-spinner fa-spin"></i>
          <p>Chargement de vos favoris...</p>
        </div>

        <!-- ERREUR -->
        <div v-else-if="errorMessage" class="state-box error">
          <i class="fas fa-exclamation-circle"></i>
          <p>{{ errorMessage }}</p>
        </div>

        <!-- VIDE -->
        <div v-else-if="favorites.length === 0" class="state-box">
          <i class="fas fa-heart-broken"></i>
          <p>Vous n'avez aucun article en favoris pour le moment.</p>
        </div>

        <!-- LISTE DES FAVORIS -->
        <div v-else class="favorites-list">
          <div v-for="item in favorites" :key="item.id" class="favorite-card">

            <!-- IMAGE -->
            <div class="fav-img">
              <img v-if="pictureUrl(item.pictures)" :src="pictureUrl(item.pictures)" :alt="item.title" />
              <div v-else class="no-img"><i class="fas fa-image"></i></div>
              <span class="badge" :class="`badge-${item.status}`">
                {{ STATUS_LABEL[item.status] ?? 'Inconnu' }}
              </span>
            </div>

            <!-- INFOS -->
            <div class="fav-info">
              <span class="tag">{{ item.categorie }}</span>
              <h3>{{ item.title }}</h3>
              <p class="description">{{ item.description }}</p>
              <div class="meta">
                <span><i class="fas fa-map-marker-alt"></i> {{ cityFromAdress(item.adress) }}</span>
                <span><i class="fas fa-calendar-alt"></i> {{ item.createAt }}</span>
                <span><i class="fas fa-user"></i> {{ item.donorName }}</span>
              </div>

              <button
                class="btn-remove"
                :disabled="removing.has(item.id)"
                @click="removeFavorite(item.id)"
              >
                <i :class="removing.has(item.id) ? 'fas fa-spinner fa-spin' : 'fas fa-heart-broken'"></i>
                {{ removing.has(item.id) ? 'Retrait...' : 'Retirer des favoris' }}
              </button>
            </div>

          </div>
        </div>

      </div>
    </div>

    <Footer />
  </div>
</template>

<style scoped>
.page-container {
  background: #f4f6f9;
  min-height: 100vh;
  font-family: Arial, sans-serif;
}

.content-wrapper {
  max-width: 900px;
  margin: 0 auto;
  padding: 40px 20px 60px;
}

.panel {
  background: white;
  border-radius: 14px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.07);
  padding: 30px;
}

h2 {
  color: #0054a6;
  font-size: 20px;
  font-weight: 800;
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 28px;
}

/* ÉTATS */
.state-box {
  text-align: center;
  padding: 60px 20px;
  color: #888;
}

.state-box i { font-size: 40px; margin-bottom: 14px; display: block; }
.state-box p  { font-size: 15px; }
.state-box.error { color: #dc2626; }

/* LISTE */
.favorites-list {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

/* CARTE */
.favorite-card {
  display: flex;
  gap: 20px;
  background: #f9fbff;
  border-radius: 12px;
  border-left: 4px solid #0054a6;
  overflow: hidden;
  transition: box-shadow 0.2s;
}

.favorite-card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

/* IMAGE */
.fav-img {
  position: relative;
  width: 160px;
  min-height: 130px;
  flex-shrink: 0;
  background: #eef2f7;
}

.fav-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.no-img {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ccc;
  font-size: 36px;
}

.badge {
  position: absolute;
  top: 8px;
  left: 8px;
  padding: 2px 9px;
  border-radius: 20px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
}

.badge-1 { background: #dcfce7; color: #16a34a; }
.badge-2 { background: #fef9c3; color: #ca8a04; }
.badge-3 { background: #fee2e2; color: #dc2626; }

/* INFOS */
.fav-info {
  flex: 1;
  padding: 14px 16px 14px 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.tag {
  display: inline-block;
  background: #e8f0fb;
  color: #0054a6;
  font-size: 10px;
  font-weight: 700;
  padding: 2px 9px;
  border-radius: 20px;
  text-transform: uppercase;
  width: fit-content;
}

.fav-info h3 {
  font-size: 16px;
  font-weight: 700;
  color: #1a1a2e;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.description {
  font-size: 13px;
  color: #666;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.5;
  margin: 0;
}

.meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  font-size: 12px;
  color: #888;
  margin-top: 4px;
}

.meta i { color: #0054a6; margin-right: 3px; }

.btn-remove {
  margin-top: auto;
  align-self: flex-start;
  background: #fee2e2;
  color: #dc2626;
  border: 1px solid #fca5a5;
  padding: 7px 14px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 7px;
  transition: background 0.2s;
}

.btn-remove:hover:not(:disabled) {
  background: #dc2626;
  color: white;
  border-color: #dc2626;
}

.btn-remove:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 600px) {
  .favorite-card {
    flex-direction: column;
  }

  .fav-img {
    width: 100%;
    height: 160px;
  }

  .fav-info {
    padding: 14px;
  }
}
</style>
