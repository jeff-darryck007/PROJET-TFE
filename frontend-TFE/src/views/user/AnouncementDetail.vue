<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Navbar from '../Navbar.vue'
import Footer from '../Footer.vue'
import '@fortawesome/fontawesome-free/css/all.css'
import { fetchAnouncementById, deleteAnouncement } from '../../controller/controllerAnouncement.js'
import { toggleFavorite, fetchLikedIds } from '../../controller/controllerFavorite.js'
import { fetchPublicComments, sendPublicComment, reportComment } from '../../controller/controllerComment.js'
import { URL_FOLDER_ANOUNCEMENT } from '../../server/config.js'

const route  = useRoute()
const router = useRouter()
const token         = localStorage.getItem('token')
const currentUserId = parseInt(localStorage.getItem('idUser') ?? '0')

const anouncement = ref(null)
const loading     = ref(true)
const error       = ref('')
const activeImg   = ref(0)
const liked        = ref(false)
const favLoading   = ref(false)
const deleting     = ref(false)
const confirmDel   = ref(false)

const publicComments    = ref([])
const newComment        = ref('')
const sendingComment    = ref(false)
const commentError      = ref('')
const isLoggedIn        = ref(!!token)
const reportConfirmId   = ref(null)
const reportedIds       = ref(new Set())

const STATUS_LABEL = { 1: 'Disponible', 2: 'Réservé', 3: 'Donné' }

onMounted(async () => {
  const id = parseInt(route.params.id)
  try {
    anouncement.value = await fetchAnouncementById(id, token)
    if (token) {
      const [ids, commentsData] = await Promise.all([
        fetchLikedIds(token),
        fetchPublicComments(id, token),
      ])
      liked.value = ids.includes(id)
      publicComments.value = commentsData.comments ?? []
    }
  } catch (e) {
    error.value = e.message || 'Annonce introuvable.'
  } finally {
    loading.value = false
  }
})

async function submitComment() {
  if (!newComment.value.trim()) return
  sendingComment.value = true
  commentError.value = ''
  try {
    const c = await sendPublicComment(anouncement.value.id, newComment.value.trim(), token)
    publicComments.value.push(c)
    newComment.value = ''
  } catch (e) {
    commentError.value = e.message || 'Erreur lors de l\'envoi.'
  } finally {
    sendingComment.value = false
  }
}

function formatDateTime(str) {
  if (!str) return ''
  const d = new Date(str)
  if (isNaN(d)) return str
  return d.toLocaleDateString('fr-BE', { day: '2-digit', month: 'short', year: 'numeric' }) +
    ' à ' + d.toLocaleTimeString('fr-BE', { hour: '2-digit', minute: '2-digit' })
}

function commentInitials(name) {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
}

async function handleReport(commentId) {
  if (reportedIds.value.has(commentId)) return
  if (reportConfirmId.value !== commentId) {
    reportConfirmId.value = commentId
    setTimeout(() => { if (reportConfirmId.value === commentId) reportConfirmId.value = null }, 4000)
    return
  }
  try {
    await reportComment(commentId, token)
    reportedIds.value = new Set([...reportedIds.value, commentId])
  } catch { /* silencieux */ }
  finally { reportConfirmId.value = null }
}

const pictures = computed(() => {
  const pics = anouncement.value?.pictures ?? []
  return pics.map(p => `${URL_FOLDER_ANOUNCEMENT}/${p}`)
})

const hasPictures = computed(() => pictures.value.length > 0)
const isOwner     = computed(() => !!currentUserId && anouncement.value?.userId === currentUserId)

function prevImg() {
  activeImg.value = (activeImg.value - 1 + pictures.value.length) % pictures.value.length
}
function nextImg() {
  activeImg.value = (activeImg.value + 1) % pictures.value.length
}

function formatDate(str) {
  if (!str) return ''
  const d = new Date(str)
  if (isNaN(d)) return str
  return d.toLocaleDateString('fr-BE', { day: '2-digit', month: 'long', year: 'numeric' })
}

function donorInitials(name) {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
}

async function handleFavorite() {
  if (!token) { router.push('/login'); return }
  favLoading.value = true
  try {
    const res = await toggleFavorite(anouncement.value.id, token)
    liked.value = res.liked
  } catch { /* silencieux */ }
  finally { favLoading.value = false }
}

function contactDonor() {
  if (!token) { router.push('/login'); return }
  router.push({ name: 'Message-View', query: { annonce: anouncement.value.id } })
}

