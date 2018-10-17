<?php
namespace app\common\exception;

use think\exception\Handle;
use think\exception\HttpException;

class myexception extends Handle
{

    public function render(\Exception $e)
    {
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
        }
        // TODO::开发者对异常的操作
        // 可以在此交由系统处理
        // 写错误
        $filePath = RUNTIME_PATH . DS . 'log' . DS . date('Ymd') . DS;
        if (is_dir($filePath)) {
            if (! is_writable($filePath)) {
                return false;
            }
        } else {
            @mkdir($filePath, 0700);
        }
        $filePath .= 'error.log';
        $content = date('Y-m-d H:i:s') . "\t" . $e->getTraceAsString() . "\r\n\r\n";
        @file_put_contents($filePath, $content, FILE_APPEND);
        // 做接口请求判断 输出不同内容
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
            exit(json_encode([
                'errcode' => 9999,
                'msg' => '服务器发生错误',
                'data' => []
            ]));
        } else {
            if ($e instanceof HttpException) {
                return $this->renderHttpException($e);
            } else {
                return $this->convertExceptionToResponse($e);
            }
        }
    }
}