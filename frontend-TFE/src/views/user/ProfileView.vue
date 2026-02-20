<script setup lang="ts">
import Navbar from "../Navbar.vue";
import "@fortawesome/fontawesome-free/css/all.css";
import { ref } from "vue";

const form = ref({
  code: "",
  name: "",
  surname: "",
  email: "",
  password: "",
  roles: [] as string[],
  picture: null as File | null,
  credit: 0,
  createAt: new Date().toISOString().substring(0, 10),
});

const preview = ref<string | null>(null);

/* UPLOAD IMAGE */
function handleFile(e: Event) {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    form.value.picture = target.files[0];
    preview.value = URL.createObjectURL(target.files[0]);
  }
}

/* SUBMIT */
function submitForm() {
  console.log("Form data:", form.value);

  // Ici vous pourrez envoyer vers API Symfony
}

/* AUTH */
const isLoggedIn = ref(!!localStorage.getItem("token"));
const roles = ref(localStorage.getItem("roles") || "null");

function checkIsAdmin(roles: string) {
  const rolesArray = roles.split(",");
  return Array.isArray(rolesArray) && rolesArray.includes("admin");
}

const isAdmin = checkIsAdmin(roles.value);

function logout() {
  localStorage.removeItem("token");
  isLoggedIn.value = false;
}

/* MENU ACTIF */
const activeMenu = ref("donateurs");

function setMenu(menu: string) {
  activeMenu.value = menu;
}
</script>

