<?php

namespace App\Helpers;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;


class FileUploadHelper
{

    public static function multipleBinaryFileUpload($requestFiles, $fileKey)
    {
        $images = [];
        if (isset($requestFiles)) {
            $files = $requestFiles;
            foreach ($files as $file) {
                $uniqueId = rand(10, 100000);
                $name               = $uniqueId . '_' . date("Y-m-d") . '_' . time();
                $fileName = $file->storeOnCloudinaryAs($fileKey, $name)->getSecurePath();
                $images[]           = $fileName;
            }
        }
        return $images;
    }

    public static function singleBinaryFileUpload($requestFile, $fileKey)
    {
        $imageUrl = "";
        if (isset($requestFile)) {
            $file = $requestFile;

            $uniqueId = rand(10, 100000);
            $name = $uniqueId . '_' . date("Y-m-d") . '_' . time();
            $fileName = $file->storeOnCloudinaryAs($fileKey, $name)->getSecurePath();
            $imageUrl = $fileName;
        }
        return $imageUrl;
    }

    // public static function singleStringFileUpload($requestFile, $fileKey)
    // {

    //     $fileUrl = '';
    //     // decode the base64 file
    //     $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $requestFile));

    //     // save it to temporary dir first.
    //     $uniqueId = rand(10, 100000);
    //     $tmpFilePath = sys_get_temp_dir() . '/' . $uniqueId . '_' . date("Y-m-d") . '_' . time();
    //     file_put_contents($tmpFilePath, $fileData);

    //     // this just to help us get file info.
    //     $tmpFile = new File($tmpFilePath);

    //     $file = new UploadedFile(
    //         $tmpFile->getPathname(),
    //         $tmpFile->getFilename(),
    //         $tmpFile->getMimeType(),
    //         0,
    //         true
    //     );

    //     $fileName = $file->storeOnCloudinaryAs($fileKey, $tmpFilePath)->getSecurePath();
    //     $fileUrl = $fileName;

    //     return $fileUrl;
       
    // }

    public static function singleStringFileUpload($requestFile, $fileKey)
    {
        $fileUrl = '';

        // Decode the base64 file
        $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $requestFile));

        if ($fileData === false) {
            throw new \Exception('Invalid base64 string.');
        }

        // Save it to a temporary dir
        $uniqueId = uniqid();
        $tmpFilePath = sys_get_temp_dir() . '/' . $uniqueId . '' . date("Y-m-d") . '' . time();
        file_put_contents($tmpFilePath, $fileData);

        // Ensure the temporary file was created
        if (!file_exists($tmpFilePath)) {
            throw new \Exception('Failed to create temporary file.');
        }

        // Get file info
        $tmpFile = new File($tmpFilePath);

        // Create UploadedFile instance
        $file = new UploadedFile(
            $tmpFile->getPathname(),
            $tmpFile->getFilename(),
            $tmpFile->getMimeType(),
            0,
            true
        );

        // Ensure the file key is valid
        if (!preg_match('/^[\w\-\/]+$/', $fileKey)) {
            throw new \Exception('Invalid file key for Cloudinary.');
        }

        // Upload to Cloudinary
        $result = Cloudinary::upload($file->getRealPath(), [
            'public_id' => $fileKey,
        ]);

        // Get the URL of the uploaded file
        $fileUrl = $result->getSecurePath();

        // Cleanup the temporary file
        unlink($tmpFilePath);
        return $fileUrl;
    }

    public static function multipleStringFileUpload($requestFiles, $fileKey)
    {

        $fileUrl = [];
        if (isset($requestFiles)) {
            $files = $requestFiles;
            foreach ($files as $file) {
                // decode the base64 file
                $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $file));

                // save it to temporary dir first.
                $uniqueId = rand(10, 100000);
                $tmpFilePath = sys_get_temp_dir() . '/' . $uniqueId . '_' . date("Y-m-d") . '_' . time();
                file_put_contents($tmpFilePath, $fileData);

                // this just to help us get file info before we use on cloudinary.
                $tmpFile = new File($tmpFilePath);

                $file = new UploadedFile(
                    $tmpFile->getPathname(),
                    $tmpFile->getFilename(),
                    $tmpFile->getMimeType(),
                    0,
                    true
                );

                $fileName = $file->storeOnCloudinaryAs($fileKey, $tmpFilePath)->getSecurePath();

                $fileUrl = $fileName;

            }
        }

        return $fileUrl;
       
    }
}
