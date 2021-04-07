<template>
    <div class="persons" ref="feed">
        <ul>
            <li v-for="person in persons" :key="person.personId" :class="{ 'selected': person.selected == 1 }">
                <div class="all-data" @click="selectPerson(person)">
                    <div class="person">
                        <p class="h6">{{ person.name }}</p>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>

<script>
    export default {
        computed: {
            persons: function () {
                return this.$store.state.persons;
            },
            selected: function () {
                return this.persons.length ? this.persons[0] : null
            }
        },
        methods: {
            selectContact(person) {
                this.selected = person;
            }
        },
    }
</script>

<style lang="scss" scoped>
.persons {
    max-height: 100%;
    height: 500px;
    overflow-y: scroll;
    position:relative;
    border-top: 1px solid #a6a6a6;
    border-bottom: 1px solid #a6a6a6;

    .add-archive{
        z-index: 999;
    }
    
    ul {
        list-style-type: none;
        padding-left: 0;

        li {
            &.selected {
                background: #dfdfdf;
            }

            .all-data {
                display: flex;
                padding: 2px;
                border-bottom: 1px solid #a6a6a6;
                /*border-left: 1px solid #a6a6a6;*/
                height: 80px;
                position: relative;
                cursor: pointer;

                &.selected {
                    background: #dfdfdf;
                }

                span.unread {
                    background: #82e0a8;
                    color: #fff;
                    position: absolute;
                    right: 11px;
                    top: 20px;
                    display: flex;
                    font-weight: 700;
                    min-width: 20px;
                    justify-content: center;
                    align-items: center;
                    line-height: 20px;
                    font-size: 12px;
                    padding: 0 4px;
                    border-radius: 3px;
                }

                .avatar {
                    flex: 1;
                    display: flex;
                    align-items: center;

                    img {
                        width: 35px;
                        border-radius: 50%;
                        margin: 0 auto;
                    }
                }

                .person {
                    flex: 3;
                    font-size: 10px;
                    overflow: hidden;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;

                    p {
                        margin: 0;

                        &.name {
                            font-weight: bold;
                        }
                    }
                }
            }
        }
    }
}
@media (max-width: 699px) {
    .persons {
        height: 190px;
        ul {
            li {
                .all-data {
                    border-left: none;
                }
            }
        }
    }
}
</style>
