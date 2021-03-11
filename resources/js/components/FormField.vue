<template>
    <default-field :field="field" :errors="errors" :show-help-text="showHelpText">
        <template slot="field">
            <input
                :id="field.name"
                type="hidden"
                class="w-full form-control form-input form-input-bordered"
                :class="errorClasses"
                :placeholder="field.name"
                v-model="value"
            />

            <input ref="dragUploadImg" type="file" multiple style="position:absolute; clip-path: circle(0);" accept="image/png, image/jpeg, image/gif, image/jpg"
                   @change="uploadImg($event)">

            <div id="weiwait-modal" v-if="showCropper">
                <div>
                </div>
                <vue-cropper
                    ref="cropper"
                    v-bind="cropper"
                    class="cropper-container">
                </vue-cropper>
                <div class="footer">
                    <input @click="$emit('cropping', 'cropping')" class="btn btn-xs btn-primary inline-block items-center mr-2" type="button" value="裁剪">
                    <label style="line-height: 2.15" class="btn btn-xs btn-primary inline-block items-center mr-2" for="uploads">更换图片</label>
                    <input type="file" id="uploads" style="position:absolute; clip-path: circle(0);" accept="image/png, image/jpeg, image/gif, image/jpg"
                           @change="changeImg($event)">
                    <button type="button" class="btn btn-xs btn-primary inline-block items-center mr-2" v-on:click="loopMultiple()">倍数 {{cropper.enlarge}}</button>
                    <input class="btn btn-xs btn-primary inline-block items-center mr-2" type="button" v-on:click="$emit('cropping', 'close')" value="关闭">
                </div>
            </div>

            <div v-if="!preview"
                 @dragleave.prevent="dragLeave()"
                 @dragenter.prevent="dragEntering()"
                 @dragover.prevent="()=>dragOver()"
                 @drop.prevent="()=>drop($event)"
                 @click.prevent="()=>dragRegionClick()"
                 v-bind:class="{'drag-enter': dragEnter}"
                 id="drag-region">
                点击或拖拽文件到此
            </div>

            <draggable v-else :list="previewUrls" @change="handleMoved" draggable=".drag-item">
                    <div v-for="(image, index) in previewUrls" :key="index"
                         class="preview drag-item"
                         @click="switchCrop(index)"
                         @mouseover="hoverIndex = index"
                         @mouseleave="hoverIndex = false"
                    >
                        <img class="img" :src="image" alt="">
                        <span
                            :class="['delete', {hover: index === hoverIndex}]"
                            @click.stop="deleteItem(index)"
                        >
                            移除
                        </span>
                    </div>
                    <div slot="footer"
                        @dragleave.prevent="dragLeave()"
                        @dragenter.prevent="dragEntering()"
                        @dragover.prevent="()=>dragOver()"
                        @drop.prevent="()=>drop($event)"
                        @click.prevent.stop="()=>dragRegionClick()"
                        v-bind:class="{'drag-enter': dragEnter, 'preview': 1}"
                        id="drag-region-li">
                        点击或拖拽文件到此
                    </div>
            </draggable>

        </template>
    </default-field>
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova'
import { VueCropper } from 'vue-cropper'
import draggable from 'vuedraggable'

