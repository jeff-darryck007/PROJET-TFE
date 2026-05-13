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
      const msg = error.response.data?.error;
      throw new Error(msg || "Adresse e-mail ou mot de passe incorrect.");
    } else {
      throw new Error("Erreur serveur ou réseau");
    }
  }
}

// fonction pour recuperer les informations de l'utilisateur connecté
export async function fetchUserProfile(token) {
  try {
    const response = await axios.get(`${BASE_URL}/api/me`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return response.data;
  } catch (error) {
    throw new Error("Erreur lors de la récupération du profil utilisateur");
  }
}

// modifier le profil (nom, prénom, email)
export async function updateProfile(data, token) {
  try {
    const response = await axios.patch(`${BASE_URL}/api/me`, data, {
      headers: { Authorization: `Bearer ${token}`, "Content-Type": "application/json" },
    });
    return response.data;
  } catch (error) {
    const msg = error.response?.data?.error;
    throw new Error(msg || "Erreur lors de la mise à jour du profil");
  }
}

// changer le mot de passe
export async function changePassword(oldPassword, newPassword, token) {
  try {
    const response = await axios.post(
      `${BASE_URL}/api/me/change-password`,
      { oldPassword, newPassword },
      { headers: { Authorization: `Bearer ${token}`, "Content-Type": "application/json" } }
    );
    return response.data;
  } catch (error) {
    const msg = error.response?.data?.error;
    throw new Error(msg || "Erreur lors du changement de mot de passe");
  }
}

export async function banUser(id, token) {
  try {
    const response = await axios.patch(`${BASE_URL}/api/admin/users/${id}/ban`, {}, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return response.data;
  } catch (error) {
    const msg = error.response?.data?.error;
    throw new Error(msg || "Erreur lors du bannissement");
  }
}

export async function unbanUser(id, token) {
  try {
    const response = await axios.patch(`${BASE_URL}/api/admin/users/${id}/unban`, {}, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return response.data;
  } catch (error) {
    const msg = error.response?.data?.error;
    throw new Error(msg || "Erreur lors du débannissement");
  }
}

export async function fetchAdminStats(token) {
  try {
    const response = await axios.get(`${BASE_URL}/api/admin/stats`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return response.data;
  } catch (error) {
    throw new Error("Erreur lors du chargement des statistiques");
  }
}

export async function fetchAdminUsers(token) {
  try {
    const response = await axios.get(`${BASE_URL}/api/admin/users`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return response.data;
  } catch (error) {
    const msg = error.response?.data?.error;
    throw new Error(msg || "Erreur lors de la récupération des utilisateurs");
  }
}