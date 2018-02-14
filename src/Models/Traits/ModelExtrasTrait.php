<?php

namespace LaraTools\Models\Traits;

use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Illuminate\Support\Facades\DB;
use LaraTools\Utility\LaraUtil;

trait ModelExtrasTrait
{
    /**
     * @var string
     */
    protected $_indexableKey = 'list';

    /**
     * @var string
     */
    protected $_showableKey = 'list';

    /**
     * Save model with its associated data
     *
     * @param $data
     * @param array $options
     * @param null $model
     * @return mixed
     * @throws \Exception
     */
    public function saveAssociated($data, $options = [], $model = null)
    {
        $isUpdate = false;
        if ($model === null) {
            $model = new $this;
        } else {
            $isUpdate = true;
        }

        foreach ($data as $k => &$d) {
            if (is_string($d) && mb_strlen($d) == 0) {
                $d = null;
            }
        }

        DB::beginTransaction();

        if ($isUpdate) {
            $saved = $model->update($data);
        } else {
            $model = $model->create($data);
            // create always returns model object !!!
            $saved = true;
        }

        if (!$saved) {
            DB::rollback();
            return false;
        }
        if (empty($options['associated'])) {
            DB::commit();
            return $model->id;
        }
        $associated = [];
        foreach ($options['associated'] as $k => $v) {
            if (is_numeric($k)) {
                $associated[$v] = [];
            } else {
                $associated[$k] = $v;
            }
        }
        $status = true;
        $relations = $this->_getRelations();
        foreach ($relations as $relationName => $relation) {
            // relation exists
            if (isset($associated[$relationName])) {
                $relationType = LaraUtil::getRelationType($relation);
                // for BelongsToMany
                if ($relationType == 'BelongsToMany') {
                    $key = $relationName . '_ids';
                    if (!empty($data[$key])) {
                        if ($model->{$relationName}()->sync($data[$key])) {
                            $status = true;
                        } else {
                            $status = false;
                            break;
                        }
                    } else {
                        $model->{$relationName}()->detach();
                    }
                } elseif ($relationType == 'HasMany') {
                    // delete existing data
                    $model->{$relationName}()->forceDelete();
                    if (!empty($data[$relationName])) {
                        $saveMany = $data[$relationName];
                        array_walk($saveMany, function(&$val) use ($relation) {
                            $class = get_class($relation->getRelated());
                            $val = new $class(($val));
                        });
                        $model->{$relationName}()->saveMany($saveMany);
                    }
                } elseif ($relationType == 'BelongsTo') {
                    dbg('belongs to');
                    die;
                }
            }
        }
        if ($status) {
            DB::commit();
            return $model->id;
        }
        DB::rollback();
        return false;
    }

    /**
     * returns the relations of the model
     *
     * @return array
     */
    public function _getRelations()
    {
        $response = [];
        foreach ($this->_relations as $rel) {
            $response[$rel] = $this->{$rel}();
        }
        return $response;
    }

    /**
     *
     *  returns the data, that is used for 'id' => 'name' list for selectboxes
     *  uses the $this->listable var, which can be provided in any of this format
     * just string
     * 'name'
     * or as array
     * [
     *      'key' => 'id'
     *      'value' => 'full_name'
     *      'columns' => [
     *          'first_name',
     *          'last_name'
     *      ]
     * ]
     * columns - can be as string as well
     * value can be virtual field (attribute should be set in model)
     * *only value is required
     *
     * @return array|bool|mixed|string
     * @throws \Exception
     */
    public function getListable()
    {
        // if was already processed
        if (!empty($this->listable['_done'])) {
            return $this->listable;
        }
        $listable = $this->listable;
        // if empty - set as "name"
        if (empty($listable)) {
            $listable = 'name';
        }
        if (is_string($listable)) {
            $listable = [
                'key' => $this->getKeyName(),
                'value' => $listable,
                'columns' => [
                    $listable
                ]
            ];
        } else {
            if (empty($listable['value'])) {
                throw new \Exception('Value param for listable is required, model: ' . $this->getTable());
            }
            if (empty($listable['key'])) {
                $listable['key'] = $this->getKeyName();
            }
            if (empty($listable['columns'])) {
                $listable['columns'] = [
                    $listable['value']
                ];
            } elseif (is_string($listable['columns'])) {
                $listable['columns'] = [
                    $listable['columns']
                ];
            }
        }
        // add primry key for find list
        $listable['columns'][] = $listable['key'];
        // just in case filter duplicates a columns
        $listable['columns'] = array_unique($listable['columns']);
        $listable['_done'] = 1;
        $this->listable = $listable;

        return $this->listable;
    }

