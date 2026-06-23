import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
// Send the XSRF-TOKEN cookie as the X-XSRF-TOKEN header so axios POST/PATCH/DELETE
// pass Laravel CSRF verification (axios 1.x requires this to be opt-in).
window.axios.defaults.withXSRFToken = true;