export default {
    mixins: [FormField, HandlesValidationErrors],

    data() {
        return {
            dragEnter: false,
            cropper: {
                img: '',
                outputType: 'png',
                autoCrop: true,
                enlarge: 1,
            },
            showCropper: false,
            preview: false,
            cropperIndex: 0,
            previewUrls: [],
            value: [
                {
                    originalName: '',
                    image: ''
                }
            ],
            stayFiles: [],
            hoverIndex: false
        }
    },

    props: ['resourceName', 'resourceId', 'field'],

    methods: {
        /*
         * Set the initial, internal value for the field.
         */
        setInitialValue() {
            this.value = this.field.value || []
            this.cropper = {...this.cropper, ...this.field.cropper}
            if (this.value.length > 0) {
                this.preview = true
                this.showCropper = false
                this.cropper.img = this.field.previewUrl
                this.previewUrls = this.field.previewUrl
            }
        },

        /**
         * Fill the given FormData object with the field's internal value.
         */
        fill(formData) {
            formData.append(this.field.attribute, this.value ? JSON.stringify(this.value) : '')
        },
        switchCrop(index = 0) {
            this.cropperIndex = index
            this.cropper.img = this.previewUrls[index] || this.field.previewUrls[index] || ''
            this.cropping()
        },
        uploadImg(e) {
            if (e.target.files.length < 1) return;
            this.awaitCroppers(e.target.files)
        },
        changeImg(e) {
            if (e.target.files.length < 1) return;
            this.readyCropper(e.target.files[0], this.cropperIndex)
        },
        loopMultiple() {
            this.cropper.enlarge >= 3 ? this.cropper.enlarge = 1 : this.cropper.enlarge++
        },
        cropping() {
            return new Promise(resolve => {
                this.showCropper = true
                this.$once('cropping', e=>{
                    switch (e) {
                        case 'cropping':
                            this.$refs.cropper.startCrop()
                            this.$refs.cropper.getCropData(data=>{
                                if ('object' !== typeof this.value[this.cropperIndex]) {
                                    this.value[this.cropperIndex] = {
                                        image: data
                                    }
                                } else {
                                    this.value[this.cropperIndex].image = data
                                }
                                this.preview = true
                                this.showCropper = false
                                this.previewUrls[this.cropperIndex] = data

                                resolve()
                            })
                            break
                        case 'close':
                            this.showCropper = false
                            this.preview = true
                            resolve()
                            break
                    }
                })
            })
        },
        readyCropper(file, cropperIndex = false) {
            return new Promise((resolve, reject) => {
                if ('image' !== file.type.substring(0, 5)) reject();

                let reader = new FileReader()
                reader.readAsDataURL(file)
                reader.onload = e => {
                    this.cropper.img = e.target.result
                    let item = {}
                    item.originalName = file.name
                    item.image = e.target.result
                    if (false === cropperIndex) {
                        this.cropperIndex = this.value.length || 0
                        this.previewUrls.push(e.target.result)
                        this.value.push(item)
                    } else {
                        this.value[cropperIndex] = item
                    }
                    this.cropping().then(()=>resolve())
                }
            })
        },
        awaitCroppers: async function (files) {
            for (let file of files) {
                await this.readyCropper(file)
            }
        },
        dragEntering() {
            this.dragEnter = true
        },
        dragLeave() {
            this.dragEnter = false
        },
        dragOver() {
            // this.dragEnter = false
        },
        dragRegionClick() {
            this.$refs.dragUploadImg.click()
        },
        drop(e) {
            e.preventDefault()
            const files = e.dataTransfer.files
            this.dragEnter = false
            if (files.length < 1) return;

            this.awaitCroppers(files)
            this.dragEnter = false
        },
        handleMoved(e) {
            let newIndex  = e.moved.newIndex;
            let oldIndex = e.moved.oldIndex;
            [this.value[newIndex], this.value[oldIndex]] = [this.value[oldIndex], this.value[newIndex]]
        },
        deleteItem(index) {
            this.previewUrls.splice(index, 1)
            this.value.splice(index, 1)
        },
    },
    mounted: async function () {
        this.cropper.img = this.previewUrl || ''
    },
    components: {
        VueCropper, draggable,
    },
}
</script>

<style scoped lang="scss">
.preview {
    float: left;
    margin: 10px 0 0 10px;
    list-style: none;
    width: 174px;
    height: 174px;
    border: 1px solid #BACAD6;
    border-radius: 4%;
    text-align: center;
    background-color: #fcfcfc;
    line-height: 174px;
    position: relative;
}
.preview > .img {
    padding: 4px;
    border-radius: 4%;
    max-width: 100%;
    max-height: 100%;
    display: inline-block;
    vertical-align: middle;
}
.preview:nth-child(4n+1) {
    margin: 10px 0 0 0;
}
#drag-region {
    height: 270px;
    border: 1px solid #BACAD6;
    text-align: center;
    line-height: 270px;
    font-size: 30px;
    color: #BACAD6;
    border-radius: 12px;
    cursor: pointer;
}
#drag-region-li {
    border: 1px solid #BACAD6;
    text-align: center;
    line-height: 174px;
    color: #BACAD6;
    border-radius: 8px;
    cursor: pointer;
    user-select: none;
}
.drag-enter {
    border: 3px solid var(--primary) !important;
}
.delete {
    width: 40px;
    height: 40px;
    opacity: 1;
    position: absolute;
    right: 0;
    bottom: 0;
    text-align: center;
    line-height: 40px;
    cursor: pointer;
    display: none;
}
.hover {
    display: block;
    color: #d91414;
}
#weiwait-modal {
    width: 640px;
    height: 420px;
    background-color: #ffffff;
    position: fixed;
    top: 25%;
    left: 38%;
    box-shadow: 12px 12px 80px -12px;
    z-index: 9;
    .footer {
        height: 60px;
        line-height: 60px;
        padding-left: .5rem;
    }
}
</style>
