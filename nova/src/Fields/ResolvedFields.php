<?php

namespace Laravel\Nova\Fields;

use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;

class ResolvedFields extends Fluent
{
    /**
     * The post-storage callbacks for the fields.
     *
     * @var Collection
     */
    public $callbacks;

    /**
     * Create a new resolved fields instance.
     *
     * @return void
     */
    public function __construct(Collection $attributes, Collection $callbacks)
    {
        parent::__construct($attributes->all());

        $this->callbacks = $callbacks;
    }

    /**
     * Get the post-storage callbacks for the fields.
     *
     * @return Collection
     */
    public function callbacks()
    {
        return $this->callbacks;
    }
}
