<?php

namespace Laravel\Nova\Fields;

trait Deletable
{
    /**
     * The callback used to delete the field.
     *
     * @var callable
     */
    public $deleteCallback;

    /**
     * Indicates if the underlying field is deletable.
     *
     * @var bool
     */
    public $deletable = true;

    /**
     * Indicates if the underlying field is prunable.
     *
     * @var bool
     */
    public $prunable = false;

    /**
     * Specify the callback that should be used to delete the field.
     *
     * @return $this
     */
    public function delete(callable $deleteCallback)
    {
        $this->deleteCallback = $deleteCallback;

        return $this;
    }

    /**
     * Specify if the underlying file is able to be deleted.
     *
     * @param  bool  $deletable
     * @return $this
     */
    public function deletable($deletable = true)
    {
        $this->deletable = $deletable;

        return $this;
    }

    /**
     * Determine if the underlying file should be pruned when the resource is deleted.
     *
     * @return bool
     */
    public function isPrunable()
    {
        return $this->prunable;
    }

    /**
     * Specify if the underlying file should be pruned when the resource is deleted.
     *
     * @param  bool  $prunable
     * @return $this
     */
    public function prunable($prunable = true)
    {
        $this->prunable = $prunable;

        return $this;
    }
}
