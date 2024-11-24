<?php
namespace App\Http\Controllers;

use Storage;

class SaveFile {

    public static function local( $fileReceived ) {
        $file = $fileReceived->getFile(); // get file
            
        $fileName = self::filename( $file );
        
        // Group files by mime type
        $mime = str_replace('/', '-', $file->getMimeType());
        // Group files by the date (week
        $dateFolder = date("Y-m-W");

        // Build the file path
        $filePath = "upload/{$mime}/{$dateFolder}/";
        $finalPath = storage_path("app/".$filePath);

        // move the file name
        $file->move($finalPath, $fileName);

        return [
            'path' => $filePath,
            'filename' => $fileName,
            'mime_type' => $mime
        ];
    }

    public static function supabase( $fileReceived ) {
        $file = $fileReceived->getFile();

        $fileName = self::filename( $file );

        $disk = Storage::disk( 's3' );
        $disk->putFileAs( 'videos', $file, $fileName );

        // Group files by mime type
        $mime = str_replace('/', '-', $file->getMimeType());

        // We need to delete the file when uploaded to s3
        unlink($file->getPathname());

        return response()->json([
            'path' => $disk->url( "videos/{$fileName}" ),
            'filename' => $fileName,
            'mime_type' =>$mime
        ]);
    }

    protected static function filename( $file ): string {
        $extension = $file->getClientOriginalExtension();
        $fileName = str_replace('.'.$extension, '', $file->getClientOriginalName()); //file name without extenstion
        $fileName .= '_' . md5(time()) . '.' . $extension; // a unique file name

        return $fileName;
    }

}