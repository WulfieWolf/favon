<?php

namespace App\Services;

use App\Models\User;
use Imagick;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class PhotoProcessingService
{
    public function process(int $photoId): void
    {
        $photo = DB::table('photos')->where('id', $photoId)->first();

        if (! $photo || $photo->status !== 'processing' || ! $photo->source_path) {
            return;
        }

        $source = Storage::disk('local')->path($photo->source_path);
        $image = null;

        try {
            if (!class_exists(Imagick::class)) {
                throw new RuntimeException('Die Imagick-Erweiterung ist auf diesem Server nicht verfügbar.');
            }

            if (!is_file($source)) {
                throw new RuntimeException('Die temporäre Upload-Datei wurde nicht gefunden.');
            }

            $image = new Imagick();
            $image->readImage($source);

            if ($image->getNumberImages() !== 1) {
                throw new RuntimeException('Animierte oder mehrseitige Bilder werden nicht unterstützt.');
            }

            $image->setIteratorIndex(0);
            $image->autoOrient();

            if ($image->getImageColorspace() !== Imagick::COLORSPACE_SRGB) {
                $image->transformImageColorspace(Imagick::COLORSPACE_SRGB);
            }

            $image->stripImage();

            $detail = $this->writeVariant($image, (string) $photo->uuid, 'detail');
            $preview = $this->writeVariant($image, (string) $photo->uuid, 'preview');

            $isExternalAutoApprove = $photo->internal_comment === \App\Services\Imports\ExternalPlacePhotoService::AUTO_APPROVE_EXTERNAL_COMMENT;

            DB::table('photos')->where('id', $photoId)->update([
                'storage_path' => $detail['path'],
                'preview_path' => $preview['path'],
                'mime_type' => 'image/webp',
                'file_size' => $detail['size'],
                'preview_file_size' => $preview['size'],
                'width' => $detail['width'],
                'height' => $detail['height'],
                'original_filename' => null,
                'source_path' => null,
                'status' => $isExternalAutoApprove ? 'approved' : 'pending',
                'moderated_at' => $isExternalAutoApprove ? now() : $photo->moderated_at,
                'processing_error' => null,
                'updated_at' => now(),
            ]);

            Storage::disk('local')->delete($photo->source_path);

            if ($photo->internal_comment === PlacePhotoService::AUTO_APPROVE_INTERNAL_COMMENT) {
                $moderator = User::find($photo->user_id);
                if ($moderator) {
                    try {
                        app(PlacePhotoService::class)->approve($moderator, $photoId, false);
                    } catch (Throwable $exception) {
                        report($exception);
                    }
                }
            }
        } catch (Throwable $exception) {
            Storage::disk('local')->delete([
                'photos/'.$photo->uuid.'/detail.webp',
                'photos/'.$photo->uuid.'/preview.webp',
            ]);
            DB::table('photos')->where('id', $photoId)->update([
                'status' => 'processing_failed',
                'source_path' => null,
                'processing_error' => mb_substr($exception->getMessage(), 0, 2000),
                'updated_at' => now(),
            ]);

            Storage::disk('local')->delete($photo->source_path);

            throw $exception;
        } finally {
            if ($image instanceof Imagick) {
                $image->clear();
                $image->destroy();
            }
        }
    }

    private function writeVariant(Imagick $source, string $uuid, string $variant): array
    {
        $settings = config('photos.variants.'.$variant);
        $maxSide = (int) ($settings['max_side'] ?? 0);
        $quality = (int) ($settings['quality'] ?? 80);

        if ($maxSide < 1) {
            throw new RuntimeException('Ungültige Konfiguration der Fotovariante.');
        }

        $image = clone $source;
        $width = $image->getImageWidth();
        $height = $image->getImageHeight();
        $largest = max($width, $height);

        if ($largest > $maxSide) {
            $scale = $maxSide / $largest;
            $width = max(1, (int) round($width * $scale));
            $height = max(1, (int) round($height * $scale));
            $image->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1, true);
        }

        $image->setImageFormat('webp');
        $image->setImageCompressionQuality($quality);
        $image->setOption('webp:method', '6');
        $image->stripImage();

        $path = 'photos/'.$uuid.'/'.$variant.'.webp';
        $blob = $image->getImageBlob();

        if (!Storage::disk('local')->put($path, $blob)) {
            throw new RuntimeException('Die verarbeitete Fotodatei konnte nicht gespeichert werden.');
        }

        $result = [
            'path' => $path,
            'size' => strlen($blob),
            'width' => $image->getImageWidth(),
            'height' => $image->getImageHeight(),
        ];

        $image->clear();
        $image->destroy();

        return $result;
    }
}
