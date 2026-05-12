<template>
  <div class="notfound-page">

    <div class="notfound-card">

      <!-- Illustration 404 -->
      <div class="illustration">
        <span class="big-404">4</span>
        <div class="globe-wrap">
          <i class="fas fa-globe-europe"></i>
        </div>
        <span class="big-404">4</span>
      </div>

      <h1>Page introuvable</h1>
      <p class="description">
        Oups ! La page que vous cherchez n'existe pas ou a été déplacée.
      </p>

      <div class="actions">
        <button class="btn-primary" @click="goHome">
          <i class="fas fa-home"></i> Retour à l'accueil
        </button>
        <button class="btn-secondary" @click="goBack">
          <i class="fas fa-arrow-left"></i> Page précédente
        </button>
      </div>

      <p class="help-text">
        Besoin d'aide ?
        <router-link to="/ContactView">Contactez-nous</router-link>
      </p>

    </div>

  </div>
</template>

<script setup>
import { useRouter } from "vue-router"

const router = useRouter()

const goHome = () => router.push("/")
const goBack = () => router.back()
</script>

<style scoped>
.notfound-page {
  min-height: 100vh;
  background: #f4f6f9;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  font-family: Arial, sans-serif;
}

.notfound-card {
  background: white;
  border-radius: 20px;
  padding: 60px 48px;
  text-align: center;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
  max-width: 520px;
  width: 100%;
  border-top: 4px solid #f1b800;
}

/* === ILLUSTRATION === */
.illustration {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-bottom: 32px;
}

.big-404 {
  font-size: 110px;
  font-weight: 900;
  color: #0054a6;
  line-height: 1;
  letter-spacing: -4px;
}

.globe-wrap {
  width: 100px;
  height: 100px;
  background: linear-gradient(135deg, #0054a6, #1a72cc);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 24px rgba(0, 84, 166, 0.3);
  flex-shrink: 0;
}

.globe-wrap i {
  font-size: 48px;
  color: white;
  opacity: 0.9;
}

/* === TEXTE === */
h1 {
  font-size: 28px;
  font-weight: 800;
  color: #222;
  margin: 0 0 12px 0;
}

.description {
  font-size: 15px;
  color: #666;
  line-height: 1.6;
  margin: 0 0 36px 0;
}

/* === BOUTONS === */
.actions {
  display: flex;
  gap: 12px;
  justify-content: center;
  flex-wrap: wrap;
  margin-bottom: 24px;
}

.btn-primary {
  padding: 13px 28px;
  background: #f1b800;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  color: #222;
  font-family: inherit;
  display: flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 4px 0 #c89b00;
  transition: background 0.2s, transform 0.1s;
}

.btn-primary:hover {
  background: #dca600;
  transform: translateY(-1px);
}

.btn-secondary {
  padding: 13px 28px;
  background: transparent;
  border: 2px solid #e8ecf0;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  color: #555;
  font-family: inherit;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: border-color 0.2s, color 0.2s, background 0.2s;
}

.btn-secondary:hover {
  border-color: #0054a6;
  color: #0054a6;
  background: #f0f6ff;
}

/* === AIDE === */
.help-text {
  font-size: 13px;
  color: #aaa;
  margin: 0;
}

.help-text a {
  color: #0054a6;
  font-weight: 600;
  text-decoration: none;
}

.help-text a:hover {
  text-decoration: underline;
}

/* === RESPONSIVE === */
@media (max-width: 480px) {
  .notfound-card { padding: 40px 24px; }
  .big-404 { font-size: 80px; }
  .globe-wrap { width: 76px; height: 76px; }
  .globe-wrap i { font-size: 36px; }
  h1 { font-size: 22px; }
  .actions { flex-direction: column; }
  .btn-primary, .btn-secondary { justify-content: center; }
}
</style>
