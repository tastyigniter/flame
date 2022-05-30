/**
 * Load required plugins.
 */
window.$ = window.jQuery = require('jquery');
window.Popper = require('@popperjs/core');
window.bootstrap = require('bootstrap');
window.Cookies = require('js-cookie');
window.Alpine = require('alpinejs')
window.moment = require('moment');

require('sweetalert2');
require('js-cookie');
require('metismenu');

require('./partials/request');
require('./partials/loader.bar');
require('./partials/loader.progress');
require('./partials/flashmessage');
require('./partials/toggler');
require('./partials/trigger');
require('./partials/script');