<template>
  <div class="page-container">
    <Navbar />

    <div class="spacer"></div>

    <div class="content">
      <!-- SIDEBAR -->
      <aside class="sidebar">
        <!-- INFO -->
        <div class="sidebar-block">
          <h5 class="sidebar-title">Informations personnelles</h5>

          <ul class="menu">
            <li
              :class="{ active: activeMenu === 'donateurs' }"
              @click="setMenu('donateurs')"
            >
              <i class="fas fa-hand-holding-heart"></i>
              Modifier mon profil
            </li>

            <li
              :class="{ active: activeMenu === 'demandeurs' }"
              @click="setMenu('demandeurs')"
            >
              <i class="fas fa-users"></i>
              Modifier mon contact
            </li>
          </ul>
        </div>

        <!-- AUTRE -->
        <div class="sidebar-block">
          <h3 class="sidebar-title">Options</h3>

          <ul class="menu">
            <li><i class="fas fa-question-circle"></i> Articles</li>
            <li><i class="fas fa-question-circle"></i> Messages</li>
          </ul>
        </div>

        <!-- CATEGORIES -->
        <div class="sidebar-block">
          <h3 class="sidebar-title">Sécurité</h3>

          <ul class="menu">
            <li
              :class="{ active: activeMenu === 'bureau' }"
              @click="setMenu('bureau')"
            >
              <i class="fas fa-briefcase"></i> Changer le mot de passe
            </li>

            <li
              :class="{ active: activeMenu === 'electronique' }"
              @click="setMenu('electronique')"
            >
              <i class="fas fa-laptop"></i> Informations du compte
            </li>
          </ul>
        </div>

        <!-- AUTRE -->
        <div class="sidebar-block">
          <h3 class="sidebar-title">Autre</h3>

          <ul class="menu">
            <li><i class="fas fa-question-circle"></i> Aide</li>

            <li v-if="!isLoggedIn">
              <i class="fas fa-sign-in-alt"></i>
              <a href="/login">Connexion</a>
            </li>

            <li v-else @click="logout">
              <i class="fas fa-sign-out-alt"></i>
              Déconnexion
            </li>
          </ul>
        </div>

        <!-- ADMIN -->
        <div v-if="isLoggedIn && isAdmin" class="sidebar-block admin">
          <h3 class="sidebar-title">Administration</h3>

          <ul class="menu">
            <li><i class="fas fa-users-cog"></i> Utilisateurs</li>
            <li><i class="fas fa-comments"></i> Commentaires</li>
            <li><i class="fas fa-bullhorn"></i> Annonces</li>
            <li><i class="fas fa-chart-line"></i> Statistiques</li>
          </ul>
        </div>
      </aside>

      <!-- CONTENU PRINCIPAL -->
      <main class="listing">
        <div class="content-card">
          <div v-if="activeMenu === 'donateurs'">
            <div class="form-container">
              <div class="card">
                <div class="cover-container">
                  <!-- IMAGE DE FOND -->
                  <img
                    src="/src/image/diegartenprofis-garden-professionals-7598976_1920.jpg"
                    class="cover-image"
                  />

                  <!-- AVATAR -->
                  <div class="avatar-wrapper">
                    <img
                      src="https://devcodesms.com//component/assets/images/client/ifpsj.png"
                      alt="Avatar"
                      class="avatar"
                    />
                  </div>
                </div>

                <div style="margin-top: 10px; height: 80px"></div>

                <form @submit.prevent="submitForm">
                  <!-- NOM -->
                  <div class="form-row">
                    <div class="input-group">
                      <label>Nom</label>
                      <input v-model="form.name" type="text" required />
                    </div>

                    <div class="input-group">
                      <label>Prénom</label>
                      <input v-model="form.surname" type="text" required />
                    </div>
                  </div>

                  <!-- ROLES -->
                  <div class="input-group">
                    <label>Rôle</label>
                    <select v-model="form.roles" multiple>
                      <option value="donateur">Donateur</option>
                      <option value="visiteur">Visiteur</option>
                      <option value="admin">Donnateur & Visiteur</option>
                    </select>
                  </div>

                  <!-- PHOTO -->
                  <div class="input-group">
                    <label>Photo de profil</label>
                    <input type="file" @change="handleFile" />

                    <img v-if="preview" :src="preview" class="preview" />
                  </div>

                  <!-- BUTTON -->
                  <button class="btn-submit">Modifier les Informations</button>
                </form>
              </div>
            </div>
          </div>

          <div v-if="activeMenu === 'demandeurs'">
            <div class="form-container">
              <div class="card">
                <div class="cover-container">
                  <!-- IMAGE DE FOND -->
                  <img
                    src="/src/image/diegartenprofis-garden-professionals-7598976_1920.jpg"
                    class="cover-image"
                  />

                  <!-- AVATAR -->
                  <div class="avatar-wrapper">
                    <img
                      src="https://devcodesms.com//component/assets/images/client/ifpsj.png"
                      alt="Avatar"
                      class="avatar"
                    />
                  </div>
                </div>

                <div style="margin-top: 10px; height: 80px"></div>

                <form @submit.prevent="submitForm">
                  <!-- NOM -->
                  <div class="input-group">
                    <label>Mon ancien adresse e-mail</label>
                    <input v-model="form.email" type="email" required />
                  </div>

                  <div class="input-group">
                    <button class="btn-submit-code">Envoyer le code</button>
                  </div>

                  <div class="input-group">
                    <label>Entrer le code reçu</label>
                    <input v-model="form.code" type="text" required />
                  </div>

                  <div class="input-group">
                    <label>Ma nouvelle adresse e-mail</label>
                    <input v-model="form.email" type="email" required />
                  </div>

                  <!-- BUTTON -->
                  <button class="btn-submit">
                    Modifier mon adresse e-mail
                  </button>
                </form>
              </div>
            </div>
          </div>

          <div v-if="activeMenu === 'bureau'">
            <h2>Catégorie Bureau</h2>
            <p>Objets de bureau disponibles.</p>
          </div>

          <div v-if="activeMenu === 'electronique'">
            <h2>Catégorie Électronique</h2>
            <p>Appareils électroniques disponibles.</p>
          </div>

          <div v-if="activeMenu === 'meubles'">
            <h2>Catégorie Meubles</h2>
            <p>Meubles disponibles.</p>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<style>
