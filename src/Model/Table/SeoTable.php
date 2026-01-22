<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Auth\DefaultPasswordHasher;

class SeoTable extends Table
{

    public $name = 'Seo';

    public function initialize(array $config)
    {
        $this->table('seo');
        $this->primaryKey('id');

      


    }
}