    /**
     * get list of columns that are allowed to be sorted
     * @param null $column - if provided checks whether provided column exists
     * @return array|bool
     */
    public function getSortable($column = null, $group = null)
    {
        if (is_null($group)) {
            $group = $this->_indexableKey;
        }

        $indexable = $this->getIndexable(true);
        $sortable = Hash::extract($indexable[$group], '{n}[sortable=1].name');

        if ($column) {
            return in_array($column, $sortable);
        }

        return $sortable;
    }

    /**
     * retuns the list of table colums for index page
     *
     * @param bool|false $hidden - if true hidden columns will be returned
     *      hidden column are not shown on the view
     * @param bool|false $full - whether to return list of columns or the full array
     * @param $group
     * @return array
     * @throws \Exception
     */
    public function getIndexable($full = false, $hidden = true, $group = null)
    {
        return $this->getAbleProperty('indexable' ,$full, $hidden, $group);
    }

    /**
     * @param bool $full
     * @param bool $hidden
     * @param null $group
     * @return array
     */
    public function getShowable($full = false, $hidden = true, $group = null)
    {
        return $this->getAbleProperty('showable' ,$full, $hidden, $group);
    }

    /**
     * @param $property
     * @param bool $full
     * @param bool $hidden
     * @param null $group
     * @return array
     * @throws \Exception
     */
    public function getAbleProperty($property, $full = false, $hidden = true, $group = null)
    {
        if (is_null($group)) {
            $group = $this->{'_' . $property . 'Key'};
        }

        $this->validatAbleProperty($property);

        $propertyVal = $this->{$property};
        if (!in_array($group, array_keys($propertyVal))) {
            throw new \Exception(sprintf('this "%s" group does not defined in %s columns', $group, $property));
        }

        if (!$hidden) {
            $propertyVal[$this->{'_' . $property . 'Key'}] = Hash::extract($this->{$property}[$group], '{n}[hidden=false]');
        }

        if ($full) {
            return $propertyVal;
        }

        return array_merge([$propertyVal['primary_key']], Hash::extract($propertyVal[$group], '{n}[virtual!=true].name'));
    }

    /**
     * @param $property
     * @return bool
     */
    protected function validatAbleProperty($property)
    {
        if (empty($this->{$property})) {
            foreach ($this->fillable as $filed) {
                $this->{$property}[] = ['name' => $filed];
            }
        }
        // if was already validated
        if (!empty($this->{$property}['primary_key'])) {
            return true;
        }

        $general = '_general';
        $groups[$general] = [];
        $allGroups = [$this->{'_' .$property .'Key'}];
        // set _indexAbleKey values
        array_walk($this->{$property}, function (&$val) use (&$groups, &$allGroups, $general, $property) {
            if (!array_key_exists('name', $val)) {
                throw new \Exception('For listable columns name parameter is required');
            }
            if (!array_key_exists('hidden', $val)) {
                $val['hidden'] = false;
            }
            if (!array_key_exists('label', $val)) {
                $val['label'] = Inflector::humanize($val['name']);
            }
            if ($property == 'indexable' && !array_key_exists('sortable', $val)) {
                $val['sortable'] = false;
            }
            // escape string on echo
            if (!array_key_exists('escape', $val)) {
                $val['escape'] = true;
            }
            if (!array_key_exists('virtual', $val)) {
                $val['virtual'] = false;
            }
            if (!empty($val['raw'])) {
                if (!stristr($val['name'], $this->getTable().'.')) {
                    throw new \Exception(sprintf('Invalid column: %s. Raw columns should contain table name', $val['name']));
                }
                $val['name'] = DB::raw($val['name']);
            }
            if (!array_key_exists('group', $val)) {
                $groups[$general][] = $val;
                $val['group'] = $allGroups;
            }
            $valGroup = array_pull($val, 'group');
            if (!is_array($valGroup)) {
                $valGroup = [$valGroup];
            }
            foreach ($valGroup as $group) {
                if (!in_array($group, $allGroups)) {
                    $allGroups[] = $group;
                }
            }
            foreach ($allGroups as $group) {
                if(in_array($group, $valGroup)) {
                    if (empty($groups[$group]) && $groups[$general] != [$val]) {
                        $groups[$group] = $groups[$general];
                    }
                    $groups[$group][] = $val;
                }
            }
        });

        unset($groups[$general]);

        if (empty($groups)) {
            $groups[$this->_indexableKey] = [];
        }

        // @TODO - table without PK ? list with another col ?
        $this->{$property} = [
            'primary_key' => $this->getKeyName(),
            'actions' => $this->getActions(),
            'table' => $this->getTable()
        ];

        $this->{$property} = array_merge($this->{$property}, $groups);
    }

    /**
     * returns the status colulmn - for using conditions with "Active"
     *
     * @return string
     */
    public function getStatusColumn()
    {
        return $this->statusColumn;
    }

    /**
     * @return array
     */
    public function getActions()
    {
        return $this->actions;
    }

}