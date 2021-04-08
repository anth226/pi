<template>
    <div class="call-app">
        <loader :isVisible="isLoading" />
        <PersonsList   />
        <Conversation />
    </div>
</template>

<script>
    import PersonsList from './PersonsList';
    import Loader from './Loader';
    import Conversation from './Conversation';

    export default {
        props: {
            owner_id: {
                type: Number,
                required: true
            }
        },
        mounted() {
            this.enableInterceptor();
            this.fetchPersons();
        },
        methods: {
            fetchPersons(){
                this.$store.dispatch('setPersons', this.owner_id);
            },
            enableInterceptor() {
                this.axiosInterceptor = window.axios.interceptors.request.use((config) => {
                    this.isLoading = true;
                    return config
                }, (error) => {
                    this.isLoading = false;
                    return Promise.reject(error)
                });

                window.axios.interceptors.response.use((response) => {
                    this.isLoading = false;
                    return response
                }, function(error) {
                    this.isLoading = false;
                    return Promise.reject(error)
                })
            },
            disableInterceptor() {
                window.axios.interceptors.request.eject(this.axiosInterceptor)
            },
        },
        data() {
            return {
                isLoading: false,
                axiosInterceptor: null,
            };
        },

        components: {PersonsList, Loader, Conversation}
    }
</script>


<style lang="scss" scoped>
    @media (min-width: 700px) {
        .call-app {
            display: flex;
        }
    }
</style>
