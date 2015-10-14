<?php namespace Nextus\Acl\Traits;

use Illuminate\Support\Collection;

trait HasUserPermission
{

    /*
    |----------------------------------------------------------------------
    | User Permission Trait Methods
    |----------------------------------------------------------------------
    |
    */

    public function addPermission($name, $permission = true, $resource_id = 0)
    {
        // filter by resource id
        foreach($this->permissions as $k=>$row)
        {
            if($row->resource_id != $resource_id)
                $this->permissions->forget($k);
        }

        $slugs = $this->permissions->keyBy('name');

        list($slug, $name) = $this->extractAlias($name);

        if ( $slugs->has($name) && is_null($slug) && ! is_array($permission) ) {
            return true;
        }

        /*
        if ( ! $slugs->has($name) && is_null($slug) ) {
            return $this->addPermissionCrud($name, $resource_id);
        }
        */

        $slug = is_array($permission)
            ? $permission : [$slug => (bool) $permission];

        if(is_null($resource_id))
            $resource_id = 0;

        // if alias doesn't exist, create permission
        if ( ! $slugs->has($name) ) {
            $new = $this->permissions()->create(compact('name', 'slug', 'resource_id'));
            $this->permissions->push($new);

            return $new;
        }

        // update slug
        return $slugs[$name]->update(compact('slug'));
    }

    public function removePermission($name)
    {
        $slugs = $this->permissions->keyBy('name');
        list($slug, $alias) = $this->extractAlias($name);

        // remove whole alias
        if ( $slugs->has($alias) && is_null($slug) ) {
            return $slugs[$alias]->delete();
        }

        // remove slug only.
        if ( $slugs->has($alias) && ! is_null($slug) ) {
            return $slugs[$alias]->update([
                'slug' => [$slug => null],
            ]);
        }

        return true;
    }


    /*
    |----------------------------------------------------------------------
    | Slug Permission Related Protected Methods
    |----------------------------------------------------------------------
    |
    */

    protected function addPermissionCrud($name, $resource_id = 0)
    {
        $slugs = $this->permissions->keyBy('name');
        list(, $name) = $this->extractAlias($name);

        $hasCrud = isset($slugs->get($name)->slug['create']);

        if ( $slugs->has($name) && $hasCrud ) {
            return true;
        }

        // crud slug
        $slug = [
            'create' => true, 'read' => true,
            'view'   => true, 'update' => true,
            'delete' => true
        ];

        // if alias doesn't exist, create crud permissions
        if ( ! $slugs->has($name) ) {
            $new = $this->permissions()->create(compact('name', 'slug', 'resource_id'));
            $this->permissions->push($new);

            return $new;
        }

        // update crud slug
        return $slugs[$name]->update(compact('slug'));
    }

    protected function extractAlias($str)
    {
        preg_match('/([^.].*)[\._]([^\s].*?)$/i', $str, $m);

        return [
            isset($m[1]) ? $m[1] : null, //slug
            isset($m[2]) ? $m[2] : $str, //alias
        ];
    }

    /**
     * Deprecated
     *
     * @param       $alias
     * @param array $permissions
     * @return mixed
     */
    protected function addSlug($alias, array $permissions)
    {
        $slugs = $this->permissions->lists('slug', 'name');
        $collection = new Collection($slugs);

        if ( $collection->has($alias) ) {
            $permissions = $permissions + $collection->get($alias);
        }

        $collection->put($alias, $permissions);

        return $collection->get($alias);
    }

    /**
     * Deprecated
     *
     * @param       $alias
     * @param array $permissions
     * @return mixed
     */
    protected function removeSlug($alias, array $permissions)
    {
        $slugs = $this->permissions->lists('slug', 'name');
        $collection = new Collection($slugs);

        if ( $collection->has($alias) ) {
            $new = array_diff_key($collection->get($alias), array_flip($permissions));
            $collection->put($alias, $new);
        }

        return $collection->get($alias);
    }

}
