<?php

use App\Models\UserProfile;
use App\Models\Document;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');

        $secure = function (string $path, string $label) use ($public, $private): void {
            if (!$public->exists($path)) {
                return;
            }

            $contents = $public->get($path);
            if (!$private->exists($path) && !$private->put($path, $contents)) {
                throw new RuntimeException("Could not secure {$label}");
            }
            if (hash('sha256', $contents) !== hash('sha256', $private->get($path))) {
                throw new RuntimeException("File verification failed for {$label}");
            }

            if (!$public->delete($path) || $public->exists($path)) {
                throw new RuntimeException("Could not remove public {$label}");
            }
        };

        UserProfile::whereNotNull('id_proof_path')->chunkById(100, function ($profiles) use ($secure) {
            foreach ($profiles as $profile) {
                $path = $profile->id_proof_path;
                if (str_starts_with($path, 'id_proofs/')) {
                    $secure($path, "identity proof for profile {$profile->id}");
                }
            }
        });

        Document::whereNotNull('file_path')->chunkById(100, function ($documents) use ($secure) {
            foreach ($documents as $document) {
                if (str_starts_with($document->file_path, 'documents/')) {
                    $secure($document->file_path, "document {$document->id}");
                }
            }
        });
    }

    public function down(): void
    {
        // Identity proofs and member documents must remain private.
    }
};
