<template>
    <div class="contacts-list">
        <SearchNumber @search="searchNumber" />
        <Contacts  :contacts="contacts" @selected="selectContact" @archived="archiveContact" />
        <NewConversation @create="createNewConversation" />
    </div>
</template>

<script>
    import SearchNumber from './SearchNumber';
    import Contacts from './Contacts';
    import NewConversation from './NewConversation';

    export default {
        props: {
            // contact: {
            //     type: Object,
            //     default: null
            // },
            contacts: {
                type: Array,
                default: []
            }
        },
        methods: {
            selectContact(selected) {
                this.$emit('selected', selected);
            },
            archiveContact(archived){
                this.$emit('archived', archived);
            },
            createNewConversation(phone_number){
                this.$emit('phone_number', phone_number);
            },
            searchNumber(search_number){
                this.$emit('search_number', search_number);
            }
        },
        components: {Contacts, SearchNumber, NewConversation}
    }
</script>

<style lang="scss" scoped>
.contacts-list {
    flex: 2;
    background-color: aliceblue;
    border: 1px solid #aaa;
}
@media (min-width: 700px) {
    .contacts-list {
        border-right: none;
    }
}
</style>
