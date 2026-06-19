import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.DEV ? 'http://localhost/Inventory (4)/Inventory/api' : '/Inventory/api',
  headers: {
    'Content-Type': 'application/json',
  },
});

export default api;
