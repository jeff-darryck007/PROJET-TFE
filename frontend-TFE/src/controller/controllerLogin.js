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

    return response.data;
  } catch (error) {
    if (error.response) {
      const msg = error.response.data?.error;
      throw new Error(msg || "Erreur serveur");
    }
    throw new Error("Impossible de joindre le serveur");
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

    return response.data;
  } catch (error) {
    if (error.response && error.response.status === 401) {
      throw new Error("Identifiants invalides");
    } else {
      throw new Error("Erreur serveur ou réseau");
    }
  }
}

// fonction pour recuperer les informations de l'utilisateur connecté
export async function fetchUserProfile(token) {
  try {
    const response = await axios.get(`${BASE_URL}/api/me`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    return response.data;
  } catch (error) {
    throw new Error("Erreur lors de la récupération du profil utilisateur");
  }
}