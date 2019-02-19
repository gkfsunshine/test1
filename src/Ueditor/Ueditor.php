<?php
namespace JiaLeo\Laravel\Ueditor;

class Ueditor
{
    public function __construct($config_name)
    {
        $this->configName = $config_name;
    }

    /**
     * 运行
     * @param $action
     */
    public function run()
    {
        $action = request()->query->get('action');
        switch ($action) {
            //获取配置信息
            case 'config':
                $config = config('ueditor');
                $this->returnResult($config[$this->configName]);
                return;
            /* 上传图片 */
            case 'uploadimage':
                /* 上传涂鸦 */
            case 'uploadscrawl':
                /* 上传视频 */
            case 'uploadvideo':
                /* 上传文件 */
            case 'uploadfile':
                //$result = include("action_upload.php");
                return $this->uploadFile();
            /* 列出图片 */
            //case 'listimage':
            //    break;
            /* 列出文件 */
            //case 'listfile':
            //    break;

            /* 抓取远程文件 */
            //case 'catchimage':
            //$result = include("action_crawler.php");
            //    break;

            default:
                $this->returnResult(array(
                    'state' => '请求地址出错'
                ));
        }
    }

    /**
     * 输出结果
     * @param $result
     * @return \Illuminate\Http\JsonResponse
     */
    private function returnResult($result)
    {

        $callback = Request()->query->get('callback');

        header("Content-Type: application/json; charset=utf-8");

        /* 输出结果 */
        if (!empty($callback)) {
            if (preg_match("/^[\w_]+$/", $callback)) {

                echo htmlspecialchars($callback) . '(' . json_encode($result) . ')';
                exit;
            } else {
                echo json_encode(array(
                    'state' => 'callback参数不合法'
                ));
                exit;
            }
        } else {
            echo json_encode($result);
            exit;
        }
    }

    public function uploadFile()
    {
        //获取配置信息
        $get_config = config('ueditor');
        $CONFIG = $get_config[$this->configName];

        /* 上传配置 */
        $base64 = "upload";
        switch (htmlspecialchars($_GET['action'])) {
            case 'uploadimage':
                $config = array(
                    "pathFormat" => $CONFIG['imagePathFormat'],
                    "maxSize" => $CONFIG['imageMaxSize'],
                    "allowFiles" => $CONFIG['imageAllowFiles']
                );
                $fieldName = $CONFIG['imageFieldName'];
                break;
            case 'uploadscrawl':
                $config = array(
                    "pathFormat" => $CONFIG['scrawlPathFormat'],
                    "maxSize" => $CONFIG['scrawlMaxSize'],
                    "allowFiles" => $CONFIG['scrawlAllowFiles'],
                    "oriName" => "scrawl.png"
                );
                $fieldName = $CONFIG['scrawlFieldName'];
                $base64 = "base64";
                break;
            case 'uploadvideo':
                $config = array(
                    "pathFormat" => $CONFIG['videoPathFormat'],
                    "maxSize" => $CONFIG['videoMaxSize'],
                    "allowFiles" => $CONFIG['videoAllowFiles']
                );
                $fieldName = $CONFIG['videoFieldName'];
                break;
            case 'uploadfile':
            default:
                $config = array(
                    "pathFormat" => $CONFIG['filePathFormat'],
                    "maxSize" => $CONFIG['fileMaxSize'],
                    "allowFiles" => $CONFIG['fileAllowFiles']
                );
                $fieldName = $CONFIG['fileFieldName'];
                break;
        }

        /* 生成上传实例对象并完成上传 */
        $up = new Uploader($fieldName, $config, $base64);

        /**
         * 得到上传文件所对应的各个参数,数组结构
         * array(
         *     "state" => "",          //上传状态，上传成功时必须返回"SUCCESS"
         *     "url" => "",            //返回的地址
         *     "title" => "",          //新文件名
         *     "original" => "",       //原始文件名
         *     "type" => ""            //文件类型
         *     "size" => "",           //文件大小
         * )
         */

        /* 返回数据 */
        $r = $up->getFileInfo();
        load_helper('File');

        //判断是否上传到云
        $is_cloud=config('ueditor')[$this->configName]['upload_to_cloud'];
        if($is_cloud){
            $r['url'] = upload_to_cloud(public_path($r['url']),$r['url']);
        }

        $r['url'] = file_url($r['url'],$is_cloud);

        return response()->json($r);
    }


}