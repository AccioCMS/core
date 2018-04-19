import Vue from 'vue'
import VueFroala from 'vue-froala-wysiwyg'
import Multiselect from 'vue-multiselect'

// general cms app
Vue.component('app', require('../../views/components/App.vue'));
Vue.component('application-link', require('../../views/components/vendor/ApplicationLink.vue'));
Vue.component('cms-menu-link', require('../../views/components/vendor/CmsMenuLink.vue'));

// Spinner (Loading compontent)
Vue.component('spinner', require('../../views/components/vendor/Spinner.vue'));

// Custom Fields
Vue.component("field", require("../../views/components/custom_fields/Field.vue"));
Vue.component("customField", require("../../views/components/vendor/CustomField.vue"));

// noty css
require('noty/lib/noty.css');

// Multi select plugin
Vue.component("multiselect", Multiselect);

// Froala Editor
Vue.use(VueFroala);