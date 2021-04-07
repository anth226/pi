<template>
    <div class="persons-list">
        <Persons  @selected="selectPerson" />
    </div>
</template>

<script>
    import Persons from './Persons';

    export default {
        methods: {
            selectPerson(selected) {
                this.$emit('selected', selected);
            }
        },
        components: {Persons}
    }
</script>

<style lang="scss" scoped>
.persons-list {
    flex: 2;
    background-color: aliceblue;
    border: 1px solid #aaa;
}
@media (min-width: 700px) {
    .persons-list {
        border-right: none;
    }
}
</style>
