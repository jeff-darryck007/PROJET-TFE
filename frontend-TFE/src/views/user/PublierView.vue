<script setup lang="ts">
import Navbar from "../Navbar.vue";
import "@fortawesome/fontawesome-free/css/all.css";
import { ref } from "vue";

/* DATE AUTOMATIQUE */
const today = new Date().toISOString().substring(0, 10);

/* FORMULAIRE ARTICLE */
const article = ref({
  title: "",
  category: "",
  status: "Disponible", // Toujours disponible à la création
  description: "",
  street: "",
  number: "",
  postalCode: "",
  city: "",
  publishDate: today,
  pictures: [] as File[],
});

const previews = ref<string[]>([]);
const loading = ref(false);

/* UPLOAD MULTIPLE IMAGES */
function handleFiles(e: Event) {
  const target = e.target as HTMLInputElement;

  if (target.files) {
    article.value.pictures = Array.from(target.files);
    previews.value = article.value.pictures.map(file =>
      URL.createObjectURL(file)
    );
  }
}

/* SUBMIT */
function submitArticle() {
  if (
    !article.value.title ||
    !article.value.category ||
    !article.value.description ||
    !article.value.street ||
    !article.value.number ||
    !article.value.postalCode ||
    !article.value.city
  ) {
    alert("Veuillez remplir tous les champs obligatoires");
    return;
  }

  loading.value = true;

  setTimeout(() => {
    loading.value = false;
    alert("Article publié avec succès 🎉");
    console.log("Article:", article.value);
  }, 1000);
}
</script>

<template>
  <div class="page-container">
    <Navbar />

    <div class="form-wrapper">
      <div class="card">
        <h2><i class="fas fa-plus-circle"></i> Publier un article</h2>

        <form @submit.prevent="submitArticle">

          <!-- TITRE -->
          <div class="input-group">
            <label>Titre *</label>
            <input v-model="article.title" type="text" placeholder="Ex: Bureau en bois" />
          </div>

          <!-- CATEGORIE -->
          <div class="input-group">
            <label>Catégorie *</label>
            <select v-model="article.category">
              <option value="">Choisir une catégorie</option>
              <option>Meubles</option>
              <option>Électronique</option>
              <option>Vêtements</option>
              <option>Maison</option>
              <option>Autre</option>
            </select>
          </div>

          <!-- STATUS (toujours disponible à la création) -->
          <div class="input-group">
            <label>Status</label>
            <select v-model="article.status" disabled>
              <option>Disponible</option>
            </select>
          </div>

          <!-- DESCRIPTION -->
          <div class="input-group">
            <label>Description *</label>
            <textarea v-model="article.description" rows="4" placeholder="Décrivez votre objet..."></textarea>
          </div>

          <!-- ADRESSE -->
          <div class="address-section">
            <h3><i class="fas fa-map-marker-alt"></i> Adresse</h3>

            <div class="form-row">
              <div class="input-group">
                <label>Nom de la rue *</label>
                <input v-model="article.street" type="text" placeholder="Rue Victor Hugo" />
              </div>

              <div class="input-group small">
                <label>Numéro *</label>
                <input v-model="article.number" type="text" placeholder="15" />
              </div>
            </div>

            <div class="form-row">
              <div class="input-group">
                <label>Code postal *</label>
                <input v-model="article.postalCode" type="text" placeholder="75015" />
              </div>

              <div class="input-group">
                <label>Ville *</label>
                <input v-model="article.city" type="text" placeholder="Paris" />
              </div>
            </div>
          </div>

          <!-- DATE AUTO -->
          <div class="input-group">
            <label>Date de publication</label>
            <input type="date" v-model="article.publishDate" disabled />
          </div>

          <!-- PHOTOS -->
          <div class="input-group">
            <label>Photos (plusieurs possibles)</label>
            <input type="file" multiple @change="handleFiles" />

            <div class="preview-container">
              <img
                v-for="(img, index) in previews"
                :key="index"
                :src="img"
                class="preview"
              />
            </div>
          </div>

          <!-- BOUTON -->
          <button class="btn-submit" :disabled="loading">
            {{ loading ? "Publication..." : "Publier l'article" }}
          </button>

        </form>
      </div>
    </div>
  </div>
</template>

<style>
/* même style que précédemment */
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
  max-width: 700px;
}

h2 {
  margin-bottom: 25px;
  color: #0054a6;
  display: flex;
  align-items: center;
  gap: 10px;
}

.input-group {
  display: flex;
  flex-direction: column;
  margin-bottom: 18px;
}

label {
  font-weight: 600;
  margin-bottom: 6px;
  color: #444;
}

input,
select,
textarea {
  padding: 10px;
  border-radius: 8px;
  border: 1px solid #ccc;
  transition: 0.2s;
  font-size: 14px;
}

input:focus,
select:focus,
textarea:focus {
  border-color: #0054a6;
  outline: none;
  box-shadow: 0 0 0 2px rgba(0,84,166,0.15);
}

.address-section {
  background: #f9fbff;
  padding: 15px;
  border-radius: 12px;
  margin-bottom: 20px;
  border-left: 4px solid #0054a6;
}

.address-section h3 {
  margin-bottom: 15px;
  color: #0054a6;
  font-size: 15px;
}

.form-row {
  display: flex;
  gap: 15px;
  margin-bottom: 15px;
}

.form-row .input-group {
  flex: 1;
}

.input-group.small {
  max-width: 120px;
}

.preview-container {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 10px;
}

.preview {
  width: 90px;
  height: 90px;
  object-fit: cover;
  border-radius: 10px;
  border: 2px solid #eee;
}

.btn-submit {
  width: 100%;
  padding: 12px;
  border: none;
  background: #0054a6;
  color: white;
  font-weight: bold;
  border-radius: 10px;
  cursor: pointer;
  transition: 0.25s;
}

.btn-submit:hover {
  background: #003f7d;
}

.btn-submit:disabled {
  background: #999;
  cursor: not-allowed;
}

@media (max-width: 600px) {
  .form-row {
    flex-direction: column;
  }

  .input-group.small {
    max-width: 100%;
  }
}
</style>