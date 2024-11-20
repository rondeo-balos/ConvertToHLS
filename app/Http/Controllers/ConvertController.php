<?php

namespace App\Http\Controllers;

use App\Models\Convert;
use Illuminate\Http\Request;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ConvertController extends Controller {

    public function index( Request $request ) {
        return Inertia::render('Welcome', []);
    }

    public function complete( Request $request ) {
        return Inertia::render('Complete', [
            'playlist' => $request->playlist
        ]);
    }
    
    public function convert( Request $request ) {
        // Validate the uploaded video
        $request->validate([
            'video' => 'required|mimes:mp4|max:5242880' // Limit to 5GB for example
        ]);

        // Get the uploaded file
        $video = $request->file('video');
        $filename = pathinfo($video->getClientOriginalName(), PATHINFO_FILENAME);

        // Save the uploaded video locally
        $videoPath = $video->storeAs('videos', $video->getClientOriginalName());

        if( !$videoPath ) {
            throw new \Exception( 'Unable to upload file!' );
        }

        // Output HLS directory
        $hlsDirectory = storage_path("app/public/hls/{$filename}");
        if (!file_exists($hlsDirectory)) {
            mkdir($hlsDirectory, 0755, true);
        }

        // Process the video with FFmpeg
        $ffmpeg = FFMpeg::create();
        $video = $ffmpeg->open(storage_path("app/private/{$videoPath}"));

        // Define resolutions and corresponding bitrates
        $formats = [
            '240p' => [
                'bitrate' => 300, // 300 kbps
                'width' => 426,
                'height' => 240,
            ],
            '360p' => [
                'bitrate' => 500, // 500 kbps
                'width' => 640,
                'height' => 360,
            ],
            '480p' => [
                'bitrate' => 1000, // 1000 kbps
                'width' => 854,
                'height' => 480,
            ],
            '720p' => [
                'bitrate' => 2500, // 2500 kbps
                'width' => 1280,
                'height' => 720,
            ],
            '1080p' => [
                'bitrate' => 5000, // 5000 kbps
                'width' => 1920,
                'height' => 1080,
            ]
        ];

        // Process video for each resolution
        foreach ($formats as $resolution => $details) {
            $format = (new X264())->setKiloBitrate($details['bitrate'])->setAudioCodec("aac");

            // Apply resizing filter
            $video->filters()->resize(new Dimension($details['width'], $details['height']))->synchronize();

            // Save HLS for this resolution
            $video->save($format, "{$hlsDirectory}/{$resolution}.m3u8");
        }

        // Generate a master playlist
        $masterPlaylist = "#EXTM3U\n";
        foreach ($formats as $resolution => $format) {
            $bandwidth = $details['bitrate'] * 1000; // Convert kbps to bps
            $masterPlaylist .= "#EXT-X-STREAM-INF:BANDWIDTH={$bandwidth},RESOLUTION={$details['width']}x{$details['height']}\n";
            $masterPlaylist .= "{$resolution}.m3u8\n";
        }

        file_put_contents("{$hlsDirectory}/master.m3u8", $masterPlaylist);

        //return response()->json(['message' => 'HLS conversion completed!', 'playlist' => "storage/hls/{$filename}/master.m3u8"]);

        return Redirect::route( 'complete', [
            'playlist' => "storage/hls/{$filename}/master.m3u8"
        ]);
    }

}
