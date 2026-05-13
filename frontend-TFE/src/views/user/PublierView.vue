<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import Navbar from "../Navbar.vue";
import Footer from "../Footer.vue";
import "@fortawesome/fontawesome-free/css/all.css";
import { createAnouncement } from "../../controller/controllerAnouncement.js";

const router = useRouter();

const CATEGORIES = [
  "Meubles", "Électronique", "Vêtements", "Maison",
  "Livres", "Jouets", "Sport", "Jardin", "Autre",
];

const form = ref({
  title:       "",
  categorie:   "",
  description: "",
  street:      "",
  number:      "",
  postalCode:  "",
  city:        "",
});

const pictures  = ref([]);   // File[]
const previews  = ref([]);   // { url, name }[]
const loading   = ref(false);
const errorMsg  = ref("");
const successMsg = ref("");

onMounted(() => {
  const token = localStorage.getItem("token");
  if (!token) router.push("/login");
});

function handleFiles(e) {
  const files = Array.from(e.target.files);
  files.forEach(file => {
    pictures.value.push(file);
    previews.value.push({ url: URL.createObjectURL(file), name: file.name });
  });
  e.target.value = "";
}

function removeImage(index) {
  URL.revokeObjectURL(previews.value[index].url);
  pictures.value.splice(index, 1);
  previews.value.splice(index, 1);
}

function buildAdress() {
  return `${form.value.street} ${form.value.number}, ${form.value.postalCode} ${form.value.city}`.trim();
}

