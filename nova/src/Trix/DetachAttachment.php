<?php

namespace Laravel\Nova\Trix;

use Illuminate\Http\Request;

class DetachAttachment
{
    /**
     * Delete an attachment from the field.
     *
     * @return void
     */
    public function __invoke(Request $request)
    {
        Attachment::where('url', $request->attachmentUrl)
            ->get()
            ->each
            ->purge();
    }
}
