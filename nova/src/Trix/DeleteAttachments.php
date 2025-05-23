<?php

namespace Laravel\Nova\Trix;

use Illuminate\Http\Request;

class DeleteAttachments
{
    /**
     * The field instance.
     *
     * @var \Laravel\Nova\Fields\Trix
     */
    public $field;

    /**
     * Create a new class instance.
     *
     * @param  \Laravel\Nova\Fields\Trix  $field
     * @return void
     */
    public function __construct($field)
    {
        $this->field = $field;
    }

    /**
     * Delete the attachments associated with the field.
     *
     * @param  mixed  $model
     * @return array
     */
    public function __invoke(Request $request, $model)
    {
        Attachment::where('attachable_type', $model->getMorphClass())
            ->where('attachable_id', $model->getKey())
            ->get()
            ->each
            ->purge();

        return [$this->field->attribute => ''];
    }
}
