<script setup>
import CheckboxVal from '@/Components/CheckboxVal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    video: '',
    resolutions: []
});

const showEmpty = ref(false);
const handleUpload = () => {
    console.log( form.resolutions );
    if( !form.resolutions || form.resolutions.length <= 0 ) {
        showEmpty.value = true;
        return;
    }
    form.post( route('convert'), {
        forceFormData: true
    });
}
</script>

<template>
    <Head title="Upload Video" />
    
    <div class="w-full min-h-screen flex flex-col gap-10 justify-center items-center">
        <h1 class="text-3xl font-bold text-gray-900">Convert Any Video into streamable (HLS) format</h1>
        <form @submit.prevent="handleUpload" class="border-4 border-dashed border-slate-400 bg-gray-100 rounded-xl shadow p-8 flex flex-col gap-5 justify-center items-center mx-auto">
            <div class="group bg-red-600 border rounded-lg h-12 relative w-full flex items-center justify-center">
                <input type="file" @input="form.video = $event.target.files[0]" class="absolute left-0 top-0 right-0 bottom-0 opacity-0 h-full w-full cursor-pointer">
                <p class="font-semibold text-white">{{ form.video.name ?? 'Click to Choose a file' }}</p>
            </div>
            
            <span v-show="showEmpty" class="text-sm text-red-700">Please choose at least one of these resolutions</span>
            <div class="flex flex-row items-center">
                <CheckboxVal v-model="form.resolutions" value="240p" class="cursor-pointer" @checked="showEmpty = false"/><InputLabel class="ms-2 me-8">240p</InputLabel>
                <CheckboxVal v-model="form.resolutions" value="360p" class="cursor-pointer" @checked="showEmpty = false"/><InputLabel class="ms-2 me-8">360p</InputLabel>
                <CheckboxVal v-model="form.resolutions" value="480p" class="cursor-pointer" @checked="showEmpty = false"/><InputLabel class="ms-2 me-8">480p</InputLabel>
                <CheckboxVal v-model="form.resolutions" value="720p" class="cursor-pointer" @checked="showEmpty = false"/><InputLabel class="ms-2 me-8">720p</InputLabel>
                <CheckboxVal v-model="form.resolutions" value="1080p" class="cursor-pointer" @checked="showEmpty = false"/><InputLabel class="ms-2 me-8">1080p</InputLabel>
            </div>
            {{ form.errors.video }}
            <progress v-if="form.progress" :value="form.progress.percentage" max="100">
            {{ form.progress?.percentage }}%
            </progress>
            <span v-show="form.progress?.percentage == 100">Processing...</span>
            <PrimaryButton type="submit" class="py-3 px-10 !font-bold !text-base">Upload</PrimaryButton>
        </form>
        <p class="text-xs">By proceeding, you agree to our Terms of Use.</p>
    </div>

</template>