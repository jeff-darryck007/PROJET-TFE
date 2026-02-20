import axios from "axios";
import { BASE_URL } from "../server/config.js";

// fonction pour gérer l'inscription de l'utilisateur
export async function registerUser(email, password, name, surname, role) {
  if (!name || !surname || !email || !password || !role) {
    throw new Error("Tous les champs sont obligatoires");
  } 

  try {
    const response = await axios.post(`${BASE_URL}/api/register`, {
      name,
      surname,
      email,
      password,
      role
    });

    return response.data; // renvoie la réponse au composant Vue
  } catch (error) {
    if (error.response && error.response.status === 409) {
      throw new Error("Email déjà utilisé");
    } else {
      throw new Error("Erreur serveur ou réseau");
    }
  }
}

// fonction pour gérer la connexion de l'utilisateur
export async function loginUser(email, password) {
  if (!email || !password) {
    throw new Error("Email et mot de passe obligatoires");
  }

  try {
    const response = await axios.post(`${BASE_URL}/api/login`, {
      email,
      password,
    });

    // Tu peux ici gérer le stockage du token si tu veux :
    // localStorage.setItem("token", response.data.token);

    return response.data; // renvoie la réponse au composant Vue
  } catch (error) {
    if (error.response && error.response.status === 401) {
      throw new Error("Identifiants invalides");
    } else {
      throw new Error("Erreur serveur ou réseau");
    }
  }
}

// fonction pour recuperer les informaqtions de l'utilisateur connecté
export async function fetchUserProfile(token) {
  try {
    const response = await axios.get(`${BASE_URL}/api/get_user/${token}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    return response.data; // renvoie les données de l'utilisateur
  } catch (error) {
    throw new Error("Erreur lors de la récupération du profil utilisateur");
  }
}