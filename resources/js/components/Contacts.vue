<template>
    <div class="contacts" ref="feed">
        <ul>
            <li v-for="contact in contacts" :key="contact.id" :class="{ 'selected': contact.selected == 1 }">
                <div class="add-archive position-absolute">
                    <button class="btn btn-danger position-absolute m-1" v-if="!contact.unread && !contact.selected" @click="archiveContact(contact)">x</button>
                </div>
                <div class="all-data" @click="selectContact(contact)">
                    <div class="avatar">
                        <!--<img :src="contact.profile_image" :alt="contact.name">-->
                    </div>
                    <div class="contact">
                        <p class="h6">{{ contact.contact_number + (contact.status ? '' : ' Archived')}}</p>
                        <p class="h5">{{ contact.full_name }}</p>
                        <!--<p class="email">{{ contact.email }}</p>-->
                    </div>
                    <span class="unread" v-if="contact.unread">{{ contact.unread }}</span>
                </div>
            </li>
        </ul>
    </div>
</template>

<script>
    export default {
        props: {
            contacts: {
                type: Array,
                default: []
            },
            selected: {
                type: Object,
                default: null
            },
            // selectedContact: {
            //     type: Object,
            //     default: null
            // }
        },
        data() {
            return {
                selected: this.contacts.length ? this.contacts[0] : null
            };
        },
        methods: {
            selectContact(contact) {
                this.selected = contact;
                // this.scrollToTop();
                this.$emit('selected', contact);
            },
            archiveContact(contact){
                this.$emit('archived', contact);
            },
            // scrollToTop() {
            //     setTimeout(() => {
            //         this.$refs.feed.scrollTop = 0;
            //     }, 50);
            // },
        },
        // computed: {
        //     sortedContacts() {
        //         return _.sortBy(this.contacts, [(contact) => {
        //             if (contact == this.selected) {
        //                 return Infinity;
        //             }
        //
        //             return contact.unread;
        //         }]).reverse();
        //     }
        //
        // },
        // watch: {
        //     selectedContact(){
        //         this.selected = this.selectedContact;
        //         this.scrollToTop();
        //     }
        // }
    }
</script>

<style lang="scss" scoped>
.contacts {
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
            &:first-child{
                .all-data {
                    /*border-top: 1px solid #a6a6a6;*/
                }
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

                .contact {
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
    .contacts {
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
