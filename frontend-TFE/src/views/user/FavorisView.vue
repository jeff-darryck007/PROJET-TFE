<script setup lang="ts">
import Navbar from "../Navbar.vue";
import "@fortawesome/fontawesome-free/css/all.css";
import { ref } from "vue";

/* ARTICLES FAVORIS SIMULÉS */
const favorites = ref([
  {
    id: 1,
    title: "Bureau en bois",
    category: "Meubles",
    status: "Disponible",
    description: "Bureau en bois massif, très bon état.",
    city: "Paris",
    publishDate: "2026-02-25",
    pictures: ["/src/image/desk.jpg"],
  },
  {
    id: 2,
    title: "Chaise de bureau",
    category: "Meubles",
    status: "Réservé",
    description: "Chaise ergonomique, confortable.",
    city: "Lyon",
    publishDate: "2026-02-20",
    pictures: ["/src/image/chair.jpg"],
  },
  {
    id: 3,
    title: "Ordinateur portable",
    category: "Électronique",
    status: "Disponible",
    description: "Laptop 8GB RAM, 256GB SSD.",
    city: "Marseille",
    publishDate: "2026-02-22",
    pictures: ["/src/image/laptop.jpg"],
  },
]);

/* SUPPRIMER DES FAVORIS */
function removeFavorite(id: number) {
  favorites.value = favorites.value.filter(item => item.id !== id);
}
</script>

<template>
  <div class="page-container">
    <Navbar />

    <div class="form-wrapper">
      <div class="card">
        <h2><i class="fas fa-star"></i> Mes Favoris</h2>

        <div v-if="favorites.length === 0" class="empty-msg">
          Vous n'avez aucun article en favoris pour le moment.
        </div>

        <div class="favorites-grid" v-else>
          <div v-for="item in favorites" :key="item.id" class="favorite-card">
            <div class="favorite-image">
              <img :src="item.pictures[0]" alt="Photo article" />
            </div>

            <div class="favorite-info">
              <h3>{{ item.title }}</h3>
              <p class="category">{{ item.category }} - <span :class="item.status === 'Disponible' ? 'status-available' : 'status-reserved'">{{ item.status }}</span></p>
              <p class="description">{{ item.description }}</p>
              <p class="city"><i class="fas fa-map-marker-alt"></i> {{ item.city }}</p>
              <p class="date"><i class="fas fa-calendar-alt"></i> Publié le {{ item.publishDate }}</p>

              <button class="btn-remove" @click="removeFavorite(item.id)">
                <i class="fas fa-trash-alt"></i> Retirer des favoris
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
.page-container {
  background: #f4f6f9;
  min-height: 100vh;
  padding-bottom: 40px;
  font-family: Arial, sans-serif;
}

.form-wrapper {
  display: flex;
  justify-content: center;
  padding: 40px 20px;
}

.card {
  background: white;
  padding: 30px;
  border-radius: 14px;
  box-shadow: 0 8px 25px rgba(0,0,0,0.08);
  width: 100%;
  max-width: 900px;
}

h2 {
  margin-bottom: 25px;
  color: #0054a6;
  display: flex;
  align-items: center;
  gap: 10px;
}

.favorites-grid {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.favorite-card {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  background: #f9fbff;
  border-radius: 12px;
  padding: 15px;
  border-left: 4px solid #0054a6;
}

.favorite-image img {
  width: 150px;
  height: 120px;
  object-fit: cover;
  border-radius: 10px;
}

.favorite-info {
  flex: 1;
}

.favorite-info h3 {
  margin: 0 0 8px 0;
  color: #222;
}

.favorite-info .category {
  font-size: 13px;
  color: #555;
  margin-bottom: 8px;
}

.status-available {
  color: #0a7d2c;
  font-weight: bold;
}

.status-reserved {
  color: #f1b800;
  font-weight: bold;
}

.favorite-info .description {
  font-size: 14px;
  margin-bottom: 8px;
  color: #444;
}

.favorite-info .city,
.favorite-info .date {
  font-size: 13px;
  color: #555;
  margin-bottom: 5px;
}

.btn-remove {
  background: #f44336;
  color: white;
  border: none;
  padding: 8px 12px;
  border-radius: 10px;
  cursor: pointer;
  font-size: 13px;
  transition: 0.25s;
}

.btn-remove:hover {
  background: #c62828;
}

.empty-msg {
  text-align: center;
  font-size: 14px;
  color: #777;
  padding: 40px 0;
}

/* RESPONSIVE */
@media (max-width: 700px) {
  .favorite-card {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .favorite-image img {
    width: 100%;
    max-width: 300px;
  }
}
</style>