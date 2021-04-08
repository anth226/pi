<template>
    <div class="dialer">
        <div class="row">
            <div class="col-lg-12 m-auto">
                <div class="card">
                    <div class="card-body">
                        <VuePhoneNumberInput v-model="dialer" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import VuePhoneNumberInput from 'vue-phone-number-input';
    import 'vue-phone-number-input/dist/vue-phone-number-input.css';

    export default {
        data(){
            return {
                dialer:null
            }
        },
        components: {VuePhoneNumberInput}
    }


</script>