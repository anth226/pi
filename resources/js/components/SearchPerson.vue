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
                search_person: '',
                start: 0,
                next_start: 0
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
                let options = {
                    owner_id: this.owner_id,
                    text: '',
                    start: 0
                };
                if (this.search_person.length > 1) {
                    options = {
                        owner_id: this.owner_id,
                        text: this.search_person,
                        start: 0
                    };
                }
                this.$store.dispatch('showPersons', options);
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

