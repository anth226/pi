<template>
    <div class="new-conversation">
        <div class="m-3">
            <form v-on:submit="create">
                <input class="mb-1" type="text" v-model="phone_number" placeholder="Phone Number..." />
                <button class="btn btn-primary" type="submit">Start New Conversation</button>
            </form>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                phone_number: ''
            };
        },
        methods: {
            create(e) {
                e.preventDefault();
                if (this.phone_number == '') {
                    return;
                }

                this.$emit('create', this.phone_number);
                this.phone_number = '';
            }
        }
    }
</script>

<style lang="scss" scoped>
    .new-conversation{
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

