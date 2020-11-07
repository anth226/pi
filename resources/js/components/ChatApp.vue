<template>
    <div class="chat-app">
        <ContactsList :contacts="contacts" @archived="archiveContact" @selected="startConversationWith" @phone_number="newContact" @search_number="searchContact" />
        <Conversation :contact="selectedContact" :messages="messages" @new="saveNewMessage"/>
    </div>
</template>

<script>
    import Conversation from './Conversation';
    import ContactsList from './ContactsList';

    export default {
        props: {
            support: {
                type: Object,
                required: true
            }
        },

        data() {
            return {
                selectedContact: null,
                messages: [],
                contacts: []
            };
        },
        mounted() {
            Echo.private(`messages.${this.support.id}`)
                .listen('NewMessage', (e) => {
                    this.hanleIncoming(e.message);
                    this.sendPushNotification(e.message);
                });
            this.fetchContacts();
        },
        methods: {
            startConversationWith(contact) {
                this.updateUnreadCount(contact, true);

                this.contacts = this.contacts.map((single) => {

                    if (single.id !== contact.id) {
                        single.selected = 0;
                    }
                    else{
                        single.selected = 1;
                    }

                    return single;
                });

                axios.post(`/conversation/${contact.id}`)
                    .then((response) => {
                        this.messages = response.data.messages;
                        contact.emails = response.data.emails;
                        contact.project_url = response.data.project_url;
                        this.selectedContact = contact;
                    })
                    .catch(err => {
                        if(err.message == 'CSRF token mismatch.'){
                            alert('Your session has expired. Please refresh the page.')
                        }
                    })
            },
            archiveContact(contact){
                if(contact.status) {
                    axios.post(`/archive-conversation/${contact.id}`)
                        .then((response) => {
                            if (response.data) {
                                this.removeContact(contact);
                            }
                        })
                        .catch(err => {
                            if (err.message == 'CSRF token mismatch.') {
                                alert('Your session has expired. Please refresh the page.')
                            }
                        })
                }
                else{
                    this.removeContact(contact);
                }
            },
            removeContact(contact){
                this.contacts.forEach((cont, index) => {
                    if (cont.id == contact.id) {
                        this.contacts.splice(index, 1);
                    }
                });
            },
            saveNewMessage(message) {
                this.messages.push(message);
            },
            updateSentMessage(message) {
                let is_exist = false;
                this.messages.forEach( (mes, index)  => {
                    if(message.id == mes.id){
                        is_exist = true;
                        mes.sent_by_admin = 2;
                    }
                });
                if(!is_exist){
                    this.messages.push(message);
                }
            },

            hanleIncoming(message) {
                if (this.selectedContact && message.contact_id == this.selectedContact.id) {
                    message.is_new = 1;
                    if(typeof message.sent_by_admin !== 'undefined' && (message.sent_by_admin > 1)){
                        this.updateSentMessage(message);
                    }
                    else{
                        this.saveNewMessage(message);
                    }

                    return;
                }

                this.updateContacts(message.from_contact);
            },

            updateUnreadCount(contact, reset) {
                this.contacts = this.contacts.map((single) => {

                    if (single.id !== contact.id) {
                        return single;
                    }

                    if (reset)
                        single.unread = 0;
                    else
                        single.unread += 1;

                    return single;
                });

            },
            sendPushNotification(message){
                if (! ('Notification' in window)) {
                    alert('Web Notification is not supported');
                    return;
                }

                Notification.requestPermission( permission => {
                    let notification = new Notification('New message alert!', {
                        body: message.message, // content for the alert
                        icon: "/img/message.png" // optional image url
                    });

                    // link to page on clicking the notification
                    notification.onclick = () => {
                        window.open(window.location.href);
                    };
                });
            },
            updateContacts(contact){
                const newContact = this.contacts.filter((single) => {
                    return single.id == contact.id;
                });
                if(newContact.length == 0){
                    // this.fetchContacts();
                    contact.unread = 1;
                    this.contacts.unshift(contact);
                }
                else{
                    this.updateUnreadCount(contact, false);
                    let needUnshift = false;
                    this.contacts.forEach( (cont, index)  => {
                        if(cont.id == contact.id && !cont.selected){
                             contact = cont;
                             this.contacts.splice(index, 1);
                             needUnshift = true;
                        }
                    });
                    if(needUnshift) {
                        this.contacts.unshift(contact);
                    }
                }
            },
            fetchContacts(){
                this.contacts = [];
                axios.post('/contacts',{support_id: this.support.id})
                    .then((response) => {
                        this.contacts = response.data;
                    })
                    .catch(err => {
                        if(err.message == 'CSRF token mismatch.'){
                            alert('Your session has expired. Please refresh the page.')
                        }
                    })
                ;
            },
            newContact(phone_number){
                axios.post('/conversation-create', {
                    support_id: this.support.id,
                    phone_number: phone_number
                }).then((response) => {
                    let needToAdd = true;
                    this.contacts.forEach( (contact, index)  => {
                        if(contact.id == response.data.id){
                            needToAdd = false;
                        }
                    });
                    if(needToAdd) {
                        this.contacts.unshift(response.data);
                    }
                    this.startConversationWith(response.data);
                })
                .catch(err => {
                    if(err.message == 'CSRF token mismatch.'){
                        alert('Your session has expired. Please refresh the page.')
                    }
                })
            },
            searchContact(search_number){
                axios.post('/search-contact', {
                    support_id: this.support.id,
                    search_number: search_number
                }).then((response) => {
                    let needToAdd = true;
                    this.contacts.forEach( (contact, index)  => {
                        if(contact.id == response.data.id){
                            needToAdd = false;
                        }
                    });
                    if(needToAdd) {
                        this.contacts.unshift(response.data);
                    }
                    this.startConversationWith(response.data);
                })
                .catch(err => {
                    if(err.message == 'CSRF token mismatch.'){
                        alert('Your session has expired. Please refresh the page.')
                    }
                })
            }
        },
        components: {Conversation, ContactsList}
    }
</script>


<style lang="scss" scoped>
    @media (min-width: 700px) {
        .chat-app {
            display: flex;
        }
    }
</style>
