<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;

class ConvertController extends Controller {

    public function index( Request $request ) {
        return Inertia::render('Welcome', []);
    }

    public function complete( Request $request ) {
        return Inertia::render('Complete', [
            'playlist' => $request->playlist
        ]);
    }

    public function upload( Request $request ) {
        $receiver = new FileReceiver( 'file', $request, HandlerFactory::classFromRequest( $request ) );
        if( !$receiver->isUploaded() ) {
            // file not uploaded
        }

        $fileReceived = $receiver->receive();
        if( $fileReceived->isFinished() ) {
            return SaveFile::local( $fileReceived );
        }

        // return percentage information
        $handler = $fileReceived->handler();
        return [
            'done' => $handler->getPercentageDone(),
            'status' => true
        ];
    }
    
    public function convert( Request $request ) {
        // Validate the uploaded video
        $request->validate([
            'filename' => 'required|string',
            'video' => 'required|string',
            'resolutions' => 'required|array' // [ "240p", "360p", "480p", "720p", "1080p" ]
        ]);

        // Get the uploaded file
        $video = $request->video;
        $filename = $request->filename;
        $resolutions = $request->resolutions;
        
        try {
            $converted = Convert::convert( $filename, $video, $resolutions );
        } catch( \Exception $e ) {
            \Log::error( $e->getLine() );
            \Log::error( $e->getMessage() );
            unlink(storage_path( "app/{$video}{$filename}" ));
            return Redirect::back()->with( 'error', 'Unable to convert video' );
        }

        return Redirect::route( 'convert.complete', [
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
            
            $filename = pathinfo($video->getClientOriginalName(), PATHINFO_FILENAME);
            // Save the uploaded video locally
            $videoPath = $video->storeAs('videos', $video->getClientOriginalName());
            $converted = Convert::convert( $filename, $videoPath, $request->resolutions );

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

}
