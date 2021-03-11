<template>
    <div class="flex border-b border-40 -mx-6 px-6">
        <div class="w-1/4 py-4">
            <h4 class="font-normal text-80">{{ field.name }}</h4>
        </div>
        <div class="w-3/4 py-4 break-words">
            <ul style="padding: 0;">
                <li v-for="image in field.previewUrl" class="preview">
                    <img class="img" :src="image" alt="">
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
export default {
    props: ['resource', 'resourceName', 'resourceId', 'field'],
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
    background-color: #fbfbfb;
    line-height: 174px;
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
</style>
