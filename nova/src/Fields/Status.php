<?php

namespace Laravel\Nova\Fields;

class Status extends Text
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'status-field';

    /**
     * Indicates if the element should be shown on the creation view.
     *
     * @var bool
     */
    public $showOnCreation = false;

    /**
     * Indicates if the element should be shown on the update view.
     *
     * @var bool
     */
    public $showOnUpdate = false;

    /**
     * Specify the values that should be considered "loading".
     *
     * @return $this
     */
    public function loadingWhen(array $loadingWords)
    {
        return $this->withMeta(['loadingWords' => $loadingWords]);
    }

    /**
     * Specify the values that should be considered "failed".
     *
     * @return $this
     */
    public function failedWhen(array $failedWords)
    {
        return $this->withMeta(['failedWords' => $failedWords]);
    }
}
