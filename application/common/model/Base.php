<?php
namespace app\common\model;

use think\Model;
/**
*
* 模型 基础类
* @date: 2017年6月22日 下午3:16:44
* @author: 6005001708
*
*/
class Base extends Model
{
    protected $autoWriteTimestamp = true;
    // 创建时间字段
    protected $createTime = 'create_time';
    // 更新时间字段
    protected $updateTime = 'modify_time';
    // 字段类型或者格式转换
    protected $type = [
        'create_time' => 'datetime',
        'modify_time' => 'datetime',
    ];
}