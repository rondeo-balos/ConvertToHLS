<script setup>
import CheckboxVal from '@/Components/CheckboxVal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { useAutoAnimate } from '@formkit/auto-animate/vue'

const inputFile = ref(null);
const progress = ref(0);
const showEmpty = ref(false);
const error = ref(null);

const form = useForm({
    video: '',
    filename: '',
    resolutions: [
        "240p",
        "360p",
        "480p",
        "720p",
        "1080p"
    ]
});

let resumable = new Resumable({
    target: route( 'upload' ),
    query: {
        _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
    fileType: ['mp4','avi','mkv','mov','flv','wmv','webm','mpeg','mpg','ogv'],
    chunkSize: 10*1024*1024,
    headers: {
        'Accept': 'application/json'
    },
    testChunks: false,
    throttleProgressCallbacks: 1
});
onMounted(() => resumable.assignBrowse(inputFile.value));

resumable.on( 'fileAdded', (file) => {
    error.value = null;
    form.filename = file.file.name;
    
    if( !form.resolutions || form.resolutions.length <= 0 ) {
        showEmpty.value = true;
        return;
    }
    resumable.upload() // to actually start uploading.
});

resumable.on( 'fileProgress', (file) => {
    progress.value = Math.floor(file.progress() * 100);
});

resumable.on( 'fileError', (file, response) => {
    error.value = 'File uploading error.';
});

resumable.on( 'fileSuccess', (file, response) => {
    response = JSON.parse(response);
    console.log( response );
    form.video = response.path;
    form.filename = response.filename;
    form.post( route('convert') );
});

const [autoAnimate] = useAutoAnimate()
</script>

<template>
    <Head title="Upload Video" />
    
    <div class="w-full min-h-screen flex flex-col gap-10 justify-center items-center" ref="autoAnimate">
        <h1 class="text-3xl font-bold text-gray-900">Convert Any Video Into Streamable (HLS) Format</h1>
        <form @submit.prevent="" class="border-4 border-dashed border-slate-400 bg-gray-100 rounded-xl shadow p-10 flex flex-col gap-5 justify-center items-center mx-auto max-w-[600px] max-h-[500px] w-full h-full">
            <template v-if="progress <= 0">
                <div class="flex flex-row items-center">
                    <CheckboxVal v-model="form.resolutions" value="240p" class="cursor-pointer" @checked="showEmpty = false"/><InputLabel class="ms-2 me-8">240p</InputLabel>
                    <CheckboxVal v-model="form.resolutions" value="360p" class="cursor-pointer" @checked="showEmpty = false"/><InputLabel class="ms-2 me-8">360p</InputLabel>
                    <CheckboxVal v-model="form.resolutions" value="480p" class="cursor-pointer" @checked="showEmpty = false"/><InputLabel class="ms-2 me-8">480p</InputLabel>
                    <CheckboxVal v-model="form.resolutions" value="720p" class="cursor-pointer" @checked="showEmpty = false"/><InputLabel class="ms-2 me-8">720p</InputLabel>
                    <CheckboxVal v-model="form.resolutions" value="1080p" class="cursor-pointer" @checked="showEmpty = false"/><InputLabel class="ms-2">1080p</InputLabel>
                </div>

                <div class="group bg-red-600 border rounded-lg h-12 relative w-full flex items-center justify-center">
                    <input ref="inputFile" type="file" class="absolute left-0 top-0 right-0 bottom-0 opacity-0 h-full w-full cursor-pointer" accept="video/mp4,video/x-msvideo,video/x-matroska,video/quicktime,video/x-flv,video/x-ms-wmv,video/webm,video/mpeg,video/ogg">
                    <p class="font-semibold text-white" v-html="form.video.name ?? `<i class='fa-solid fa-file-circle-plus me-3'></i> Click to Choose a file`"></p>
                </div>
                
                <span v-show="showEmpty" class="text-sm text-red-700">Please choose at least one of these resolutions</span>
                <!--<PrimaryButton type="submit" class="py-3 px-10 !font-bold !text-base">Upload</PrimaryButton>-->
            </template>
            <progress v-if="progress > 0" :value="progress" max="100" class="h-2 rounded-lg overflow-hidden w-full progress">
                {{ progress }}%
            </progress>
            <span v-if="progress >= 100" class="flex flex-row items-center justify-center gap-2">
                <i class="fa-solid fa-spinner animate-spin"></i>
                Video Processing & Converting...
            </span>
            <span v-else-if="progress > 0" class="flex flex-row items-center justify-center gap-2">
                <i class="fa-solid fa-cloud-arrow-up animate-bounce"></i>
                Uploading Video. Please wait...
            </span>
            {{ error ?? form.errors }}
        </form>
        <p class="text-sm"><a href="#" class="text-blue-700">How does this work?</a></p>
        <p class="text-xs">By proceeding, you agree to our <a href="#" class="text-blue-700">Terms of Use</a>.</p>
    </div>

</template>

<style scoped>
.progress::-webkit-progress-bar {
    background-color: #96a0b0;
}
.progress::-webkit-progress-value {
    background-color: #dc2625;
}
</style>