async function submitArticle() {
  errorMsg.value  = "";
  successMsg.value = "";

  const { title, categorie, description, street, number, postalCode, city } = form.value;
  if (!title || !categorie || !description || !street || !number || !postalCode || !city) {
    errorMsg.value = "Veuillez remplir tous les champs obligatoires.";
    return;
  }

  const token = localStorage.getItem("token");
  if (!token) { router.push("/login"); return; }

  loading.value = true;
  try {
    await createAnouncement(
      { title, categorie, description, adress: buildAdress(), postX: 0, postY: 0, postZ: 0 },
      pictures.value,
      token
    );

    successMsg.value = "Annonce publiée avec succès !";
    form.value = { title: "", categorie: "", description: "", street: "", number: "", postalCode: "", city: "" };
    pictures.value  = [];
    previews.value  = [];

    setTimeout(() => router.push("/"), 1800);
  } catch (e) {
    errorMsg.value = e.message || "Erreur lors de la publication.";
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="page-container">
    <Navbar />

    <div class="form-wrapper">
      <div class="card">
        <h2><i class="fas fa-plus-circle"></i> Publier une annonce</h2>

        <!-- SUCCÈS -->
        <div v-if="successMsg" class="alert alert-success">
          <i class="fas fa-check-circle"></i> {{ successMsg }}
          <span class="redirect-hint">Redirection en cours...</span>
        </div>

        <!-- ERREUR -->
        <div v-if="errorMsg" class="alert alert-error">
          <i class="fas fa-exclamation-circle"></i> {{ errorMsg }}
        </div>

        <form @submit.prevent="submitArticle">

          <!-- TITRE -->
          <div class="input-group">
            <label>Titre <span class="req">*</span></label>
            <input v-model="form.title" type="text" placeholder="Ex : Bureau en bois massif" />
          </div>

          <!-- CATEGORIE -->
          <div class="input-group">
            <label>Catégorie <span class="req">*</span></label>
            <select v-model="form.categorie">
              <option value="">Choisir une catégorie</option>
              <option v-for="cat in CATEGORIES" :key="cat" :value="cat">{{ cat }}</option>
            </select>
          </div>

          <!-- DESCRIPTION -->
          <div class="input-group">
            <label>Description <span class="req">*</span></label>
            <textarea
              v-model="form.description"
              rows="4"
              placeholder="Décrivez l'objet : état, dimensions, raison du don..."
            ></textarea>
          </div>

          <!-- ADRESSE -->
          <div class="address-section">
            <h3><i class="fas fa-map-marker-alt"></i> Adresse de récupération</h3>

            <div class="form-row">
              <div class="input-group">
                <label>Rue <span class="req">*</span></label>
                <input v-model="form.street" type="text" placeholder="Rue de la Paix" />
              </div>
              <div class="input-group input-small">
                <label>N° <span class="req">*</span></label>
                <input v-model="form.number" type="text" placeholder="12" />
              </div>
            </div>

            <div class="form-row">
              <div class="input-group input-small">
                <label>Code postal <span class="req">*</span></label>
                <input v-model="form.postalCode" type="text" placeholder="1000" />
              </div>
              <div class="input-group">
                <label>Ville <span class="req">*</span></label>
                <input v-model="form.city" type="text" placeholder="Bruxelles" />
              </div>
            </div>
          </div>

          <!-- PHOTOS -->
          <div class="input-group">
            <label>Photos</label>

            <label class="file-picker">
              <i class="fas fa-camera"></i>
              Ajouter des photos
              <input type="file" multiple accept="image/*" @change="handleFiles" hidden />
            </label>

            <div v-if="previews.length > 0" class="preview-grid">
              <div v-for="(img, i) in previews" :key="i" class="preview-item">
                <img :src="img.url" :alt="img.name" />
                <button type="button" class="btn-remove-img" @click="removeImage(i)" title="Supprimer">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- STATUT AUTOMATIQUE -->
          <div class="status-info">
            <i class="fas fa-circle-check"></i>
            Votre annonce sera publiée avec le statut <strong>Disponible</strong>
          </div>

          <!-- BOUTON SUBMIT -->
          <button class="btn-submit" type="submit" :disabled="loading || !!successMsg">
            <i :class="loading ? 'fas fa-spinner fa-spin' : 'fas fa-paper-plane'"></i>
            {{ loading ? "Publication en cours..." : "Publier l'annonce" }}
          </button>

        </form>
      </div>
    </div>

    <Footer />
  </div>
</template>

<style scoped>
.page-container {
  background: #f4f6f9;
  min-height: 100vh;
  padding-bottom: 60px;
  font-family: Arial, sans-serif;
}

.form-wrapper {
  display: flex;
  justify-content: center;
  padding: 40px 20px;
}

.card {
  background: white;
  padding: 32px;
  border-radius: 16px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.08);
  width: 100%;
  max-width: 700px;
}

h2 {
  margin: 0 0 28px 0;
  color: #0054a6;
  font-size: 22px;
  display: flex;
  align-items: center;
  gap: 10px;
}

/* ALERTES */
.alert {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 13px 16px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 22px;
  flex-wrap: wrap;
}

.alert-success {
  background: #dcfce7;
  color: #16a34a;
  border: 1px solid #86efac;
}

.alert-error {
  background: #fee2e2;
  color: #dc2626;
  border: 1px solid #fca5a5;
}

.redirect-hint {
  font-size: 12px;
  opacity: 0.75;
  margin-left: auto;
}

/* CHAMPS */
.input-group {
  display: flex;
  flex-direction: column;
  margin-bottom: 20px;
}

label {
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 7px;
  color: #444;
}

.req { color: #dc2626; }

input,
select,
textarea {
  padding: 10px 13px;
  border-radius: 8px;
  border: 2px solid #e8ecf0;
  font-size: 14px;
  font-family: inherit;
  transition: border-color 0.2s;
  outline: none;
  background: white;
}

input:focus,
select:focus,
textarea:focus {
  border-color: #0054a6;
  box-shadow: 0 0 0 3px rgba(0,84,166,0.1);
}

textarea { resize: vertical; min-height: 100px; }

/* ADRESSE */
.address-section {
  background: #f9fbff;
  padding: 18px;
  border-radius: 12px;
  margin-bottom: 22px;
  border-left: 4px solid #0054a6;
}

.address-section h3 {
  margin: 0 0 16px 0;
  color: #0054a6;
  font-size: 15px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.form-row {
  display: flex;
  gap: 14px;
}

.form-row .input-group { flex: 1; margin-bottom: 14px; }
.form-row .input-group:last-child { margin-bottom: 0; }
.input-small { max-width: 130px; }

/* PHOTOS */
.file-picker {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border: 2px dashed #0054a6;
  border-radius: 8px;
  color: #0054a6;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: background 0.2s;
  width: fit-content;
  margin-bottom: 4px;
}

.file-picker:hover { background: #eef4ff; }

.preview-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 12px;
}

.preview-item {
  position: relative;
  width: 90px;
  height: 90px;
}

.preview-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 10px;
  border: 2px solid #e8ecf0;
}

.btn-remove-img {
  position: absolute;
  top: -6px;
  right: -6px;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 50%;
  width: 22px;
  height: 22px;
  font-size: 11px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

/* STATUT INFO */
.status-info {
  display: flex;
  align-items: center;
  gap: 9px;
  background: #dcfce7;
  color: #16a34a;
  border: 1px solid #86efac;
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 13px;
  margin-bottom: 16px;
}

.status-info i { font-size: 15px; }

/* BOUTON */
.btn-submit {
  width: 100%;
  padding: 14px;
  border: none;
  background: #0054a6;
  color: white;
  font-weight: 700;
  font-size: 15px;
  border-radius: 10px;
  cursor: pointer;
  transition: background 0.2s;
  font-family: inherit;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
}

.btn-submit:hover:not(:disabled) { background: #003f7d; }
.btn-submit:disabled { background: #999; cursor: not-allowed; }

/* RESPONSIVE */
@media (max-width: 600px) {
  .form-row { flex-direction: column; }
  .input-small { max-width: 100%; }
  .card { padding: 20px; }
}
</style>
