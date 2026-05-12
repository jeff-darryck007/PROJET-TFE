<script setup lang="ts">
import Navbar from "../Navbar.vue";
import Footer from "../Footer.vue";
import "@fortawesome/fontawesome-free/css/all.css";
import { ref } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();

const form = ref({
  code: "",
  name: "",
  surname: "",
  email: "",
  newEmail: "",
  password: "",
  oldPassword: "",
  newPassword: "",
  confirmPassword: "",
  roles: [] as string[],
  picture: null as File | null,
  credit: 0,
  createAt: new Date().toISOString().substring(0, 10),
});

const preview = ref<string | null>(null);
const loading = ref(false);

function handleFile(e: Event) {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    form.value.picture = target.files[0];
    preview.value = URL.createObjectURL(target.files[0]);
  }
}

function submitForm() {
  console.log("Form data:", form.value);
}

function changePassword() {
  if (!form.value.oldPassword || !form.value.newPassword) {
    alert("Veuillez remplir tous les champs");
    return;
  }
  if (form.value.newPassword !== form.value.confirmPassword) {
    alert("Les mots de passe ne correspondent pas");
    return;
  }
  loading.value = true;
  setTimeout(() => {
    loading.value = false;
    alert("Mot de passe modifié avec succès");
  }, 1000);
}

const isLoggedIn = ref(!!localStorage.getItem("token"));
const roles = ref(localStorage.getItem("roles") ?? "");

function checkIsAdmin(roleString: string) {
  if (!roleString) return false;
  return roleString.split(",").includes("admin");
}

const isAdmin = checkIsAdmin(roles.value);

function logout() {
  localStorage.removeItem("token");
  localStorage.removeItem("idUser");
  localStorage.removeItem("roles");
  isLoggedIn.value = false;
  router.push("/login");
}

const activeMenu = ref("editProfile");

