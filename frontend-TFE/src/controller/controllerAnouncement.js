import axios from "axios";
import { BASE_URL } from "../server/config.js";

export async function createAnouncement(payload, pictures, token) {
  const formData = new FormData();

  formData.append("title",       payload.title);
  formData.append("categorie",   payload.categorie);
  formData.append("description", payload.description);
  formData.append("adress",      payload.adress);
  formData.append("postX",       payload.postX ?? 0);
  formData.append("postY",       payload.postY ?? 0);
  formData.append("postZ",       payload.postZ ?? 0);

  pictures.forEach(file => formData.append("pictures[]", file));

  try {
    const response = await axios.post(`${BASE_URL}/api/anouncement`, formData, {
      headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "multipart/form-data",
      },
    });
    return response.data;
  } catch (error) {
    const msg = error.response?.data?.error;
    throw new Error(msg || "Erreur lors de la publication de l'annonce");
  }
}

export async function fetchAnouncementById(id, token = null) {
  try {
    const headers = token ? { Authorization: `Bearer ${token}` } : {};
    const response = await axios.get(`${BASE_URL}/api/anouncement/${id}`, { headers });
    return response.data;
  } catch (error) {
    const msg = error.response?.data?.error;
    throw new Error(msg || "Annonce introuvable");
  }
}

export async function fetchAnouncements(token = null) {
  try {
    const headers = token ? { Authorization: `Bearer ${token}` } : {};
    const response = await axios.get(`${BASE_URL}/api/anouncements`, { headers });
    return response.data;
  } catch (error) {
    throw new Error("Erreur lors de la récupération des annonces");
  }
}

export async function fetchMyAnouncements(token) {
  try {
    const response = await axios.get(`${BASE_URL}/api/my-anouncements`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return response.data;
  } catch (error) {
    throw new Error("Erreur lors de la récupération de vos annonces");
  }
}

export async function fetchRequesters(id, token) {
  try {
    const response = await axios.get(`${BASE_URL}/api/anouncement/${id}/requesters`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return response.data;
  } catch (error) {
    throw new Error("Erreur lors de la récupération des demandeurs");
  }
}

export async function reserveAnouncement(id, reservedForUserId, token) {
  try {
    const response = await axios.patch(
      `${BASE_URL}/api/anouncement/${id}/reserve`,
      { reservedForUserId },
      { headers: { Authorization: `Bearer ${token}` } }
    );
    return response.data;
  } catch (error) {
    const msg = error.response?.data?.error;
    throw new Error(msg || "Erreur lors de la réservation");
  }
}

export async function validateRecovery(id, token) {
  try {
    const response = await axios.post(
      `${BASE_URL}/api/anouncement/${id}/validate`,
      {},
      { headers: { Authorization: `Bearer ${token}` } }
    );
    return response.data;
  } catch (error) {
    const msg = error.response?.data?.error;
    throw new Error(msg || "Erreur lors de la validation");
  }
}

export async function fetchPendingRecoveries(token) {
  try {
    const response = await axios.get(`${BASE_URL}/api/my-pending-recoveries`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return response.data;
  } catch (error) {
    throw new Error("Erreur lors de la récupération des annonces en attente");
  }
}

export async function fetchMyRecoveredArticles(token) {
  try {
    const response = await axios.get(`${BASE_URL}/api/my-recovered-articles`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return response.data;
  } catch (error) {
    throw new Error("Erreur lors de la récupération des articles récupérés");
  }
}

export async function deleteAnouncement(id, token) {
  try {
    const response = await axios.delete(`${BASE_URL}/api/anouncement/${id}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return response.data;
  } catch (error) {
    const msg = error.response?.data?.error;
    throw new Error(msg || "Erreur lors de la suppression");
  }
}
