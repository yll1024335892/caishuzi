<?php
declare (strict_types=1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class Openlog extends Model
{
    protected $autoWriteTimestamp = true;
    protected $name = 'openlog';
    protected $pk = 'id';
}
