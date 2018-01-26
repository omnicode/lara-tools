<?php

namespace LaraTools\Models\Traits;

trait FullNameTrait
{
    /**
     * full name of the staff member
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return $this->attributes['first_name'] . ' ' . $this->attributes['last_name'];
    }
}