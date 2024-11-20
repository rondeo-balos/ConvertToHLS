<script setup>
import { onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps([ 'playlist' ]);

onMounted(() => {
    const videoElement = document.querySelector('video');
    const player = new Plyr(videoElement, {
        settings: ['quality'], // Enable quality selector in settings
    });

    if (Hls.isSupported()) {
        const hls = new Hls();
        hls.loadSource(props.playlist);
        hls.attachMedia(videoElement);

        // Handle quality levels when manifest is parsed
        hls.on(Hls.Events.MANIFEST_PARSED, () => {
            const availableQualities = hls.levels.map((level, index) => ({
                label: `${level.height}p`,
                value: index,
                default: level.height === 720, // Default to 720p if available
            }));

            // Set default quality
            const defaultQuality = availableQualities.find((q) => q.default) || availableQualities[0];
            hls.currentLevel = defaultQuality.value;

            // Update Plyr with quality options
            player.options.quality = {
                default: defaultQuality.value,
                options: availableQualities.map((q) => q.value),
                forced: true,
                onChange: (newQuality) => {
                    hls.currentLevel = newQuality; // Set quality in HLS.js
                },
            };

            // Refresh Plyr to reflect new quality settings
            player.config.settings.quality = availableQualities.map((q) => q.value);
            player.quality = defaultQuality.value;
        });

        // Add HLS instance to global scope for debugging
        window.hls = hls;
    } else {
        alert('HLS not supported in this browser');
    }
});
</script>

<template>
    <Head title="Conversion Completed!" />
    <video id="player" controls playsinline class="!w-full" preload="none"></video>
</template>