const menuItems = [
  {
    section: "Informations personnelles",
    items: [
      { key: "editProfile", label: "Modifier mon profil", icon: "fas fa-user-edit" },
      { key: "changeEmail", label: "Modifier mon email", icon: "fas fa-envelope" },
    ],
  },
  {
    section: "Sécurité",
    items: [
      { key: "changePassword", label: "Changer le mot de passe", icon: "fas fa-lock" },
      { key: "accountInfo", label: "Informations du compte", icon: "fas fa-id-card" },
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
              @click="activeMenu = item.key"
            >
              <i :class="item.icon"></i>
              {{ item.label }}
            </li>
          </ul>
        </div>

        <div class="sidebar-section">
          <ul class="menu-list">
            <li v-if="!isLoggedIn" class="menu-item">
              <a href="/login"><i class="fas fa-sign-in-alt"></i> Connexion</a>
            </li>
            <li v-else class="menu-item menu-item--logout" @click="logout">
              <i class="fas fa-sign-out-alt"></i> Déconnexion
            </li>
          </ul>
        </div>

        <div v-if="isLoggedIn && isAdmin" class="sidebar-section sidebar-section--admin">
          <p class="section-label">Administration</p>
          <ul class="menu-list">
            <li class="menu-item"><i class="fas fa-users-cog"></i> Utilisateurs</li>
            <li class="menu-item"><i class="fas fa-comments"></i> Commentaires</li>
            <li class="menu-item"><i class="fas fa-bullhorn"></i> Annonces</li>
            <li class="menu-item"><i class="fas fa-chart-line"></i> Statistiques</li>
          </ul>
        </div>
      </aside>

      <!-- CONTENU PRINCIPAL -->
      <main class="content-area">

        <!-- MODIFIER LE PROFIL -->
        <div v-if="activeMenu === 'editProfile'" class="content-card">
          <div class="profile-header">
            <img
              src="/src/image/diegartenprofis-garden-professionals-7598976_1920.jpg"
              class="cover-image"
            />
            <div class="avatar-wrapper">
              <img
                :src="preview || 'https://devcodesms.com//component/assets/images/client/ifpsj.png'"
                alt="Avatar"
                class="avatar"
              />
            </div>
          </div>

          <div class="form-section">
            <h2><i class="fas fa-user-edit"></i> Modifier mon profil</h2>

            <form @submit.prevent="submitForm">
              <div class="form-row">
                <div class="input-group">
                  <label>Nom</label>
                  <input v-model="form.name" type="text" placeholder="Votre nom" required />
                </div>
                <div class="input-group">
                  <label>Prénom</label>
                  <input v-model="form.surname" type="text" placeholder="Votre prénom" required />
                </div>
              </div>

              <div class="input-group">
                <label>Rôle</label>
                <select v-model="form.roles" multiple>
                  <option value="donateur">Donateur</option>
                  <option value="visiteur">Visiteur</option>
                </select>
              </div>

              <div class="input-group">
                <label>Photo de profil</label>
                <input type="file" @change="handleFile" accept="image/*" />
              </div>

              <button class="btn-submit" type="submit">
                <i class="fas fa-save"></i> Enregistrer les modifications
              </button>
            </form>
          </div>
        </div>

        <!-- CHANGER EMAIL -->
        <div v-if="activeMenu === 'changeEmail'" class="content-card">
          <div class="profile-header">
            <img
              src="/src/image/diegartenprofis-garden-professionals-7598976_1920.jpg"
              class="cover-image"
            />
            <div class="avatar-wrapper">
              <img
                src="https://devcodesms.com//component/assets/images/client/ifpsj.png"
                alt="Avatar"
                class="avatar"
              />
            </div>
          </div>

          <div class="form-section">
            <h2><i class="fas fa-envelope"></i> Modifier mon email</h2>

            <form @submit.prevent="submitForm">
              <div class="input-group">
                <label>Adresse e-mail actuelle</label>
                <input v-model="form.email" type="email" placeholder="email@exemple.com" required />
              </div>

              <div class="input-group">
                <button type="button" class="btn-secondary">
                  <i class="fas fa-paper-plane"></i> Envoyer le code de vérification
                </button>
              </div>

              <div class="input-group">
                <label>Code de vérification</label>
                <input v-model="form.code" type="text" placeholder="Entrez le code reçu" required />
              </div>

              <div class="input-group">
                <label>Nouvelle adresse e-mail</label>
                <input v-model="form.newEmail" type="email" placeholder="nouvel@email.com" required />
              </div>

              <button class="btn-submit" type="submit">
                <i class="fas fa-save"></i> Modifier mon adresse e-mail
              </button>
            </form>
          </div>
        </div>

        <!-- CHANGER MOT DE PASSE -->
        <div v-if="activeMenu === 'changePassword'" class="content-card">
          <div class="form-section">
            <h2><i class="fas fa-lock"></i> Changer le mot de passe</h2>

            <div class="input-group">
              <label>Ancien mot de passe</label>
              <input type="password" v-model="form.oldPassword" placeholder="••••••••" />
            </div>

            <div class="input-group">
              <label>Nouveau mot de passe</label>
              <input type="password" v-model="form.newPassword" placeholder="••••••••" />
            </div>

            <div class="input-group">
              <label>Confirmer le nouveau mot de passe</label>
              <input type="password" v-model="form.confirmPassword" placeholder="••••••••" />
            </div>

            <button class="btn-submit" @click="changePassword" :disabled="loading">
              <i class="fas fa-key"></i>
              {{ loading ? "Modification en cours..." : "Modifier le mot de passe" }}
            </button>
          </div>
        </div>

        <!-- INFORMATIONS DU COMPTE -->
        <div v-if="activeMenu === 'accountInfo'" class="content-card">
          <div class="form-section">
            <h2><i class="fas fa-id-card"></i> Informations du compte</h2>

            <div class="account-info">
              <div class="info-row">
                <span class="info-label"><i class="fas fa-user"></i> Nom complet</span>
                <span class="info-value">{{ form.name || '—' }} {{ form.surname }}</span>
              </div>
              <div class="info-row">
                <span class="info-label"><i class="fas fa-envelope"></i> Adresse email</span>
                <span class="info-value">{{ form.email || form.newEmail || '—' }}</span>
              </div>
              <div class="info-row">
                <span class="info-label"><i class="fas fa-tag"></i> Rôle(s)</span>
                <span class="info-value">
                  <span v-if="form.roles.length === 0" class="text-muted">—</span>
                  <span v-for="role in form.roles" :key="role" class="role-badge">{{ role }}</span>
                </span>
              </div>
              <div class="info-row">
                <span class="info-label"><i class="fas fa-coins"></i> Crédit</span>
                <span class="info-value credit">{{ form.credit }} €</span>
              </div>
              <div class="info-row">
                <span class="info-label"><i class="fas fa-calendar-alt"></i> Compte créé le</span>
                <span class="info-value">{{ form.createAt }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- AIDE -->
        <div v-if="activeMenu === 'help'" class="content-card">
          <div class="form-section">
            <h2><i class="fas fa-question-circle"></i> Centre d'aide</h2>

            <div class="help-list">
              <div class="help-item">
                <h4><i class="fas fa-user-edit"></i> Comment modifier mon profil ?</h4>
                <p>Cliquez sur "Modifier mon profil" dans la barre latérale, modifiez vos informations et enregistrez.</p>
              </div>
              <div class="help-item">
                <h4><i class="fas fa-lock"></i> Comment changer mon mot de passe ?</h4>
                <p>Dans la section "Sécurité", cliquez sur "Changer le mot de passe", remplissez les champs et confirmez.</p>
              </div>
              <div class="help-item">
                <h4><i class="fas fa-envelope"></i> Comment modifier mon adresse e-mail ?</h4>
                <p>Cliquez sur "Modifier mon email", renseignez votre ancienne adresse, puis confirmez avec le code reçu.</p>
              </div>
              <div class="help-item">
                <h4><i class="fas fa-headset"></i> Besoin d'assistance ?</h4>
                <p>Contactez notre support à <strong>support@partagegratuit.be</strong></p>
              </div>
            </div>
          </div>
        </div>

      </main>
    </div>

    <Footer />
  </div>
</template>

<style scoped>
/* === LAYOUT === */
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

/* === SIDEBAR === */
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
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.sidebar-section--admin {
  border: 1px solid #e3eefc;
  background: #f8fbff;
}

.section-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: #999;
  margin: 0 0 10px 0;
}

.menu-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

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

.menu-item i {
  width: 18px;
  text-align: center;
  color: #0054a6;
}

.menu-item:hover {
  background: #f1f5f9;
  color: #0054a6;
}

.menu-item.active {
  background: #0054a6;
  color: white;
  font-weight: 600;
}

.menu-item.active i {
  color: white;
}

.menu-item a {
  color: inherit;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
}

.menu-item--logout:hover {
  color: #e53e3e;
  background: #fff5f5;
}

/* === CONTENU === */
.content-area {
  flex: 1;
  min-width: 0;
}

.content-card {
  background: white;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.07);
}

/* === PROFIL HEADER === */
.profile-header {
  position: relative;
  height: 160px;
  background: #ddd;
}

.cover-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-wrapper {
  position: absolute;
  left: 50%;
  bottom: -50px;
  transform: translateX(-50%);
}

.avatar {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid white;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.18);
}

/* === FORM SECTION === */
.form-section {
  padding: 70px 30px 30px;
}

.form-section h2 {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 20px;
  color: #0054a6;
  margin: 0 0 24px 0;
}

/* Sections sans header de profil */
.content-card > .form-section:first-child {
  padding-top: 30px;
}

.form-row {
  display: flex;
  gap: 16px;
  margin-bottom: 0;
}

.form-row .input-group {
  flex: 1;
}

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

.input-group input,
.input-group select {
  padding: 10px 12px;
  border-radius: 8px;
  border: 2px solid #e8ecf0;
  font-size: 14px;
  font-family: inherit;
  transition: border-color 0.2s;
  outline: none;
}

.input-group input:focus,
.input-group select:focus {
  border-color: #0054a6;
  box-shadow: 0 0 0 3px rgba(0, 84, 166, 0.1);
}

.preview {
  margin-top: 10px;
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 50%;
  border: 2px solid #ddd;
}

/* === BOUTONS === */
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
  transition: background 0.2s, transform 0.1s;
  font-family: inherit;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-submit:hover {
  background: #003f7d;
  transform: translateY(-1px);
}

.btn-submit:disabled {
  background: #999;
  cursor: not-allowed;
  transform: none;
}

.btn-secondary {
  padding: 9px 16px;
  border: 2px solid #f1b800;
  background: transparent;
  color: #f1b800;
  font-weight: 600;
  font-size: 14px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s, color 0.2s;
  font-family: inherit;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-secondary:hover {
  background: #f1b800;
  color: white;
}

/* === INFOS DU COMPTE === */
.account-info {
  margin-top: 8px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 0;
  border-bottom: 1px solid #f0f0f0;
  font-size: 14px;
  gap: 16px;
}

.info-row:last-child {
  border-bottom: none;
}

.info-label {
  font-weight: 600;
  color: #555;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.info-label i {
  color: #0054a6;
  width: 16px;
}

.info-value {
  color: #222;
  text-align: right;
}

.text-muted {
  color: #aaa;
}

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

.credit {
  font-weight: 700;
  color: #0a7d2c;
}

/* === AIDE === */
.help-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.help-item {
  background: #f9fbff;
  padding: 16px;
  border-radius: 10px;
  border-left: 4px solid #0054a6;
  transition: background 0.2s;
}

.help-item:hover {
  background: #eef4ff;
}

.help-item h4 {
  margin: 0 0 8px 0;
  color: #0054a6;
  font-size: 14px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 8px;
}

.help-item p {
  margin: 0;
  font-size: 14px;
  color: #444;
  line-height: 1.6;
}

/* === RESPONSIVE === */
@media (max-width: 900px) {
  .main-layout {
    flex-direction: column;
  }

  .sidebar {
    width: 100%;
  }
}

@media (max-width: 600px) {
  .main-layout { padding: 16px; gap: 16px; }
  .form-row { flex-direction: column; }
  .form-section { padding: 70px 18px 20px; }
}
</style>
