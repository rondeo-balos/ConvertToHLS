<?php
namespace App\Http\Controllers;

use FFMpeg\Coordinate\Dimension;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Storage;

class Convert {

    public static function convert( $filename, $videoPath, array $resolutions ): array {

        if( !$videoPath ) {
            throw new \Exception( 'Unable to upload file!' );
        }

        $uniqueKey = date('YmdHis');

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
            $video = $ffmpeg->open(storage_path("app/{$videoPath}{$filename}"));
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
        self::zipPlaylist( $hlsDirectory );
        
        // Delete original video
        unlink(storage_path("app/{$videoPath}{$filename}"));

        $id = \App\Models\Convert::create([
            'user' => null,
            'directory' => "storage/hls/{$uniqueKey}/{$filename}/",
            'resolutions' => json_encode($resolutions)
        ])->id;

        return [
            'id' => $id,
            'uniqueKey' => $uniqueKey,
            'filename' => $filename,
            'directory' => $hlsDirectory
        ];
    }

    private static function zipPlaylist( $hlsDirectory ) {
        $zip = new \ZipArchive;
        $filename = "{$hlsDirectory}/master.zip";

        if( $zip->open( $filename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) === TRUE ) {
            $files = glob($hlsDirectory . '/*'); // Scan all files in the directory manually
            // Debugging: Output list of files to process
            if( empty($files) ) {
                throw new \Exception("No files found in directory: {$hlsDirectory}");
            }

            foreach( $files as $file ) {
                if( $file == "{$hlsDirectory}/.gitignore" || $file == "{$hlsDirectory}/master.zip" )
                    continue; // Skip these files

                //\Log::info( "current file iteration: {$file}" );
                
                $relativeNameInZipFile = str_replace("{$hlsDirectory}/", '', $file);
                $zip->addFile("{$file}", $relativeNameInZipFile);
            }

            if (!$zip->close()) {
                throw new \Exception("Failed to create ZIP file: {$filename}");
            }
        } else {
            throw new \Exception("Could not open ZIP file for writing: {$filename}");
        }
    }

}