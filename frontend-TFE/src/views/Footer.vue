<template>
  <footer class="site-footer">

    <div class="footer-main">

      <!-- COLONNE 1 : Marque -->
      <div class="footer-col footer-brand">
        <div class="brand-logo">
          <i class="fas fa-hands-helping"></i>
          <span>PartageGratuit</span>
        </div>
        <p class="brand-desc">
          Donnez une seconde vie à vos objets et trouvez des articles gratuits près de chez vous. 100 % gratuit, 100 % local.
        </p>
        <div class="social-links">
          <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
          <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
          <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
        </div>
      </div>

      <!-- COLONNE 2 : Navigation -->
      <div class="footer-col">
        <h4>Navigation</h4>
        <ul>
          <li><router-link to="/"><i class="fas fa-home"></i> Accueil</router-link></li>
          <li><router-link to="/PublierView"><i class="fas fa-plus-circle"></i> Publier un article</router-link></li>
          <li><router-link to="/FavorisView"><i class="fas fa-heart"></i> Mes favoris</router-link></li>
          <li><router-link to="/MessageView"><i class="fas fa-envelope"></i> Messages</router-link></li>
          <li><router-link to="/ContactView"><i class="fas fa-paper-plane"></i> Contact</router-link></li>
        </ul>
      </div>

      <!-- COLONNE 3 : À propos -->
      <div class="footer-col">
        <h4>À propos</h4>
        <ul>
          <li><a href="#"><i class="fas fa-info-circle"></i> Notre mission</a></li>
          <li><a href="#"><i class="fas fa-question-circle"></i> Aide & FAQ</a></li>
          <li><a href="#"><i class="fas fa-sitemap"></i> Plan du site</a></li>
          <li><a href="#"><i class="fas fa-handshake"></i> Partenaires</a></li>
          <li><a href="#"><i class="fas fa-shield-alt"></i> Sécurité</a></li>
        </ul>
      </div>

      <!-- COLONNE 4 : Légal -->
      <div class="footer-col">
        <h4>Légal</h4>
        <ul>
          <li><a href="#"><i class="fas fa-file-contract"></i> Conditions d'utilisation</a></li>
          <li><a href="#"><i class="fas fa-user-shield"></i> Politique de confidentialité</a></li>
          <li><a href="#"><i class="fas fa-cookie-bite"></i> Gestion des cookies</a></li>
          <li><a href="#"><i class="fas fa-balance-scale"></i> Mentions légales</a></li>
        </ul>
      </div>

    </div>

    <!-- BARRE DU BAS -->
    <div class="footer-bottom">
      <p class="copyright">
        &copy; {{ currentYear }} PartageGratuit — Tous droits réservés.
      </p>
      <p class="disclaimer">
        PartageGratuit n'est pas responsable des dommages résultant de l'utilisation de ce site.
      </p>
    </div>

  </footer>
</template>

<script setup>
import '@fortawesome/fontawesome-free/css/all.css'

const currentYear = new Date().getFullYear()
</script>

<style scoped>
.site-footer {
  background: #0a2a5e;
  color: #cdd8f0;
  font-family: Arial, sans-serif;
}

/* === SECTION PRINCIPALE === */
.footer-main {
  display: flex;
  gap: 40px;
  padding: 56px 60px 40px;
  max-width: 1400px;
  margin: 0 auto;
  flex-wrap: wrap;
}

/* === COLONNES === */
.footer-col {
  flex: 1;
  min-width: 180px;
}

/* === MARQUE === */
.footer-brand {
  flex: 1.6;
  min-width: 220px;
}

.brand-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
}

.brand-logo i {
  font-size: 26px;
  color: #f1b800;
}

.brand-logo span {
  font-size: 20px;
  font-weight: 800;
  color: white;
  letter-spacing: -0.3px;
}

.brand-desc {
  font-size: 14px;
  line-height: 1.7;
  color: #a8bcd8;
  margin: 0 0 20px 0;
  max-width: 280px;
}

/* === RÉSEAUX SOCIAUX === */
.social-links {
  display: flex;
  gap: 10px;
}

.social-links a {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #a8bcd8;
  font-size: 14px;
  transition: background 0.2s, color 0.2s, transform 0.2s;
  text-decoration: none;
}

.social-links a:hover {
  background: #f1b800;
  border-color: #f1b800;
  color: #222;
  transform: translateY(-2px);
}

/* === TITRES DE COLONNES === */
.footer-col h4 {
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: white;
  margin: 0 0 18px 0;
  padding-bottom: 10px;
  border-bottom: 2px solid #f1b800;
  display: inline-block;
}

/* === LISTES === */
.footer-col ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.footer-col ul li a {
  color: #a8bcd8;
  text-decoration: none;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: color 0.2s, gap 0.2s;
}

.footer-col ul li a i {
  font-size: 12px;
  width: 14px;
  text-align: center;
  color: #f1b800;
  opacity: 0.7;
  transition: opacity 0.2s;
}

.footer-col ul li a:hover {
  color: #f1b800;
  gap: 12px;
}

.footer-col ul li a:hover i {
  opacity: 1;
}

/* === BARRE DU BAS === */
.footer-bottom {
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding: 20px 60px;
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.copyright {
  font-size: 13px;
  color: #a8bcd8;
  margin: 0;
  font-weight: 600;
}

.disclaimer {
  font-size: 12px;
  color: #6a80a0;
  margin: 0;
}

/* === RESPONSIVE === */
@media (max-width: 900px) {
  .footer-main {
    padding: 40px 32px 32px;
    gap: 32px;
  }

  .footer-bottom {
    padding: 18px 32px;
    flex-direction: column;
    text-align: center;
  }
}

@media (max-width: 600px) {
  .footer-main {
    padding: 32px 20px 24px;
    gap: 28px;
  }

  .footer-col {
    min-width: 100%;
  }

  .footer-bottom {
    padding: 16px 20px;
  }

  .brand-desc {
    max-width: 100%;
  }
}
</style>