async function handleDelete() {
  if (!confirmDel.value) { confirmDel.value = true; return }
  deleting.value = true
  try {
    await deleteAnouncement(anouncement.value.id, token)
    router.push('/')
  } catch (e) {
    error.value = e.message || 'Erreur lors de la suppression.'
    confirmDel.value = false
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <div class="page">
    <Navbar />

    <div class="wrapper">

      <!-- LOADING -->
      <div v-if="loading" class="state-box">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Chargement de l'annonce...</p>
      </div>

      <!-- ERREUR -->
      <div v-else-if="error" class="state-box error">
        <i class="fas fa-exclamation-circle"></i>
        <p>{{ error }}</p>
        <button class="btn-back" @click="router.back()">
          <i class="fas fa-arrow-left"></i> Retour
        </button>
      </div>

      <!-- CONTENU -->
      <template v-else-if="anouncement">

        <!-- FIL D'ARIANE -->
        <nav class="breadcrumb">
          <router-link to="/">Accueil</router-link>
          <i class="fas fa-chevron-right"></i>
          <span>{{ anouncement.categorie }}</span>
          <i class="fas fa-chevron-right"></i>
          <span class="current">{{ anouncement.title }}</span>
        </nav>

        <div class="detail-layout">

          <!-- COLONNE GAUCHE : galerie + infos -->
          <div class="col-main">

            <!-- GALERIE -->
            <div class="gallery">
              <div v-if="hasPictures" class="gallery-main">
                <img :src="pictures[activeImg]" :alt="anouncement.title" class="main-img" />
                <button v-if="pictures.length > 1" class="nav-btn prev" @click="prevImg">
                  <i class="fas fa-chevron-left"></i>
                </button>
                <button v-if="pictures.length > 1" class="nav-btn next" @click="nextImg">
                  <i class="fas fa-chevron-right"></i>
                </button>
                <span class="img-counter" v-if="pictures.length > 1">
                  {{ activeImg + 1 }} / {{ pictures.length }}
                </span>
              </div>
              <div v-else class="no-img">
                <i class="fas fa-image"></i>
                <p>Aucune photo disponible</p>
              </div>

              <!-- MINIATURES -->
              <div v-if="pictures.length > 1" class="thumbnails">
                <img
                  v-for="(pic, i) in pictures"
                  :key="i"
                  :src="pic"
                  :alt="`Photo ${i + 1}`"
                  :class="['thumb', { active: i === activeImg }]"
                  @click="activeImg = i"
                />
              </div>
            </div>

            <!-- DESCRIPTION -->
            <div class="card-section">
              <h3><i class="fas fa-align-left"></i> Description</h3>
              <p class="description">{{ anouncement.description }}</p>
            </div>

            <!-- LOCALISATION -->
            <div class="card-section">
              <h3><i class="fas fa-map-marker-alt"></i> Localisation</h3>
              <p class="address">{{ anouncement.adress }}</p>
            </div>

          </div>

          <!-- COLONNE DROITE : actions -->
          <div class="col-side">

            <!-- TITRE + STATUT -->
            <div class="card-section info-card">
              <span class="status-badge" :class="`badge-${anouncement.status}`">
                {{ STATUS_LABEL[anouncement.status] ?? 'Inconnu' }}
              </span>
              <span class="category-tag">{{ anouncement.categorie }}</span>
              <h1 class="title">{{ anouncement.title }}</h1>
              <p class="meta-date">
                <i class="fas fa-calendar-alt"></i>
                Publié le {{ formatDate(anouncement.createAt) }}
              </p>
            </div>

            <!-- DONNEUR -->
            <div class="card-section donor-card">
              <h3>Proposé par</h3>
              <div class="donor-row">
                <div class="donor-avatar">{{ donorInitials(anouncement.donorName) }}</div>
                <div class="donor-info">
                  <p class="donor-name">{{ anouncement.donorName }}</p>
                  <p class="donor-label">Membre PartageGratuit</p>
                </div>
              </div>
            </div>

            <!-- ACTIONS -->
            <div class="card-section actions-card">

              <!-- Propriétaire : supprimer l'annonce -->
              <template v-if="isOwner">
                <button
                  class="btn-delete"
                  :disabled="deleting"
                  @click="handleDelete"
                >
                  <i :class="deleting ? 'fas fa-spinner fa-spin' : 'fas fa-trash-alt'"></i>
                  {{ confirmDel ? 'Confirmer la suppression ?' : 'Supprimer l\'annonce' }}
                </button>
                <p v-if="confirmDel" class="del-hint">Cliquez à nouveau pour confirmer.</p>
              </template>

              <!-- Non-propriétaire : contacter le donneur -->
              <template v-else>
                <button
                  class="btn-contact"
                  :disabled="anouncement.status !== 1"
                  @click="contactDonor"
                >
                  <i class="fas fa-envelope"></i>
                  {{ anouncement.status === 1 ? 'Contacter le donneur' : STATUS_LABEL[anouncement.status] }}
                </button>
              </template>

              <button
                class="btn-fav"
                :class="{ active: liked }"
                :disabled="favLoading"
                @click="handleFavorite"
              >
                <i :class="liked ? 'fas fa-heart' : 'far fa-heart'"></i>
                {{ liked ? 'Retirer des favoris' : 'Ajouter aux favoris' }}
              </button>

              <button class="btn-back-link" @click="router.back()">
                <i class="fas fa-arrow-left"></i> Retour aux annonces
              </button>
            </div>

            <!-- INFOS SUPPLÉMENTAIRES -->
            <div class="card-section extra-info">
              <h3>Informations</h3>
              <ul>
                <li><i class="fas fa-tag"></i> <span>Catégorie :</span> {{ anouncement.categorie }}</li>
                <li><i class="fas fa-circle-check"></i> <span>État :</span> {{ STATUS_LABEL[anouncement.status] ?? 'Inconnu' }}</li>
                <li><i class="fas fa-map-pin"></i> <span>Adresse :</span> {{ anouncement.adress }}</li>
                <li><i class="fas fa-ban"></i> <span>Prix :</span> Gratuit</li>
              </ul>
            </div>

          </div>
        </div>

        <!-- SECTION COMMENTAIRES (utilisateurs connectés uniquement) -->
        <div v-if="isLoggedIn" class="comments-section">
          <h3 class="comments-title">
            <i class="fas fa-comments"></i>
            Commentaires
            <span class="comments-count">{{ publicComments.length }}</span>
          </h3>

          <!-- LISTE -->
          <div v-if="publicComments.length === 0" class="comments-empty">
            <i class="fas fa-comment-slash"></i>
            <p>Aucun commentaire pour le moment. Soyez le premier !</p>
          </div>

          <div v-else class="comments-list">
            <div v-for="c in publicComments" :key="c.id" class="comment-item">
              <div class="comment-avatar">{{ commentInitials(c.userName) }}</div>
              <div class="comment-body">
                <div class="comment-header">
                  <span class="comment-author">{{ c.userName }}</span>
                  <span v-if="c.isAuthor" class="badge-auteur">Auteur</span>
                  <span class="comment-date">{{ formatDateTime(c.date) }}</span>
                  <button
                    v-if="c.userId !== currentUserId"
                    class="btn-report"
                    :class="{ reported: reportedIds.has(c.id), confirm: reportConfirmId === c.id }"
                    :disabled="reportedIds.has(c.id)"
                    :title="reportedIds.has(c.id) ? 'Déjà signalé' : 'Signaler ce commentaire'"
                    @click="handleReport(c.id)"
                  >
                    <i class="fas fa-flag"></i>
                    <span v-if="reportedIds.has(c.id)">Signalé</span>
                    <span v-else-if="reportConfirmId === c.id">Confirmer ?</span>
                  </button>
                </div>
                <p class="comment-text">{{ c.contenue }}</p>
              </div>
            </div>
          </div>

          <!-- FORMULAIRE -->
          <div class="comment-form">
            <div v-if="commentError" class="comment-error">
              <i class="fas fa-exclamation-circle"></i> {{ commentError }}
            </div>
            <textarea
              v-model="newComment"
              class="comment-input"
              placeholder="Laisser un commentaire..."
              rows="3"
              maxlength="1000"
            ></textarea>
            <div class="comment-form-footer">
              <span class="comment-chars">{{ newComment.length }} / 1000</span>
              <button
                class="comment-submit"
                :disabled="sendingComment || !newComment.trim()"
                @click="submitComment"
              >
                <i :class="sendingComment ? 'fas fa-spinner fa-spin' : 'fas fa-paper-plane'"></i>
                {{ sendingComment ? 'Envoi...' : 'Publier' }}
              </button>
            </div>
          </div>
        </div>

      </template>
    </div>

    <Footer />
  </div>
</template>

<style scoped>
.page {
  background: #f4f6f9;
  min-height: 100vh;
  font-family: Arial, sans-serif;
}

.wrapper {
  max-width: 1200px;
  margin: 0 auto;
  padding: 28px 20px 60px;
}

/* ÉTAT */
.state-box {
  text-align: center;
  padding: 80px 20px;
  color: #888;
}
.state-box i { font-size: 44px; display: block; margin-bottom: 16px; }
.state-box p { font-size: 16px; margin-bottom: 20px; }
.state-box.error { color: #dc2626; }

.btn-back {
  padding: 10px 22px;
  background: #0054a6;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

/* FIL D'ARIANE */
.breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #888;
  margin-bottom: 22px;
  flex-wrap: wrap;
}
.breadcrumb a { color: #0054a6; text-decoration: none; }
.breadcrumb a:hover { text-decoration: underline; }
.breadcrumb i { font-size: 10px; color: #bbb; }
.breadcrumb .current { color: #444; font-weight: 600; }

/* LAYOUT PRINCIPAL */
.detail-layout {
  display: grid;
  grid-template-columns: 1fr 360px;
  gap: 24px;
  align-items: start;
}

/* SECTIONS */
.card-section {
  background: white;
  border-radius: 14px;
  padding: 22px 24px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
  margin-bottom: 20px;
}
.card-section h3 {
  font-size: 15px;
  font-weight: 700;
  color: #333;
  margin: 0 0 14px 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* GALERIE */
.gallery { margin-bottom: 20px; }

.gallery-main {
  position: relative;
  border-radius: 14px;
  overflow: hidden;
  background: #1a1a2e;
  height: 400px;
}
.main-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.nav-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(0,0,0,0.45);
  color: white;
  border: none;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  font-size: 16px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}
.nav-btn:hover { background: rgba(0,0,0,0.7); }
.nav-btn.prev { left: 12px; }
.nav-btn.next { right: 12px; }

.img-counter {
  position: absolute;
  bottom: 12px;
  right: 14px;
  background: rgba(0,0,0,0.5);
  color: white;
  font-size: 12px;
  padding: 3px 10px;
  border-radius: 20px;
}

.thumbnails {
  display: flex;
  gap: 10px;
  margin-top: 12px;
  flex-wrap: wrap;
}
.thumb {
  width: 72px;
  height: 60px;
  object-fit: cover;
  border-radius: 8px;
  cursor: pointer;
  border: 2px solid transparent;
  transition: border-color 0.2s, opacity 0.2s;
  opacity: 0.65;
}
.thumb.active { border-color: #0054a6; opacity: 1; }
.thumb:hover { opacity: 1; }

.no-img {
  height: 300px;
  background: #eef2f7;
  border-radius: 14px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #ccc;
  gap: 12px;
}
.no-img i { font-size: 48px; }
.no-img p { font-size: 14px; }

/* DESCRIPTION */
.description {
  font-size: 15px;
  color: #444;
  line-height: 1.7;
  margin: 0;
  white-space: pre-line;
}

/* ADRESSE */
.address {
  font-size: 14px;
  color: #555;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* INFOS CARD */
.info-card {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  align-self: flex-start;
}
.badge-1 { background: #dcfce7; color: #16a34a; }
.badge-2 { background: #fef9c3; color: #ca8a04; }
.badge-3 { background: #fee2e2; color: #dc2626; }

.category-tag {
  display: inline-block;
  background: #eef4ff;
  color: #0054a6;
  font-size: 12px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 20px;
  text-transform: uppercase;
  align-self: flex-start;
}

.title {
  font-size: 22px;
  font-weight: 800;
  color: #1a1a2e;
  margin: 4px 0 0;
  line-height: 1.3;
}

.meta-date {
  font-size: 13px;
  color: #999;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 6px;
}
.meta-date i { color: #0054a6; }

/* DONNEUR */
.donor-card h3 { margin-bottom: 12px; }
.donor-row {
  display: flex;
  align-items: center;
  gap: 14px;
}
.donor-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, #0054a6, #1a72cc);
  color: white;
  font-size: 16px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.donor-name { font-size: 15px; font-weight: 600; color: #222; margin: 0; }
.donor-label { font-size: 12px; color: #999; margin: 2px 0 0; }

/* BOUTONS ACTIONS */
.actions-card {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.btn-contact {
  width: 100%;
  padding: 13px;
  background: #0054a6;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  transition: background 0.2s;
}
.btn-contact:hover:not(:disabled) { background: #003f7d; }
.btn-contact:disabled { background: #9ab3d4; cursor: not-allowed; }

.btn-fav {
  width: 100%;
  padding: 11px;
  background: white;
  color: #555;
  border: 1.5px solid #ddd;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  transition: all 0.2s;
}
.btn-fav:hover { border-color: #e53e3e; color: #e53e3e; }
.btn-fav.active { border-color: #e53e3e; color: #e53e3e; background: #fff5f5; }
.btn-fav:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-back-link {
  background: none;
  border: none;
  color: #888;
  font-size: 13px;
  cursor: pointer;
  font-family: inherit;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 6px;
  transition: color 0.2s;
}
.btn-back-link:hover { color: #0054a6; }

.btn-delete {
  width: 100%;
  padding: 13px;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  transition: background 0.2s;
}
.btn-delete:hover:not(:disabled) { background: #b91c1c; }
.btn-delete:disabled { opacity: 0.7; cursor: not-allowed; }

.del-hint {
  font-size: 12px;
  color: #dc2626;
  text-align: center;
  margin: 0;
}

/* EXTRA INFO */
.extra-info h3 { margin-bottom: 14px; }
.extra-info ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.extra-info li {
  font-size: 14px;
  color: #555;
  display: flex;
  align-items: center;
  gap: 10px;
}
.extra-info li i { color: #0054a6; width: 16px; text-align: center; }
.extra-info li span { font-weight: 600; color: #333; }

/* ===== COMMENTAIRES ===== */
.comments-section {
  background: white;
  border-radius: 14px;
  padding: 24px 28px 28px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
  margin-top: 28px;
}

.comments-title {
  font-size: 17px;
  font-weight: 700;
  color: #333;
  margin: 0 0 20px 0;
  display: flex;
  align-items: center;
  gap: 10px;
}
.comments-title i { color: #0054a6; }

.comments-count {
  background: #eef4ff;
  color: #0054a6;
  font-size: 13px;
  font-weight: 700;
  padding: 2px 10px;
  border-radius: 20px;
}

.comments-empty {
  text-align: center;
  padding: 30px 20px;
  color: #bbb;
  font-size: 14px;
}
.comments-empty i { font-size: 28px; display: block; margin-bottom: 10px; }

.comments-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 24px;
}

.comment-item {
  display: flex;
  gap: 14px;
  align-items: flex-start;
}

.comment-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #0054a6, #1a72cc);
  color: white;
  font-size: 13px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.comment-body {
  flex: 1;
  background: #f8faff;
  border: 1px solid #e8edf5;
  border-radius: 10px;
  padding: 12px 14px;
}

.comment-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
  flex-wrap: wrap;
}

.comment-author {
  font-weight: 700;
  font-size: 14px;
  color: #222;
}

.badge-auteur {
  background: #0054a6;
  color: white;
  font-size: 10px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 20px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.comment-date {
  font-size: 12px;
  color: #aaa;
  margin-left: auto;
}

.btn-report {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 9px;
  border: 1px solid #e8ecf0;
  border-radius: 20px;
  background: white;
  color: #bbb;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.2s;
  flex-shrink: 0;
}
.btn-report:hover:not(:disabled) { border-color: #f97316; color: #f97316; background: #fff7ed; }
.btn-report.confirm { border-color: #dc2626; color: #dc2626; background: #fee2e2; }
.btn-report.reported { border-color: #d1d5db; color: #9ca3af; background: #f9fafb; cursor: not-allowed; }

.comment-text {
  font-size: 14px;
  color: #444;
  margin: 0;
  line-height: 1.6;
  white-space: pre-line;
}

/* FORMULAIRE */
.comment-form { border-top: 1px solid #f0f0f0; padding-top: 20px; }

.comment-error {
  background: #fee2e2;
  color: #dc2626;
  border: 1px solid #fca5a5;
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 13px;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.comment-input {
  width: 100%;
  padding: 12px 14px;
  border: 2px solid #e8ecf0;
  border-radius: 10px;
  font-size: 14px;
  font-family: inherit;
  resize: vertical;
  outline: none;
  transition: border-color 0.2s;
  box-sizing: border-box;
}
.comment-input:focus { border-color: #0054a6; }

.comment-form-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 10px;
}

.comment-chars { font-size: 12px; color: #bbb; }

.comment-submit {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 22px;
  background: #0054a6;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
  transition: background 0.2s;
}
.comment-submit:hover:not(:disabled) { background: #003f7d; }
.comment-submit:disabled { background: #9ab3d4; cursor: not-allowed; }

/* RESPONSIVE */
@media (max-width: 900px) {
  .detail-layout {
    grid-template-columns: 1fr;
  }
  .col-side { order: -1; }
  .gallery-main { height: 280px; }
}

@media (max-width: 600px) {
  .wrapper { padding: 16px 14px 40px; }
  .title { font-size: 18px; }
  .gallery-main { height: 220px; }
}
</style>
