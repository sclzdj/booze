<?php
namespace app\admin\controller;

use app\common\controller\Common;
use app\common\builder\ZBuilder;

/**
 * 后台公共控制器
 * @package app\admin\controller
 */
class test extends Admin
{
    public function index()
    {
        return ZBuilder::make('form')
        ->addText('title', '标题')
        ->addTextarea('summary', '摘要')
        ->addUeditor('content', '内容')
        ->addImage('pic', '封面')
        ->addTags('tags', '标签')
        ->addFile('files', '附件')
        ->fetch();
    }
}