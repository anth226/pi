<template>
    <div class="composer">
        <div class="m-1">
            <div class="row">
                <div class="col-md-10">
                    <div>
                        <textarea v-model="message" placeholder="Message..."></textarea>
                    </div>
                    <div>
                        <div class="row">
                            <div class="col-12">
                                <!--<input type="file" v-model="file" @change='upload_image' name="image" accept="image/*" >-->
                                <!--<button v-if="file.length > 0" class="btn btn-danger" title="remove image" @click="removeImage">x</button>-->
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-5">
                                <div class="image img-fluid img-circle my-2" v-html="get_image()"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 pl-md-0">
                    <button class="btn btn-primary" @click="send">Send</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                file: '',
                message: '',
                image: '',
                filename: '',
                filetype: ''
            };
        },
        methods: {
            upload_image(e){
                let file = e.target.files[0];

                if(file['size'] < 10485760 && (file['type']==='image/jpeg' || file['type']==='image/png'))
                {
                    let formData = new FormData();
                    formData.append("file", file);
                    axios.post('/support/sms_img', formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
                    })
                    .then((response) => {
                        if(response && response.data && response.data.url && response.data.filename){
                            this.image = response.data.url;
                            if(this.filename){
                                axios.post('/support/delete_sms_img', {filename: this.filename });
                            }
                            this.filename = response.data.filename;
                            this.filetype = file['type'];
                        }
                    })
                    .catch(err => {
                        if(err.message == 'CSRF token mismatch.'){
                            this.image = '';
                            this.filename = '';
                            this.filetype = '';
                            this.file = '';
                            alert('Your session has expired. Please refresh the page.');
                        }
                    });
                }else{
                    this.image = '';
                    this.filename = '';
                    this.filetype = '';
                    this.file = '';
                    alert('File size can not be bigger than 10 MB')
                }
            },
            //For getting Instant Uploaded Photo
            get_image(){
                if(this.image.length){
                   return '<a class="mb-2" href="'+this.image+'" data-fancybox="attachment">' +
                              '<img class="w-100" src="'+this.image+'" alt="attachment">' +
                          '</a>'
                       ;
                }
                return '';
            },
            removeImage(){
                if(this.image){
                    if(this.filename) {
                        axios.post('/support/delete_sms_img', {filename: this.filename});
                    }
                    this.image = '';
                    this.filename = '';
                    this.filetype = '';
                    this.file = '';
                }
            },
            send(e) {
                e.preventDefault();
                if (this.message == '' && this.image == '') {
                    return;
                }

                this.$emit('send',
                            {
                                message: this.message,
                                media:this.image,
                                filetype:this.filetype
                            }
                          );
                this.message = '';
                this.image = '';
                this.filename = '';
                this.filetype = '';
                this.file = '';
            }
        }
    }
</script>

<style lang="scss" scoped>
.composer textarea {
    width: 100%;
    height: 100%;
    border-radius: 3px;
    border: 1px solid lightgray;
    padding: 6px;
}
@media (max-width: 500px){
    input[name='image'] {
        max-width: 80%;
    }
}
</style>

