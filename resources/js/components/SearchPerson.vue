<template>
    <div class="search-person">
        <div class="m-3">
            <form v-on:submit="search">
                <input class="mb-1" type="text" v-model="search_person" placeholder="Search Person..." />
                <button class="btn btn-primary" type="submit">Search</button>
            </form>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                search_person: ''
            };
        },
        computed: {
            owner_id: function() {
                return this.$store.getters.getOwnerId
            }
        },
        methods: {
            search(e) {
                e.preventDefault();
                if (this.search_person == '') {
                    this.$store.dispatch('setPersons', this.owner_id);
                }
                else {
                    axios.post('/pi-persons', {owner_id: this.owner_id, text: this.search_person})
                        .then((response) => {
                            this.$store.commit('setPersons', response.data.data);
                         })
                        .catch(err => {
                            if (err.message == 'CSRF token mismatch.') {
                                alert('Your session has expired. Please refresh the page.')
                            }
                        })
                }
            }
        }
    }
</script>

<style lang="scss" scoped>
    .search-person{
        input {
            width: 100%;
            border-radius: 3px;
            border: 1px solid lightgray;
            padding: 6px;
        }
        button {
            width: 100%;
        }
    }
</style>

