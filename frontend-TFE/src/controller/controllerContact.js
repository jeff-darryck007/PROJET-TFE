import axios from "axios";
import { BASE_URL } from "../server/config.js";

export async function sendContactMessage({ subject, message }, token) {
  const res = await axios.post(
    `${BASE_URL}/api/contact`,
    { subject, message },
    { headers: { Authorization: `Bearer ${token}` } }
  );
  return res.data;
}
