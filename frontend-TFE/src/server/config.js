
// Adresse du serveur Symfony, lue dans les fichiers .env de Vite :
// - en développement (.env.development) : http://127.0.0.1:8000
// - en production (.env.production) : vide → même adresse que le site (/api/...)
export const BASE_URL = import.meta.env.VITE_API_URL ?? "http://127.0.0.1:8000"; // lien du serveur
export const URL_FOLDER_ANOUNCEMENT = `${BASE_URL}/upload/anoucement`; // acceder  au repertoire des photos des announces
