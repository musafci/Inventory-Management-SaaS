import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.api = {
    token: null,
    organizationId: null,

    setAuth(token, orgId) {
        this.token = token;
        this.organizationId = orgId;
        localStorage.setItem('auth_token', token);
        localStorage.setItem('organization_id', orgId);
        window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        window.axios.defaults.headers.common['X-Organization-Id'] = orgId;
    },

    clearAuth() {
        this.token = null;
        this.organizationId = null;
        localStorage.removeItem('auth_token');
        localStorage.removeItem('organization_id');
        localStorage.removeItem('user_name');
        localStorage.removeItem('user_email');
        localStorage.removeItem('organizations');
        delete window.axios.defaults.headers.common['Authorization'];
        delete window.axios.defaults.headers.common['X-Organization-Id'];
    },

    init() {
        this.token = localStorage.getItem('auth_token');
        this.organizationId = localStorage.getItem('organization_id');
        if (this.token) {
            window.axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
            window.axios.defaults.headers.common['X-Organization-Id'] = this.organizationId;
        }
    },

    async request(method, url, data = null, params = null) {
        try {
            const args = [url];
            if (data) args.push(data);
            if (params) args.push({ params });
            const response = await window.axios[method](...args);
            return response.data;
        } catch (error) {
            if (error.response?.status === 401) {
                this.clearAuth();
                window.location.href = '/login';
            }
            throw error;
        }
    },

    get(url, params) { return this.request('get', url, null, params); },
    post(url, data) { return this.request('post', url, data); },
    put(url, data) { return this.request('put', url, data); },
    delete(url) { return this.request('delete', url); },
};

window.api.init();
