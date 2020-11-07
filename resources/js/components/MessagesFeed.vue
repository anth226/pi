<template>
    <div class="feed" ref="feed">
        <ul v-if="contact">
            <li v-for="message in messages" :class="`message${message.sent_by_admin > 0 ? ' sent' : ' received'}`" :key="message.id">
                <div class="text" :class="{ 'bg-success': (message.is_new == 1 && message.sent_by_admin == 0) }" v-html="'<div class=\'h5 text-left\'>'+nl2br(message.message)+'</div>' + msgToHtml(message.from_m_m_s, message.id)+'<div class=\'small\'>' + message.created_at+ '</div>'+delivered(message)"></div>
            </li>
        </ul>
    </div>
</template>

<script>
    export default {
        props: {
            contact: {
                type: Object
            },

            messages: {
                type: Array,
                required: true
            }
        },
        methods: {
            scrollToBottom() {
                setTimeout(() => {
                    this.$refs.feed.scrollTop = this.$refs.feed.scrollHeight - this.$refs.feed.clientHeight;
                }, 50);
            },
            nl2br (str) {
                if (typeof str === 'undefined' || str === null) {
                    return '';
                }
                var breakTag = '<br/>';
                return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
            },
            msgToHtml(msg, id){
                let htmlRes = '';
                if(msg !== undefined && msg.length) {
                    msg.forEach(function (m) {
                        if (m && m.mediaUrl && m.MIMEType) {
                            if (m.MIMEType.includes('image')) {
                                htmlRes += '<div class="mb-1">' +
                                    '<a href="' + m.mediaUrl + '" data-fancybox="gallery_' + id + '">' +
                                    '<img class="w-100" src="' + m.mediaUrl + '" alt="attachment">' +
                                    '</a>' +
                                    '</div>';
                            }
                            else {
                                htmlRes += '<div class="mb-1">' +
                                    '<a href="' + m.mediaUrl + '" download><i class="icon icon-download"></i> Download</a>' +
                                    '</div>';
                            }
                        }
                    });
                }
                return htmlRes;
            },
            delivered(message){
                let ret_res = '';
                if(typeof message.sent_by_admin !== 'undefined' && message.sent_by_admin > 1){
                    switch(message.sent_by_admin) {
                        case 2:
                            ret_res = '<div class="small bg-success text-white text-center">Delivered</div>';
                            break;
                        case 3:
                            ret_res = '<div class="small bg-danger text-white text-center">Undelivered</div>';
                            break;
                        case 4:
                            ret_res = '<div class="small bg-danger text-white text-center">Error</div>';
                            break;
                        default:
                            ret_res = '';
                    }
                 }
                return ret_res;
            }
        },
        watch: {
            contact(contact) {
                this.scrollToBottom();
            },
            messages(messages) {
                this.scrollToBottom();
            }
        }
    }
</script>

<style lang="scss" scoped>
.feed {
    background: #f0f0f0;
    height: 100%;
    max-height: 470px;
    overflow: scroll;
    border-top: 1px dashed lightgray;
    border-bottom: 1px dashed lightgray;
    ul {
        list-style-type: none;
        padding: 5px;

        li {
            &.message {
                margin: 10px 0;
                width: 100%;

                .text {
                    max-width: 200px;
                    border-radius: 5px;
                    padding: 12px;
                    display: inline-block;
                    &.bg-success{
                        color:white;
                    }
                }

                &.received {
                    text-align: right;

                    .text {
                        background: #b2b2b2;
                    }
                }

                &.sent {
                    text-align: left;

                    .text {
                        background: #81c4f9;
                    }
                }
            }
        }
    }
}
</style>

