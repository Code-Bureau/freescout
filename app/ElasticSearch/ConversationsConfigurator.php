<?php

namespace App\ElasticSearch;

use ScoutElastic\IndexConfigurator;
use ScoutElastic\Migratable;

class ConversationsConfigurator extends IndexConfigurator
{
    use Migratable;

    protected $name = 'conversations';

    /**
     * @var array
     */
    protected $settings = [
        //
    ];
}
