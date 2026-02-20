<template>
  <footer class="custom-footer">

    <div class="footer-top">
      <a href="#">A donner Professionnel</a>
      <a href="#">Sûr et Réussi</a>
      <a href="#">Aide & Info</a>
      <a href="#">Conditions d'utilisation</a>
      <a href="#">Politique de confidentialité</a>
      <a href="#">Cookies</a>
      <a href="#">Préférences de confidentialité</a>
    </div>

    <div class="footer-middle">
      <a href="#">À propos de A donner</a>
      <a href="#">Plan du site</a>
      <a href="#">Nous contacter</a>
    </div>

    <div class="footer-bottom">
      <p>
        A donner n'est pas responsable des dommages résultant de l'utilisation de ce site.<br />
        Copyright © 2025 A donner — Tous droits réservés.
      </p>
    </div>

  </footer>
</template>

<script setup>
// Aucun script requis pour ce footer
</script>

<style scoped>
.custom-footer {
  background: #002A66; /* bleu foncé du thème */
  color: white;
  padding: 50px 20px; /* plus grand que version initiale */
  font-size: 16px;
  text-align: center;
  line-height: 1.8;
}

/* Liens */
.custom-footer a {
  color: #F1B800; /* jaune du thème */
  text-decoration: none;
  margin: 0 18px;
  font-weight: 600;
  font-size: 16px;
  transition: 0.2s ease-in-out;
}

.custom-footer a:hover {
  color: white;
  text-decoration: underline;
}

.footer-top,
.footer-middle {
  margin-bottom: 20px;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 12px;
}

/* Texte du bas */
.footer-bottom {
  font-size: 14px;
  color: #ddd;
  max-width: 1000px;
  margin: 0 auto;
  line-height: 1.8;
}

/* Responsive */
@media (max-width: 768px) {
  .custom-footer {
    padding: 40px 15px;
    font-size: 15px;
  }

  .custom-footer a {
    font-size: 15px;
    margin: 8px;
  }

  .footer-bottom {
    font-size: 13px;
    padding: 0 10px;
  }
}

@media (max-width: 480px) {
  .custom-footer {
    padding: 30px 10px;
    font-size: 14px;
  }

  .custom-footer a {
    display: block;
    margin: 6px 0;
    font-size: 14px;
  }

  .footer-bottom {
    font-size: 12px;
    padding: 0 10px;
  }
}
</style>
