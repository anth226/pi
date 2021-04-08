<template>
    <div class="conversation">
        <StatusBox />
    </div>
</template>

<script>
    import StatusBox from './StatusBox';

    export default {
        components: {StatusBox}
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
