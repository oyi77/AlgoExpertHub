import axios from 'axios';

const api = axios.create({
    baseURL: '/api/admin',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
    },
    withCredentials: true,
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401 || error.response?.status === 419) {
            window.location.href = '/admin/login';
        }
        return Promise.reject(error);
    }
);

export default api;
