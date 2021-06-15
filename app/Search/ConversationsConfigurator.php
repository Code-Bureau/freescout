<?php

namespace App\Search;

use ScoutElastic\IndexConfigurator;
use ScoutElastic\Migratable;

/**
 * Class ConversationsConfigurator
 * @package App\Search
 */
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
