<?php

namespace LaraTools\Models\Traits;

trait IsActiveTrait
{
    /**
     * @return string|\Symfony\Component\Translation\TranslatorInterface
     */
    public function getStatusNameAttribute()
    {
        return !empty($this->attributes[$this->getStatusColumn()]) ? 'Active' : 'InActive';
    }
}