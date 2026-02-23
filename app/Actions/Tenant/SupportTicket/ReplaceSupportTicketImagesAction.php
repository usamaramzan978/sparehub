<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SupportTicket;

use App\Models\SupportTicket;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class ReplaceSupportTicketImagesAction
{
    /**
     * @param  array<int, UploadedFile>|UploadedFile|null  $uploadedImages
     */
    public function handle(SupportTicket $supportTicket, array|UploadedFile|null $uploadedImages): void
    {
        $images = is_array($uploadedImages) ? $uploadedImages : [$uploadedImages];
        $images = array_values(array_filter($images, fn (?UploadedFile $image): bool => $image instanceof UploadedFile));

        $this->deleteTicketImages($supportTicket);

        if ($images === []) {
            $supportTicket->update(['image_paths' => []]);

            return;
        }

        $storedPaths = [];
        foreach ($images as $image) {
            $storedPaths[] = (string) $image->store('support-tickets', 'public');
        }

        $supportTicket->update(['image_paths' => $storedPaths]);
    }

    private function deleteTicketImages(SupportTicket $supportTicket): void
    {
        $paths = $supportTicket->image_paths ?? [];

        if (! is_array($paths) || $paths === []) {
            return;
        }

        foreach ($paths as $path) {
            if (is_string($path) && $path !== '') {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
