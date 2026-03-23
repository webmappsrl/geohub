<?php

namespace Laravel\Nova\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Nova;
use Laravel\Nova\Resource;

class PivotFieldDestroyRequest extends NovaRequest
{
    /**
     * Authorize that the user may attach resources of the given type.
     *
     * @return void
     */
    public function authorizeForAttachment()
    {
        if (! $this->newResourceWith($this->findModelOrFail())->authorizedToAttach(
            $this, $this->findRelatedModel()
        )) {
            abort(403);
        }
    }

    /**
     * Get the pivot model for the relationship.
     *
     * @return Model
     */
    public function findPivotModel()
    {
        return once(function () {
            $resource = $this->findResourceOrFail();

            abort_unless($resource->hasRelatableField($this, $this->viaRelationship), 404);

            return $this->findRelatedModel()->{
                $resource->model()->{$this->viaRelationship}()->getPivotAccessor()
            };
        });
    }

    /**
     * Find the related resource for the operation.
     *
     * @return \Laravel\Nova\Resource
     */
    public function findRelatedResource()
    {
        $related = $this->findRelatedModel();

        $resource = Nova::resourceForModel($related);

        return new $resource($related);
    }

    /**
     * Find the related model for the operation.
     *
     * @return Model
     */
    public function findRelatedModel()
    {
        return once(function () {
            $resource = $this->findResourceOrFail();

            abort_unless($resource->hasRelatableField($this, $this->viaRelationship), 404);

            return $resource->model()->{$this->viaRelationship}()
                ->withoutGlobalScopes()
                ->lockForUpdate()
                ->findOrFail($this->relatedResourceId);
        });
    }

    /**
     * Find the field being deleted or fail if it is not found.
     *
     * @return Field
     */
    public function findFieldOrFail()
    {
        return $this->findRelatedResource()->resolvePivotFields($this, $this->resource)
            ->whereInstanceOf(File::class)
            ->findFieldByAttribute($this->field, function () {
                abort(404);
            });
    }
}
