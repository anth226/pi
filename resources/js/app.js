/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');
// require('./select2');
// require('./daterangepicker');
require('./pusher');
require('./script');

window.Vue = require('vue');

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

const files = require.context('./', true, /\.vue$/i);
files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default));
Vue.config.devtools = true;
// Vue.component('example-component', require('./components/ExampleComponent.vue').default);

// Vue.component('chat-messages', require('./components/ChatMessages.vue'));
// Vue.component('chat-form', require('./components/ChatForm.vue'));

// Vue.component('chat-app', require('./components/ChatApp.vue'));

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const apppp = new Vue({
    el: '#apppp',
    mounted() {
        this.enableInterceptor()
    },
    data: {
        isLoading: false,
        axiosInterceptor: null,
    },
    methods: {
        enableInterceptor() {
            this.axiosInterceptor = window.axios.interceptors.request.use((config) => {
                this.isLoading = true
                return config
            }, (error) => {
                this.isLoading = false
                return Promise.reject(error)
            });

            window.axios.interceptors.response.use((response) => {
                this.isLoading = false
                return response
            }, function(error) {
                this.isLoading = false
                return Promise.reject(error)
            })
        },

        disableInterceptor() {
            window.axios.interceptors.request.eject(this.axiosInterceptor)
        },
    },
});

