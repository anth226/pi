<template>
    <div class="status-box">
        <div class="row mb-2">
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

        watch: {
            deviceReady: function(val) {
                if (val) {
                    this.statusText = "Ready";
                }
            },
            deviceError: function(error) {
                if (error) {
                    this.statusText = "ERROR: " + error.message;
                }
            },
            deviceConnected: function(val) {
                if (val) {
                    this.disabledHangUp = false;
                    this.disabledAnswerButton = true;
                    // If phoneNumber is part of the connection, this is a call from a
                    // support agent to a customer's phone
                    if ("phoneNumber" in this.connection.message) {
                        this.statusText = "In call with " + this.connection.message.phoneNumber;
                    } else {
                        // This is a call from a website user to a support agent
                        this.statusText = "In call";
                    }
                }
            },
            deviceDisconnected: function(val) {
                if (val) {
                    this.disabledHangUp = true;
                    this.disabledAnswerButton = true;
                    this.statusText = "Ready" ;
                }
            },
            deviceCanceled: function(val) {
                if (val) {
                    this.disabledHangUp = true;
                    this.disabledAnswerButton = true;
                    this.statusText = "Ready" ;
                }
            },
            deviceIncoming: function(val) {
                if (val) {
                    this.disabledHangUp = false;
                    this.disabledAnswerButton = false;
                    this.statusText = "Incoming call from " +  this.connection.parameters.From;
                    // Set a callback to be executed when the connection is accepted
                    this.connection.accept(this.acceptedCallback());
                }
            },
            deviceOffline: function(val) {
                if (val) {
                    this.setupClient();
                }
            }
        },
        data() {
            return {
                statusText: 'Connecting...',
                device: null,
                connection:null,
                disabledHangUp:true,
                disabledAnswerButton:true,
                deviceConnected:false,
                deviceReady:false,
                deviceError:false,
                deviceDisconnected:false,
                deviceCanceled:false,
                deviceIncoming:false,
                deviceOffline:false,
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
                        this.device.setup(response.token);
                        this.setupHandlers(this.device);
                    })
                    .catch(err => {
                        this.statusText = err + "Could not get a token from server!";
                    })
            },
            setupHandlers(device) {
                device.on('ready', function () {
                    this.deviceReady = true;
                });

                /* Report any errors to the call status display */
                device.on('error', function (error) {
                    this.deviceError = error;
                });

                /* Callback for when Twilio Client initiates a new connection */
                device.on('connect', function (connection) {
                    // Enable the hang up button and disable the call buttons
                    this.connection = connection;
                    this.deviceConnected = true;
                });

                /* Callback for when a call ends */
                device.on('disconnect', function(connection) {
                    // Disable the hangup button and enable the call buttons
                    this.deviceDisconnected = true;
                });

                /* Callback for when a call canceled */
                device.on('cancel', function(connection) {
                    // Disable the hangup button and enable the call buttons
                    this.deviceCanceled = true;
                });

                /* Callback for when Twilio Client receives a new incoming call */
                device.on('incoming', function(connection) {
                    this.deviceIncoming = true;
                });

                //reconnect
                device.on('offline', function() {
                    this.deviceOffline = true;
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
            },
            acceptedCallback(){
                this.statusText = "In call with customer";
            }
        }

    }
</script>

<style lang="scss" scoped>

</style>
