<?php

namespace App\Http\Controllers;

use App\Models\Convert;
use FFMpeg\Filters\Video\ResizeFilter;
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
            'video' => 'required|mimes:mp4,avi,mkv,mov,flv,wmv,webm,mpeg,mpg,ogv|max:5242880', // Limit to 5GB for example
            'resolutions' => 'required|array' // [ "240p", "360p", "480p", "720p", "1080p" ]
        ]);

        // Get the uploaded file
        $video = $request->file('video');
        
        try {
            $converted = $this->convertVideo( $video, $request->resolutions );
        } catch( \Exception $e ) {
            return Redirect::back()->with( 'error', 'Unable to convert video' );
        }

        return Redirect::route( 'complete', [
            'playlist' => "storage/hls/{$converted['uniqueKey']}/{$converted['filename']}/master.m3u8"
        ]);
    }

    public function api( Request $request ) {
        // Validate the uploaded video
        $request->validate([
            'video' => 'required|mimes:mp4,avi,mkv,mov,flv,wmv,webm,mpeg,mpg,ogv|max:5242880', // Limit to 5GB for example
            'resolutions' => 'required|array' // [ "240p", "360p", "480p", "720p", "1080p" ]
        ]);

        $video = $request->file('video');

        try {
            $converted = $this->convertVideo( $video, $request->resolutions );
        } catch( \Exception $e ) {
            return response()->json([
                'message' => 'Unable to convert video',
                'playlist' => null
            ]);
        }

        return response()->json([
            'message' => 'HLS conversion completed!',
            'playlist' => "storage/hls/{$converted['uniqueKey']}/{$converted['filename']}/master.m3u8"
        ]);
    }
    
    protected function convertVideo( $video, array $resolutions ): array {
        $filename = pathinfo($video->getClientOriginalName(), PATHINFO_FILENAME);

        // Save the uploaded video locally
        $videoPath = $video->storeAs('videos', $video->getClientOriginalName());

        if( !$videoPath ) {
            throw new \Exception( 'Unable to upload file!' );
        }

        $uniqueKey = fake()->unique()->randomKey();

        // Output HLS directory
        $hlsDirectory = storage_path("app/public/hls/{$uniqueKey}/{$filename}");
        if (!file_exists($hlsDirectory)) {
            mkdir($hlsDirectory, 0755, true);
        }

        // Process the video with FFmpeg
        $ffmpeg = FFMpeg::create();

        // Define resolutions and corresponding bitrates
        $formats = [
            '240p' => [
                'bitrate' => 500, // 500 kbps
                'width' => 426,
                'height' => 240,
            ],
            '360p' => [
                'bitrate' => 1000, // 1000 kbps
                'width' => 640,
                'height' => 360,
            ],
            '480p' => [
                'bitrate' => 2500, // 2500 kbps
                'width' => 854,
                'height' => 480,
            ],
            '720p' => [
                'bitrate' => 5000, // 5000 kbps
                'width' => 1280,
                'height' => 720,
            ],
            '1080p' => [
                'bitrate' => 8000, // 8000 kbps
                'width' => 1920,
                'height' => 1080,
            ]
        ];

        // Select only the requested resolutions
        $formats = array_intersect_key( $formats, array_flip($resolutions) );

        // Process video for each resolution
        foreach ($formats as $resolution => $details) {
            $video = $ffmpeg->open(storage_path("app/private/{$videoPath}"));
            $format = (new X264())->setKiloBitrate($details['bitrate'])->setAdditionalParameters([
                '-crf', '10',
                '-force_key_frames', 'expr:gte(t,n_forced*2)',
                '-preset', 'fast', // Encoding speed
                '-hls_list_size', '0'
            ])->setAudioCodec("aac");

            // Apply resizing filter
            $video->filters()->resize(new Dimension($details['width'], $details['height']))->synchronize();

            // Save HLS for this resolution
            $video->save($format, "{$hlsDirectory}/{$resolution}.m3u8");
        }

        // Generate a master playlist
        $masterPlaylist = "#EXTM3U\n";
        foreach ($formats as $resolution => $format) {
            $bandwidth = $format['bitrate'] * 1000; // Convert kbps to bps
            $masterPlaylist .= "#EXT-X-STREAM-INF:BANDWIDTH={$bandwidth},RESOLUTION={$format['width']}x{$format['height']}\n";
            $masterPlaylist .= "{$resolution}.m3u8\n";
        }

        file_put_contents("{$hlsDirectory}/master.m3u8", $masterPlaylist);
        
        // Delete original video
        unlink(storage_path("app/private/{$videoPath}"));

        $id = Convert::create([
            'user' => null,
            'directory' => $hlsDirectory,
            'resolutions' => json_encode($resolutions)
        ])->id;

        return [
            'id' => $id,
            'uniqueKey' => $uniqueKey,
            'filename' => $filename,
            'directory' => $hlsDirectory
        ];
    }

}
