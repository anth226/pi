<template>
    <div class="status-box">
        <div class="row mb-2">
            <div class="col-lg-12 m-auto">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group row">
                            <div class="col-12">
                                <input id="call-status" class="form-control" type="text" placeholder="Connecting..." readonly>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-12">
                                <button class="btn btn-lg btn-danger hangup-button" disabled onclick="hangUp()">Hang up</button>
                                <button class="btn btn-lg btn-success answer-button" disabled>Answer call</button>
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
        methods: {

        },
    }
</script>

<style lang="scss" scoped>

</style>
