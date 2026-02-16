<?php

namespace App\Observers;

use App\Models\Site;

class SiteObserver
{
    /**
     * Handle the Site "creating" event.
     */
    public function creating(Site $site): void
    {
        if (is_null($site->mcs_id)) {
            $max = Site::max('mcs_id') ?? 0;
            $site->mcs_id = $max + 1;
        }
    }
}
