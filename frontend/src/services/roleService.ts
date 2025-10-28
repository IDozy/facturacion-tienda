import axios from "axios";

const API_URL = "/api/roles";

export const roleService = {
  getAll: () => axios.get(API_URL)
};
