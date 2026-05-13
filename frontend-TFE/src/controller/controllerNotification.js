import axios from "axios";
import { BASE_URL } from "../server/config.js";

const headers = (token) => ({ Authorization: `Bearer ${token}` });

export async function fetchNotifications(token) {
  const res = await axios.get(`${BASE_URL}/api/notifications`, { headers: headers(token) });
  return res.data;
}

export async function fetchUnreadCount(token) {
  const res = await axios.get(`${BASE_URL}/api/notifications/unread-count`, { headers: headers(token) });
  return res.data.count ?? 0;
}

export async function markAsRead(id, token) {
  const res = await axios.patch(`${BASE_URL}/api/notification/${id}/read`, {}, { headers: headers(token) });
  return res.data;
}

export async function markAllAsRead(token) {
  const res = await axios.patch(`${BASE_URL}/api/notifications/read-all`, {}, { headers: headers(token) });
  return res.data;
}

export async function deleteNotification(id, token) {
  const res = await axios.delete(`${BASE_URL}/api/notification/${id}`, { headers: headers(token) });
  return res.data;
}
