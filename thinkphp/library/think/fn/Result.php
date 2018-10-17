<?php
namespace think\fn;

class Result
{

    private $errcode = 200;

    private $data = [];

    private $msg = '';

    /**
     * 判断函数返回是否成功
     * @return boolean
     */
    function isSuccess()
    {
        return 200 == $this->getErrcode() ? true : false;
    }
    
    /**
     * 初始化
     * @param int $errcode
     * @param array $data
     * @param string $msg
     */
    function __construct($errcode,$data,$msg){
        $this->setErrcode($errcode);
        $this->setData($data);
        $this->setMsg($msg);
    }
    
    /**
     *
     * @return the $errcode
     */
    public function getErrcode()
    {
        return $this->errcode;
    }

    /**
     *
     * @return the $data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     *
     * @return the $msg
     */
    public function getMsg()
    {
        return $this->msg;
    }

    /**
     *
     * @param number $errcode            
     */
    public function setErrcode($errcode)
    {
        $this->errcode = $errcode;
    }

    /**
     *
     * @param multitype: $data            
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     *
     * @param string $msg            
     */
    public function setMsg($msg)
    {
        $this->msg = $msg;
    }
}