.cover-container {
  position: relative;
  width: 100%;
  height: 160px;
  border-radius: 14px;
  overflow: visible; /* important pour laisser sortir avatar */
  background: #ddd;
}

/* IMAGE FOND */
.cover-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 14px;
}

/* WRAPPER AVATAR */
.avatar-wrapper {
  position: absolute;
  left: 50%;
  bottom: -60px; /* fait sortir l'avatar */
  transform: translateX(-50%);
}

/* AVATAR */
.avatar {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  object-fit: cover;
  border: 5px solid white;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
}

.form-container {
  display: flex;
  justify-content: center;
  padding: 40px;
  background: #f4f6f9;
  min-height: 100vh;
}

.card {
  background: white;
  padding: 30px;
  border-radius: 14px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
  width: 100%;
  max-width: 600px;
}

h2 {
  margin-bottom: 20px;
  color: #222;
}

/* INPUT */
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
select {
  padding: 10px;
  border-radius: 8px;
  border: 1px solid #ccc;
  transition: 0.2s;
}

input:focus,
select:focus {
  border-color: #0054a6;
  outline: none;
  box-shadow: 0 0 0 2px rgba(0, 84, 166, 0.15);
}

/* ROW */
.form-row {
  display: flex;
  gap: 15px;
}

.form-row .input-group {
  flex: 1;
}

/* PREVIEW */
.preview {
  margin-top: 10px;
  width: 90px;
  height: 90px;
  object-fit: cover;
  border-radius: 50%;
  border: 2px solid #ddd;
}

.btn-submit-code {
  width: fit-content;
  padding: 8px 16px;
  border: none;
  background: #f1b800;
  color: white;
  font-weight: bold;
  border-radius: 10px;
  cursor: pointer;
  transition: 0.25s;
}

/* BUTTON */
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

/* RESPONSIVE */
@media (max-width: 600px) {
  .form-row {
    flex-direction: column;
  }
}

.page-container {
  background: #f4f6f9;
  min-height: 100vh;
  padding: 20px;
  font-family: Arial, sans-serif;
}

.spacer {
  height: 40px;
}

/* LAYOUT */
.content {
  display: flex;
  gap: 20px;
}

/* SIDEBAR */
.sidebar {
  width: 260px;
  background: white;
  border-radius: 14px;
  padding: 20px;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
  height: fit-content;
}

.sidebar-block {
  margin-bottom: 25px;
}

.sidebar-block.admin {
  background: #f8fbff;
  border: 1px solid #e3eefc;
  padding: 15px;
  border-radius: 10px;
}

.sidebar-title {
  font-size: 11px;
  font-weight: bold;
  color: #444;
  margin-bottom: 10px;
  text-transform: uppercase;
}

/* MENU */
.menu {
  list-style: none;
  padding: 0;
  margin: 0;
}

.menu li {
  display: flex;
  align-items: center;
  padding: 9px 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: 0.25s;
  color: #555;
  font-size: 14px;
}

.menu li:hover {
  background: #f1f5f9;
}

/* ACTIF */
.menu li.active {
  background: #0054a6;
  color: white;
  font-weight: 600;
}

.menu li.active i {
  color: white;
}

/* ICON */
.menu i {
  margin-right: 10px;
  width: 20px;
  color: #0054a6;
}

/* BADGE */
.badge {
  background: #0054a6;
  color: white;
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 20px;
}

/* MAIN */
.listing {
  flex: 1;
}

.content-card {
  background: white;
  border-radius: 14px;
  padding: 25px;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
}

h2 {
  margin-top: 0;
  color: #222;
}

/* LINKS */
a {
  text-decoration: none;
  color: inherit;
}

/* RESPONSIVE */
@media (max-width: 900px) {
  .content {
    flex-direction: column;
  }

  .sidebar {
    width: 100%;
  }
}
</style>
