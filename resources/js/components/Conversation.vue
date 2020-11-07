<template>
    <div class="conversation">
        <h1 v-html="contact ? this.getContact(contact) : 'Select a Contact'"></h1>
        <MessagesFeed :contact="contact" :messages="messages"/>
        <MessageComposer v-if="contact" @send="sendMessage"/>
    </div>
</template>

<script>
    import MessagesFeed from './MessagesFeed';
    import MessageComposer from './MessageComposer';

    export default {
        props: {
            contact: {
                type: Object,
                default: null
            },
            messages: {
                type: Array,
                default: []
            }
        },
        methods: {
            sendMessage(text) {
                if (!this.contact) {
                    return;
                }

                axios.post('/conversation-message/send', {
                    contact_id: this.contact.id,
                    text: text.message,
                    media: text.media,
                    filetype: text.filetype
                }).then((response) => {
                    this.$emit('new', response.data);
                })
                .catch(err => {
                    if(err.message == 'CSRF token mismatch.'){
                        alert('Your session has expired. Please refresh the page.')
                    }
                })
            },
            getContact(contact){
                let phone = contact.contact_number;
                // if(contact.phone_id){
                //     phone = '<a href="/phones/'+phone+'" target="_blank" title="Report">'+phone+'</a>';
                // }
                let emails = '';
                let email_url = '';
                let phone_url = '';
                let checkout_url = '';
                if(contact.project_url !== 'undefined' && contact.project_url){
                    email_url = 'https://'+contact.project_url+'/admin/orders?selectedView=all&query=';
                    phone_url = 'https://'+contact.project_url+'/admin/customers?query=';
                    checkout_url = 'https://'+contact.project_url+'/admin/checkouts?query=';
                }
                if(contact.emails !== 'undefined' && contact.emails.length){
                    emails = '<div>';
                    contact.emails.forEach(email => {
                        if(email !== 'undefined' && email ){
                            emails += '<div class="mb-2">'+email+'</div>';
                            emails += '<div class="small">';
                            if(email_url){
                                emails += '<div><a class="small text-muted" href="'+encodeURI(email_url+email)+'" target="_blank" title="Search shopify orders for '+email+'">Shopify Orders</a></div>';
                            }
                            if(phone_url){
                                emails += '<div><a class="small text-muted" href="'+encodeURI(phone_url+email)+'" target="_blank" title="Search shopify customers for '+email+'">Shopify Customer</a></div>';
                            }
                            if(checkout_url){
                                emails += '<div><a class="small text-muted text-nowrap" href="'+encodeURI(checkout_url+email)+'" target="_blank" title="Search shopify abandoned checkouts for '+email+'">Abandoned Checkout</a></div>';
                            }
                            emails += '</div><hr />';
                        }
                    });
                    emails += '</div>';
                }
                let full_name = '';
                if(contact.full_name !== 'undefined' && contact.full_name){
                    full_name = contact.full_name;
                }
                let shopify_client_phone = '';
                if(phone_url){
                    shopify_client_phone = '<div class="small"><a class="small text-muted" href="'+encodeURI(phone_url+contact.contact_number)+'" target="_blank" title="Search shopify customers for '+contact.contact_number+'">Shopify Customer (search by phone)</a></div>';
                }

                return phone + (contact.status ? '' : ' Archived') +'<div>'+ full_name +'</div>' +emails + '<div>'+shopify_client_phone+'</div>';
            }
        },
        components: {MessagesFeed, MessageComposer}
    }
</script>

<style lang="scss" scoped>
.conversation {
    flex: 5;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    background-color: aliceblue;
    border: 1px solid #aaa;

    h1 {
        font-size: 20px;
        padding: 10px;
        margin: 0;
        /*border-bottom: 1px dashed lightgray;*/
    }
}
@media (max-width: 699px) {
    .conversation{
        margin-top: 2rem;
    }
}
</style>
