<?php

namespace App\Traits;

use Vinkla\Hashids\Facades\Hashids;

trait HasPublicId
{
    /**
     * Get the public hashed ID.
     */
    public function getPublicIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    /**
     * Resolve a model instance by its public ID.
     * 
     * @param string $publicId
     * @return self|null
     */
    public static function findByPublicId($publicId)
    {
        if (empty($publicId)) {
            return null;
        }

        $decoded = Hashids::decode($publicId);

        if (empty($decoded)) {
            return null;
        }

        return static::find($decoded[0]);
    }

    /**
     * Resolve a model instance by public ID or fail.
     * 
     * @param string $publicId
     * @return self
     */
    public static function findByPublicIdOrFail($publicId)
    {
        $instance = static::findByPublicId($publicId);

        if (!$instance) {
            abort(404, 'Resource not found');
        }

        return $instance;
    }
}
