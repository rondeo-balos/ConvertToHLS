<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    video: ''
});

const handleUpload = () => {
    form.post( route('convert'), {
        forceFormData: true
    } );
}
</script>

<template>
    <Head title="Upload Video" />
    
    <form @submit.prevent="handleUpload">
        <input type="file" @input="form.video = $event.target.files[0]">
        {{ form.errors.video }}
        <progress v-if="form.progress" :value="form.progress.percentage" max="100">
        {{ form.progress?.percentage }}%
        </progress>
        <span v-show="form.progress?.percentage == 100">Processing...</span>
        <button type="submit">Submit</button>
    </form>

</template>