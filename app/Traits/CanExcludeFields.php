<?php

namespace App\Traits;

use Illuminate\Support\Facades\Schema;

trait CanExcludeFields
{
    public function scopeExclude($query, array $fields)
    {
        $table = $query->getModel()->getTable();
        $columns = Schema::getColumnListing($table);

        $columns = array_diff($columns, $fields);

        return $query->select($columns);
    }
}
