<template>
    <div class="status-box">
        <div class="row">
            <div class="col-lg-12 m-auto">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group row">
                            <div class="col-12">
                                <input class="form-control" type="text" v-model="statusText"  readonly />
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-12">
                                <button v-on:click="hangUp" class="btn btn-lg btn-danger hangup-button" :disabled='disabledHangUp' >Hang up</button>
                                <button v-on:click="answerCall" class="btn btn-lg btn-success answer-button" :disabled='disabledAnswerButton' >Answer call</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        data(){
            return {
                statusText: "Connecting...",
                disabledHangUp: true,
                disabledAnswerButton:true,
                device:null,
                connection:null
            }
        },
        mounted(){
            this.setupClient()
        },
        methods: {
            setupClient(){
                axios.post('/twilio-token', {_token: $('meta[name="csrf-token"]').attr('content')})
                    .then((response) => {
                        this.device = new Twilio.Device();
                        this.device.setup(response.data.token);
                        this.setupHandlers(this.device);
                    })
                    .catch(err => {
                        this.statusText = err + " Could not get a token from server!";
                    })
            },
            setupHandlers(device) {
                device.on('ready', () => {
                    this.statusText = "Ready";
                });

                /* Report any errors to the call status display */
                device.on('error', (error) => {
                    this.statusText = "ERROR: " + error.message;
                });

                /* Callback for when Twilio Client initiates a new connection */
                device.on('connect', (connection) => {
                    // Enable the hang up button and disable the call buttons
                    this.connection = connection;
                    this.disabledHangUp = false;
                    this.disabledAnswerButton = true;
                    // If phoneNumber is part of the connection, this is a call from a
                    // support agent to a customer's phone
                    if ("phoneNumber" in connection.message) {
                        this.statusText = "In call with " + connection.message.phoneNumber;
                    } else {
                        // This is a call from a website user to a support agent
                        this.statusText = "In call";
                    }
                });

                /* Callback for when a call ends */
                device.on('disconnect', (connection) => {
                    // Disable the hangup button and enable the call buttons
                    this.connection = connection;
                    this.disabledHangUp = true;
                    this.disabledAnswerButton = true;
                    this.statusText = "Ready" ;
                });

                /* Callback for when a call canceled */
                device.on('cancel', (connection) => {
                    // Disable the hangup button and enable the call buttons
                    this.connection = connection;
                    this.disabledHangUp = true;
                    this.disabledAnswerButton = true;
                    this.statusText = "Ready" ;
                });

                /* Callback for when Twilio Client receives a new incoming call */
                device.on('incoming', (connection) => {
                    this.connection = connection;
                    this.disabledHangUp = false;
                    this.disabledAnswerButton = false;
                    this.statusText = "Incoming call from " +  connection.parameters.From;
                    // Set a callback to be executed when the connection is accepted
                    connection.accept(() => {
                        this.statusText = "In call with customer";
                    });
                });

                //reconnect
                device.on('offline', () => {
                    this.setupClient();
                });

            },
            hangUp() {
                this.device.disconnectAll();
                this.connection.reject();
                this.statusText = "Ready";
                this.disabledHangUp = true;
                this.disabledAnswerButton = true;
            },
            answerCall() {
                this.connection.accept();
            }
        }

    }
</script>

<style lang="scss" scoped>

</style>
