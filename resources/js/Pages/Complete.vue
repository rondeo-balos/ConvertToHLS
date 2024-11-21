<script setup>
import { onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';


/** working example from https://github.com/datlife
 * from this comment https://github.com/sampotts/plyr/issues/1741#issuecomment-640293554
 * codepen https://codepen.io/datlife/pen/dyGoEXo
 */
const props = defineProps(['playlist']);
const defaultOptions = ref({});

onMounted(() => {
    const videoElement = document.querySelector('video');

    if (Hls.isSupported()) {
        console.log( 'hls supported' );
        const hls = new Hls();
        hls.loadSource(props.playlist);

        // From the m3u8 playlist, hls parses the manifest and returns
        // all available video qualities. This is important, in this approach,
        // we will have one source on the Plyr player.
        hls.on(Hls.Events.MANIFEST_PARSED, function (event, data) {
            console.log( 'done parsing hls' );
            // Transform available levels into an array of integers (height values).
            const availableQualities = hls.levels.map( (level) => level.height );
            console.log( 'available qualities: ', availableQualities );

            // Add new qualities to option
            defaultOptions.value.quality = {
                default: availableQualities[0],
                options: availableQualities,
                // this ensures Plyr to use Hls to update quality level
                forced: true,        
                onChange: (e) => updateQuality(e),
            };

            // Initialize here
            const player = new Plyr(videoElement, defaultOptions.value);
            console.log( 'done Initialize' );
        });
        hls.attachMedia(videoElement);
        window.hls = hls;
        console.log( 'media attached' );
    } else {
        // default options with no quality update in case Hls is not supported
        const player = new Plyr(videoElement, defaultOptions.value);
        console.log( 'hls not supported' );
    }
});

const updateQuality = (newQuality) => {
    console.log( 'updating quality' );
    window.hls.levels.forEach( (level, levelIndex) => {
        if( level.height === newQuality ) {
            console.log( "Found quality match with " + newQuality );
            window.hls.currentLevel = levelIndex;
        }
    });
}
</script>

<template>
    <Head title="Conversion Completed!" />
    <video id="player" controls playsinline class="!w-full" preload="none">
        <source type="application/x-mpegURL" :src="playlist">
    </video>
</template>