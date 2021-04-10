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
        start: 0,
        next_start: 0,
        text: '',
        page_size: 100,
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
        setStart(state, start){
            state.start = start;
        },
        setNextStart(state, next_start){
            state.next_start = next_start;
        },
        setText(state, text){
            state.text = text;
        },
        setPageSize(state, page_size){
            state.page_size = page_size;
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
        },
        getStart: state => {
            return state.start
        },
        getNextStart: state => {
            return state.next_start
        },
        getText: state => {
            return state.text
        },
        getPageSize: state => {
            return state.page_size
        }
    },
    actions: {
        showPersons (context, options) {
            context.commit('setOwner', options.owner_id);
            axios.post('/pi-persons',{owner_id: options.owner_id, text:options.text, start:options.start})
                .then((response) => {
                    context.commit('setPersons', response.data.data.data);
                    context.commit('setStart', response.data.data.start);
                    context.commit('setNextStart', response.data.data.next_start);
                    context.commit('setPageSize', response.data.data.page_size);
                    context.commit('setText', options.text);
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