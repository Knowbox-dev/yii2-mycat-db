<?php

namespace Knowbox\Mycat;

/**
 * Created by PhpStorm.
 * User: heavi
 * Date: 2019/2/20
 * Time: 10:25 AM
 */
class Connection extends \yii\db\Connection
{
    public $commandClass = 'Knowbox\Mycat\Command';

}
