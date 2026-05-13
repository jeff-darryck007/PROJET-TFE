import axios from "axios";
import { BASE_URL } from "../server/config.js";

const headers = (token) => ({ Authorization: `Bearer ${token}` });

export async function toggleFavorite(anouncementId, token) {
  const res = await axios.post(`${BASE_URL}/api/favorite/toggle/${anouncementId}`, {}, { headers: headers(token) });
  return res.data; // { liked: true|false }
}

export async function fetchFavorites(token) {
  const res = await axios.get(`${BASE_URL}/api/favorites`, { headers: headers(token) });
  return res.data; // { favorites: [...], likedIds: [...] }
}

export async function fetchLikedIds(token) {
  const res = await axios.get(`${BASE_URL}/api/favorites/ids`, { headers: headers(token) });
  return res.data.likedIds ?? [];
}
