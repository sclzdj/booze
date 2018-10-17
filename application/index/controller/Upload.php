<?php
namespace app\index\controller;
use app\admin\model\Attachment as AttachmentModel;
class Upload extends Home
{
    /**
     * 输出内容
     * @param array $data
     * @param int $errcode
     * @param string $msg
     */
    function response($data,$errcode,$msg)
    {
        $res = [
            'data'=>$data,
            'errcode'=>$errcode,
            'msg' =>$msg
        ];
        exit(json_encode($res));
    }
    /**
     * 上传附件
     * @param string $dir 保存的目录:images,files,videos,voices
     * @param string $from 来源，wangeditor：wangEditor编辑器, ueditor:ueditor编辑器, editormd:editormd编辑器等
     * @param string $module 来自哪个模块
     * @author 蔡伟明 <460932465@qq.com>
     * @return \think\response\Json|void
     */
    public function file($dir = 'images', $from = '', $module = '')
    {
        if ($dir == '')         return $this->response([],201,'没有指定上传目录');
        return $this->saveFile($dir, $from, $module);
    }
    
    /**
     * 保存附件
     * @param string $dir 附件存放的目录
     * @param string $from 来源
     * @param string $module 来自哪个模块
     * @author 蔡伟明 <460932465@qq.com>
     * @return string|\think\response\Json
     */
    private function saveFile($dir = '', $from = '', $module = '')
    {
        // 附件大小限制
        $size_limit = $dir == 'images' ? config('upload_image_size') : config('upload_file_size');
        $size_limit = $size_limit * 1024 * 1024;
        // 附件类型限制
        $ext_limit = $dir == 'images' ? config('upload_image_ext') : config('upload_file_ext');
        $ext_limit = $ext_limit != '' ? parse_attr($ext_limit) : '';
    
        // 获取附件数据
        switch ($from) {
            case 'editormd':
                $file_input_name = 'editormd-image-file';
                break;
            case 'ckeditor':
                $file_input_name = 'upload';
                $callback = $this->request->get('CKEditorFuncNum');
                break;
            default:
                $file_input_name = 'file';
        }
        $file = $this->request->file($file_input_name);
    
        // 判断附件是否已存在
        if ($file_exists = AttachmentModel::get(['md5' => $file->hash('md5')])) {
            $file_path = PUBLIC_PATH. $file_exists['path'];
                    $this->response([
                    'status' => 1,
                    'info'   => '上传成功',
                    'class'  => 'success',
                    'id'     => $file_exists['id'],
                    'path'   => ltrim($file_path,'/'),
                    'url'    => $file_exists['url']
                    ],200,'');
        }
    
        // 判断附件大小是否超过限制
        if ($size_limit > 0 && ($file->getInfo('size') > $size_limit)) {
                    $this->response([],202,'附件过大');
        }
    
        // 判断附件格式是否符合
        $file_name = $file->getInfo('name');
        $file_ext  = substr($file_name, strrpos($file_name, '.')+1);
        $file_ext = strtolower($file_ext);
        $error_msg = '';
        if ($ext_limit == '') {
            $error_msg = '获取文件信息失败！';
        }
        if ($file->getMime() == 'text/x-php' || $file->getMime() == 'text/html') {
            $error_msg = '禁止上传非法文件！';
        }
        if (!in_array($file_ext, $ext_limit)) {
            $error_msg = '附件类型不正确！';
        }
        if ($error_msg != '') {
                    return $this->response([],203,$error_msg);
        }
    
        // 移动到框架应用根目录/uploads/ 目录下
        $info = $file->move(config('upload_path') . DS . $dir);
    
        if($info){
            // 水印功能
            if ($dir == 'images' && config('upload_thumb_water') == 1 && config('upload_thumb_water_pic') > 0) {
                $this->create_water($info->getRealPath());
            }
    
            // 缩略图路径
            $thumb_path_name = '';
            // 生成缩略图
            if ($dir == 'images' && config('upload_image_thumb') != '') {
                $thumb_path_name = $this->create_thumb($info, $info->getPathInfo()->getfileName(), $info->getFilename());
            }
    
            // 获取附件信息
            $file_info = [
                'name'   => $file->getInfo('name'),
                'mime'   => $file->getInfo('type'),
                'path'   => 'uploads/' . $dir . '/' . str_replace('\\', '/', $info->getSaveName()),
                'ext'    => $info->getExtension(),
                'size'   => $info->getSize(),
                'md5'    => $info->hash('md5'),
                'sha1'   => $info->hash('sha1'),
                'thumb'  => $thumb_path_name,
                'module' => $module,
                
            ];
            $file_info['url'] = getRequestHost().$file_info['path'];
            // 写入数据库
            if ($file_add = AttachmentModel::create($file_info)) {
                $file_path = PUBLIC_PATH. $file_info['path'];
                        return $this->response([
                        'status' => 1,
                        'info'   => '上传成功',
                        'class'  => 'success',
                        'id'     => $file_add['id'],
                        'path'   => ltrim($file_path,'/'),
                        'url'    => $file_info['url']
                        ],200,'');
            } else {
                        $this->response([],204,'上传失败');
            }
        }else{
                $this->response([],204,$file->getError());
        }
    }
}