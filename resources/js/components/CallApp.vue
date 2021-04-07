<template>
    <div class="call-app">
        <PersonsList :persons="persons"  />
    </div>
</template>

<script>
    import PersonsList from './PersonsList';

    export default {
        props: {
            owner_id: {
                type: Number,
                required: true
            }
        },
        data() {
            return {
                selectedPerson: null,
                persons: []
            };
        },
        mounted() {
            this.fetchPersons();
        },
        methods: {
            fetchPersons(){
                this.persons = [];
                axios.post('/pi-persons',{owner_id: this.owner_id})
                    .then((response) => {
                        this.persons = response.data;
                    })
                    .catch(err => {
                        if(err.message == 'CSRF token mismatch.'){
                            alert('Your session has expired. Please refresh the page.')
                        }
                    })
                ;
            },
        },
        components: {PersonsList}
    }
</script>


<style lang="scss" scoped>
    @media (min-width: 700px) {
        .call-app {
            display: flex;
        }
    }
</style>
