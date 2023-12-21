<?php

namespace Laravel\Nova\Trix;

use Illuminate\Http\Request;

class DiscardPendingAttachments
{
    /**
     * Discard pending attachments on the field.
     *
     * @return void
     */
    public function __invoke(Request $request)
    {
        PendingAttachment::where('draft_id', $request->draftId)
            ->get()
            ->each
            ->purge();
    }
}
