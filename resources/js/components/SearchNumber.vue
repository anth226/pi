<template>
    <div class="search-number">
        <div class="m-3">
            <form v-on:submit="search">
                <input class="mb-1" type="text" v-model="search_number" placeholder="Search Number..." />
                <button class="btn btn-primary" type="submit">Search</button>
            </form>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                search_number: ''
            };
        },
        methods: {
            search(e) {
                e.preventDefault();
                if (this.search_number == '') {
                    return;
                }

                this.$emit('search', this.search_number);
            }
        }
    }
</script>

<style lang="scss" scoped>
    .search-number{
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

