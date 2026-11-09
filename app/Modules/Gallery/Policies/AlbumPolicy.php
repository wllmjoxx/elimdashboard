<?php

namespace App\Modules\Gallery\Policies;

use App\Models\User;
use App\Modules\Gallery\Models\Album;
use App\Modules\Gallery\Models\AlbumPhoto;

/**
 * PRD 4.2: Pelayan uploads photos; Admin manages albums, order and
 * placement. Branch scoping comes from BelongsToChurch on Album.
 */
class AlbumPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('gallery.view');
    }

    public function upload(User $user, Album $album): bool
    {
        return $user->can('gallery.upload');
    }

    public function manage(User $user): bool
    {
        return $user->can('gallery.manage');
    }

    /**
     * Admin edits any photo; Pelayan edits and removes their own uploads.
     */
    public function editPhoto(User $user, Album $album, AlbumPhoto $photo): bool
    {
        return $user->can('gallery.manage')
            || ($user->can('gallery.upload') && $photo->uploaded_by === $user->id);
    }
}
