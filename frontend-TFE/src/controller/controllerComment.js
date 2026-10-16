import axios from "axios";
import { BASE_URL } from "../server/config.js";

export async function sendComment(anouncementId, contenue, token, threadUserId = null) {
  try {
    const response = await axios.post(
      `${BASE_URL}/api/comment`,
      { anouncementId, contenue, threadUserId },
      { headers: { Authorization: `Bearer ${token}` } }
    );
    return response.data;
  } catch (error) {
    const msg = error.response?.data?.error;
    throw new Error(msg || "Erreur lors de l'envoi du message");
  }
}

// afterId : ne récupérer que les messages plus récents que cet id (rafraîchissement automatique)
export async function fetchComments(anouncementId, token, threadUserId = null, afterId = null) {
  const params = {};
  if (threadUserId) params.threadUserId = threadUserId;
  if (afterId) params.afterId = afterId;
  const response = await axios.get(
    `${BASE_URL}/api/anouncement/${anouncementId}/comments`,
    { headers: { Authorization: `Bearer ${token}` }, params }
  );
  return response.data;
}

export async function fetchPublicComments(anouncementId, token) {
  const response = await axios.get(
    `${BASE_URL}/api/anouncement/${anouncementId}/public-comments`,
    { headers: { Authorization: `Bearer ${token}` } }
  );
  return response.data;
}

export async function sendPublicComment(anouncementId, contenue, token) {
  try {
    const response = await axios.post(
      `${BASE_URL}/api/anouncement/${anouncementId}/public-comment`,
      { contenue },
      { headers: { Authorization: `Bearer ${token}` } }
    );
    return response.data;
  } catch (error) {
    const msg = error.response?.data?.error;
    throw new Error(msg || "Erreur lors de l'envoi du commentaire");
  }
}

export async function reportComment(commentId, token) {
  try {
    const response = await axios.post(
      `${BASE_URL}/api/comment/${commentId}/report`,
      {},
      { headers: { Authorization: `Bearer ${token}` } }
    );
    return response.data;
  } catch (error) {
    const msg = error.response?.data?.error;
    throw new Error(msg || "Erreur lors du signalement");
  }
}

export async function fetchReportedComments(token) {
  try {
    const response = await axios.get(`${BASE_URL}/api/admin/reported-comments`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return response.data;
  } catch (error) {
    throw new Error("Erreur lors du chargement des commentaires signalés");
  }
}

export async function deleteAdminComment(id, token) {
  try {
    const response = await axios.delete(`${BASE_URL}/api/admin/comment/${id}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return response.data;
  } catch (error) {
    const msg = error.response?.data?.error;
    throw new Error(msg || "Erreur lors de la suppression");
  }
}

export async function fetchMyConversations(token) {
  const response = await axios.get(
    `${BASE_URL}/api/my-conversations`,
    { headers: { Authorization: `Bearer ${token}` } }
  );
  return response.data;
}
