/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');
// require('./select2');
// require('./daterangepicker');
// require('./pusher');
window.Twilio = require('twilio-client');
//require('./script');

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

import Vuex from 'vuex'

Vue.use(Vuex);

const store = new Vuex.Store({
    state: {
        persons: [],
        owner_id: '',
        device: null,
        connection: null
    },
    mutations: {
        setPersons (state, persons) {
            state.persons = persons;
        },
        setOwner(state, owner_id){
            state.owner_id = owner_id;
        },
        setDevice(state, device){
            state.device = device;
        },
        setConnection(state, connection){
            state.connection = connection;
        }
    },
    getters: {
        getOwnerId: state => {
            return state.owner_id
        }
    },
    actions: {
        setPersons (context, owner_id) {
            context.commit('setOwner', owner_id);
            axios.post('/pi-persons',{owner_id: owner_id})
                .then((response) => {
                    context.commit('setPersons', response.data.data);
                })
                .catch(err => {
                    if(err.message == 'CSRF token mismatch.'){
                        alert('Your session has expired. Please refresh the page.')
                    }
                })
        }
    }
});

const apppp = new Vue({
    el: '#apppp',
    store: store
});