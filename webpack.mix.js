const mix = require('laravel-mix');
const src = 'resources';
const dist = 'public';

mix.setPublicPath('./public');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your TastyIgniter application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

//
//  Build Admin SCSS
//
mix.sass(`${src}/scss/app.scss`, `${dist}/css`).options({
    processCssUrls: false,
})

mix.sass(`${src}/scss/static.scss`, `${dist}/css`)

//
//  Build Admin JS
//
mix.js(`${src}/js/app.js`, `${dist}/js`);

mix.combine([
    'node_modules/animate.css/animate.compat.css',
    'node_modules/bootstrap-colorpicker/dist/css/bootstrap-colorpicker.min.css',
    'node_modules/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css',
    'node_modules/bootstrap-multiselect/dist/css/bootstrap-multiselect.css',
    'node_modules/bootstrap-table/dist/bootstrap-table.min.css',
    // 'node_modules/bootstrap-treeview/dist/bootstrap-treeview.min.css',
    'node_modules/codemirror/lib/codemirror.css',
    'node_modules/codemirror/theme/material.css',
    'node_modules/clockpicker/dist/bootstrap-clockpicker.min.css',
    'node_modules/daterangepicker/daterangepicker.css',
    'node_modules/dropzone/dist/dropzone.css',
    'node_modules/easymde/dist/easymde.min.css',
    'node_modules/fullcalendar/main.min.css',
    'node_modules/summernote/dist/summernote-bs5.min.css',
    'node_modules/tempusdominus-bootstrap-4/build/css/tempusdominus-bootstrap-4.min.css',
    `${src}/js/vendor/timesheet/timesheet.css`,
], `${dist}/css/vendor.css`)

mix.combine([
    'node_modules/bootstrap-colorpicker/dist/js/bootstrap-colorpicker.min.js',
    'node_modules/bootstrap-multiselect/dist/js/bootstrap-multiselect.js',
    'node_modules/bootstrap-table/dist/bootstrap-table.min.js',
    // 'node_modules/bootstrap-treeview/dist/bootstrap-treeview.min.js',
    'node_modules/dropzone/dist/dropzone-min.js',
    'node_modules/inputmask/dist/jquery.inputmask.min.js',
    'node_modules/mustache/mustache.min.js',
    `${src}/js/vendor/selectonic/selectonic.min.js`,
    'node_modules/sortablejs/Sortable.js',
], `${dist}/js/vendor.js`)

mix.combine([
    'node_modules/chart.js/dist/chart.min.js',
    'node_modules/chartjs-adapter-moment/dist/chartjs-adapter-moment.min.js',
], `${dist}/js/vendor.chart.js`)

mix.combine([
    'node_modules/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js',
    'node_modules/clockpicker/dist/bootstrap-clockpicker.min.js',
    'node_modules/daterangepicker/daterangepicker.js',
    'node_modules/fullcalendar/main.min.js',
    'node_modules/tempusdominus-bootstrap-4/build/js/tempusdominus-bootstrap-4.min.js',
    `${src}/js/vendor/timesheet/timesheet.js`,
], `${dist}/js/vendor.datetime.js`)

mix.combine([
    'node_modules/codemirror/lib/codemirror.js',
    'node_modules/codemirror/mode/clike/clike.js',
    'node_modules/codemirror/mode/css/css.js',
    'node_modules/codemirror/mode/htmlembedded/htmlembedded.js',
    'node_modules/codemirror/mode/htmlmixed/htmlmixed.js',
    'node_modules/codemirror/mode/javascript/javascript.js',
    'node_modules/codemirror/mode/php/php.js',
    'node_modules/codemirror/mode/xml/xml.js',
    'node_modules/summernote/dist/summernote-bs5.min.js',
    'node_modules/easymde/dist/easymde.min.js',
], `${dist}/js/vendor.editor.js`)

mix.copyDirectory(`${src}/images`, `${dist}/images`)

// We only want to copy these files when building for production
if (!mix.inProduction()) return

//
// Copy fonts from node_modules
//
mix.copyDirectory(
    'node_modules/@fortawesome/fontawesome-free/webfonts',
    `${dist}/fonts/FontAwesome`
).copyDirectory(
    'node_modules/summernote/dist/font',
    `${dist}/fonts/summernote`
).copyDirectory(
    'node_modules/summernote/dist/lang',
    `${dist}/js/locales/summernote`
).copyDirectory(
    'node_modules/bootstrap-datepicker/dist/locales',
    `${dist}/js/locales/datepicker`
).copy(
    'node_modules/fullcalendar/locales-all.min.js',
    `${dist}/js/locales/fullcalendar/locales-all.min.js`
);
