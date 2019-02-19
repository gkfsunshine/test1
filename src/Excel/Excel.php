<?php
namespace JiaLeo\Laravel\Excel;

use App\Exceptions\ApiException;
use OSS\OssClient;
use OSS\Core\OssException;

class Excel
{
    public $creator = '';                       //设置excel创始人
    public $lastModifiedBy = '';                //设置excel最后修改人
    public $title = '';                         //设置excel标题
    public $subject = '';                       //设置excel题目
    public $description = '';                   //设置excel描述
    public $keywords = '';                      //设置excel关键字
    public $category = '';                      //设置excel种类
    public $activeSheetIndex = 0;               //设置当前的sheet
    public $exportExcelVerison = '2007';        //导出excel版本
    public $imgVal = [];     //图片地址栏

    public $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');

    public $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');

    public $savePath;      //本地保存路径
    public $fileName;      //保存的文件名

    public function __construct()
    {
        //默认保存位置
        $this->savePath = base_path() . '/storage/excel/';
    }

    /**
     * 导出excel
     * @param string $file_name 文件名
     * @param array $table_data 数据
     * @param bool $is_out_put 是否文件输出
     * @param bool $is_upload 是否上传到远端(本地文件会被删除)
     * @eg:传入的数据格式
     * $export_data=array(
     *
     *       array(
     *          '订单号','支付时间','商品id','商品名称'
     *       ),
     *
     *       array(
     *          '1',
     *           2,
     *          array(
     *              '10','11'
     *          ),
     *          array(
     *             '商品1','商品2'
     *          )
     *       ),
     *       array(
     *           '12222',
     *           211111111,
     *           12,
     *           '商品3'
     *       )
     * );
     *
     *
     * @author: 亮 <chenjialiang@han-zi.cn>
     */
    public function export($file_name, $table_data = array(), $is_out_put = false, $is_upload = false)
    {
        require_once __DIR__ . '/Phpexcel/PHPExcel.php';

        $objPHPExcel = new \PHPExcel();

        if ($this->exportExcelVerison == '2005') {
            $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
            $ext = 'xls';
        } else {
            $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
            $ext = 'xlsx';
        }

        $this->setTitle = $file_name;
        //创建人
        $objPHPExcel->getProperties()->setCreator($this->creator);
        //最后修改人
        $objPHPExcel->getProperties()->setLastModifiedBy($this->lastModifiedBy);
        //标题
        $objPHPExcel->getProperties()->setTitle($this->title);
        //题目
        $objPHPExcel->getProperties()->setSubject($this->subject);
        //描述
        $this->description .= 'Export time:' . date('Y-m-d H:i:s');
        $objPHPExcel->getProperties()->setDescription($this->description);
        //关键字
        $objPHPExcel->getProperties()->setKeywords($this->keywords);
        //种类
        $objPHPExcel->getProperties()->setCategory();

        //设置当前的sheet
        $objPHPExcel->setActiveSheetIndex($this->activeSheetIndex);

        //设置文字对齐
        $objPHPExcel->setActiveSheetIndex()->getDefaultStyle()
            ->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->setActiveSheetIndex()->getDefaultStyle()
            ->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

        //$cellNum = count($cell_name);
        $dataNum = count($table_data);
        $cellNum = count($table_data[0]);

        //设置列
        $this->setCellName($cellNum);

        $version = config('app.excel_version', 'v1');


        if ($version == 'v1') {
            //表头模板输出
            $objPHPExcel->getActiveSheet()->mergeCells('A1:' . $this->cellName[$cellNum - 1] . '1');//合并单元格
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'Export time:' . date('Y-m-d H:i:s'));

            $title_col_index = 2;
            $col_index = 3;
        } else {
            //v2版开始,不带Export-time列
            $title_col_index = 1;
            $col_index = 2;
        }

        //数据输出
        foreach ($table_data as $key => $v) {

            if ($key == 0) {  //表头
                foreach ($v as $key2 => $vv) {
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue($this->cellName[$key2] . $title_col_index, $vv);
                    $objPHPExcel->getActiveSheet()->getStyle($this->cellName[$key2] . $title_col_index)->getFont()->setBold(true);//设置加粗
                    $objPHPExcel->getActiveSheet()->getColumnDimension($this->cellName[$key2])->setWidth(20);
                    //$objPHPExcel->getActiveSheet()->getStyle($this->cellName[$key2] . $title_col_index)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
                    //$objPHPExcel->getActiveSheet()->getStyle($this->cellName[$key2] . $title_col_index)->getFill()->getStartColor()->setARGB('FF1AE694');
                }
            } else {       //数据
                //循环一次,是否有数组
                $is_array = false;
                $col_count = 0;
                foreach ($v as $key2 => $vv) {
                    if (is_array($vv)) {
                        $is_array = true;
                        if (count($vv) > $col_count) {
                            $col_count = count($vv);
                        }
                    }
                }

                if (!$is_array) {    //没数组
                    foreach ($v as $key2 => $vv) {
                        if (array_keys($this->imgVal, $key2)) {
                            $img_height = $this->setDrawing($objPHPExcel, $vv, $this->cellName[$key2] . $col_index);
                            $objPHPExcel->setActiveSheetIndex(0)->getRowDimension($col_index)->setRowHeight($img_height);
                        } else {

                            $format = is_numeric($vv) ? \PHPExcel_Cell_DataType::TYPE_NUMERIC : \PHPExcel_Cell_DataType::TYPE_STRING;
                            $objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicit($this->cellName[$key2] . $col_index, $vv, $format);
                            //$objPHPExcel->setActiveSheetIndex(0)->setCellValue($this->cellName[$key2].$col_index, $vv);
                        }
                    }

                    $col_index += 1;
                } else {    //有数组
                    foreach ($v as $key2 => $vv) {
                        if (!is_array($vv)) {
                            $format = is_numeric($vv) ? \PHPExcel_Cell_DataType::TYPE_NUMERIC : \PHPExcel_Cell_DataType::TYPE_STRING;
                            $objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicit($this->cellName[$key2] . $col_index, $vv, $format);
                            //合拼单元格
                            $objPHPExcel->getActiveSheet()->mergeCells($this->cellName[$key2] . $col_index . ':' . $this->cellName[$key2] . ($col_index + $col_count - 1));

                        } else {

                            //如果小于总数据,则说明有合并单元格
                            if (count($vv) < $col_count) {
                                foreach ($vv as $key3 => $vvv) {
                                    $format = is_numeric($vv) ? \PHPExcel_Cell_DataType::TYPE_NUMERIC : \PHPExcel_Cell_DataType::TYPE_STRING;
                                    $objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicit($this->cellName[$key2] . ($col_index + ($col_count / count($vv)) * $key3), $vvv, $format);
                                    $objPHPExcel->getActiveSheet()->mergeCells($this->cellName[$key2] . ($col_index + ($col_count / count($vv)) * $key3) . ':' . $this->cellName[$key2] . ($col_index + ($col_count / count($vv)) * ($key3 + 1) - 1));
                                }

                            } else {
                                foreach ($vv as $key3 => $vvv) {
                                    $format = is_numeric($vv) ? \PHPExcel_Cell_DataType::TYPE_NUMERIC : \PHPExcel_Cell_DataType::TYPE_STRING;
                                    $objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicit($this->cellName[$key2] . ($col_index + $key3), $vvv, $format);
                                }
                            }
                        }
                    }
                    $col_index += $col_count;
                }
            }
        }

        //直接输出下载
        if ($is_out_put) {
            //为文件名加上日期
            $file_name .= '-' . date('YmdHis');

            //防止ie输出中文名乱码
            $userBrowser = $_SERVER['HTTP_USER_AGENT'];
            if (preg_match('/MSIE/i', $userBrowser)) {
                $file_name = urlencode($file_name);
            }
            $file_name = iconv('UTF-8', 'GBK//IGNORE', $file_name);

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl;charset=UTF-8");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");;
            header('Content-Disposition:attachment;filename="' . $file_name . '.' . $ext . '"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');
            exit;
        }

        load_helper('File');

        //保存到本地
        $this->fileName = $file_name . '-' . date('YmdHis') . rand(10000, 99999) . '.' . $ext;
        $this->savePath = $this->savePath . ltrim($this->fileName, '/');

        //判断文件夹
        dir_exists(dirname($this->savePath));

        $objWriter->save($this->savePath);

        //上传到阿里云
        if ($is_upload) {
            $return_url = upload_to_cloud($this->savePath, $this->fileName);
            return $return_url;
        }

        return true;
    }

    /**
     * 获取导入文件,返回对象
     * @author: 亮 <chenjialiang@han-zi.cn>
     */
    public function import($file)
    {
        if (!file_exists($file)) {
            throw new ApiException('文件不存在', 'FILE_ERROR');
        }

        require_once dirname(__FILE__) . '/Phpexcel/PHPExcel.php';
        $fileType = \PHPExcel_IOFactory::identify($file);//自动获取文件的类型提供给phpexcel用
        $objReader = \PHPExcel_IOFactory::createReader($fileType);//获取文件读取操作对象
        @$objPHPExcel = $objReader->load($file);//加载文件

        $sheetName = $objPHPExcel->getSheet(0)->toArray(); //默认拿第一个sheet

        return $sheetName;  //返回对象
    }

    /**
     * 导出csv
     * @param $file_name
     * @param array $table_data
     * @param bool $is_out_put
     * @param bool $is_upload
     * @return bool
     */
    public function exportCsv($file_name, $table_data = array(), $is_out_put = false, $is_upload = false)
    {

        //直接输出下载
        if ($is_out_put) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $file_name . '.csv"');
            header('Cache-Control: max-age=0');

            //打开PHP文件句柄,php://output 表示直接输出到浏览器
            $fp = fopen('php://output', 'a');
        } else {
            load_helper('File');

            //保存到本地
            $this->fileName = $file_name . '-' . date('YmdHis') . rand(10000, 99999) . '.csv';
            $this->savePath = $this->savePath . ltrim($this->fileName, '/');

            //判断文件夹
            dir_exists(dirname($this->savePath));
            $fp = fopen($this->savePath, 'a');
        }

        //计数器
        $num = 0;

        //每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
        $limit = 100000;

        //逐行取出数据，不浪费内存
        $count = count($table_data);
        for ($i = 0; $i < $count; $i++) {

            $num++;

            //刷新一下输出buffer，防止由于数据过多造成问题
            if ($limit == $num) {
                ob_flush();
                flush();
                $num = 0;
            }

            $row = $table_data[$i];
            foreach ($row as $key => $value) {
                $row[$key] = iconv('utf-8', 'gbk', $value);
            }
            fputcsv($fp, $row);
        }

        $this->setTitle = $file_name;

        //直接输出下载
        if ($is_out_put) {
            exit;
        }

        //上传到阿里云
        if ($is_upload) {
            $return_url = upload_to_cloud($this->savePath, $this->fileName);
            return $return_url;
        }

        return true;
    }

    /**
     * 导入csv文件
     * @param $file
     * @return array
     * @throws ApiException
     */
    public function importCsv($file)
    {
        if (!file_exists($file)) {
            throw new ApiException('文件不存在', 'FILE_ERROR');
        }

        $fh = fopen($file, "r");
        $list = [];
        while ($data = fgetcsv($fh)) { //每次读取CSV里面的一行内容
            if (!empty($data)) {
                foreach ($data as $key => $value) {
                    $data[$key] = iconv('gb2312', 'utf-8', $value);
                }
            }
            $list[] = $data;
        }
        fclose($fh);
        return $list;
    }

    /**
     * 从远程服务器下载excel
     * @param string $url 远程地址
     * @param string $save_path 保存路径
     * @return bool
     * @throws ApiException
     */
    public function downloadExcel($url, $save_path)
    {
        $content = $this->httpGet($url);
        if (!$content) {
            throw new ApiException('excel下载失败', 'DOWN_ERROR');
        }
        $result = file_put_contents($save_path, $content);
        if (!$result) {
            throw new ApiException('excel保存失败', 'SAVE_ERROR');
        }
        return true;
    }

    /**
     * GET 请求
     * @param string $url
     */
    private function httpGet($url)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }

    public function __call($name, $arguments)
    {
        if (substr($name, 0, 3) === 'set') {
            $setter = lcfirst(ltrim($name, 'set'));
            if (isset($this->$setter)) {
                $this->$setter = $arguments[0];
                return true;
            } else {
                throw new ApiException('属性 ' . $setter . ' 不存在!');
            }
        } elseif (substr($name, 0, 3) === 'get') {
            $getter = lcfirst(ltrim($name, 'get'));
            if (isset($this->$getter)) {
                return $this->$getter;
            } else {
                throw new ApiException('属性 ' . $getter . ' 不存在!');
            }
        }

        throw new ApiException('方法 ' . $name . ' 不存在!');
    }


    /**
     * 设置列
     * @param $column
     */
    public function setCellName($column)
    {
        //小于26列
        if ($column < 26) {
            $this->cellName = [];
            for ($i = 0; $i < $column; $i++) {
                $this->cellName[] = $this->letter[$i];
            }
            return;
        }

        //大于26列
        $this->cellName = $this->letter;
        $column = $column - 26;
        $a = intval(ceil($column / 26));

        for ($j = 0; $j < $a; $j++) {
            for ($i = 0; $i < 26; $i++) {
                $this->cellName[] = $this->letter[$j] . $this->letter[$i];
                $column--;
                if ($column <= 0) {
                    break;
                }
            }
        }
    }

    /**
     * 设置图片到excel
     * @param $objPHPExcel
     * @param $img_val
     * @param $coordinates
     * @return int
     */
    public function setDrawing(&$objPHPExcel, $img_val, $coordinates)
    {
        /*实例化插入图片类*/
        $objDrawing = new \PHPExcel_Worksheet_Drawing();

        /*设置图片路径 切记：只能是本地图片*/
        try {
            $objDrawing->setPath($img_val);
        } catch (\Exception $e) {
            return 100;
        }

        /*设置图片高度*/
        $objDrawing->setWidth(100);
        $img_height = $objDrawing->getHeight();

        //dd($img_height);
        /*设置图片要插入的单元格*/
        $objDrawing->setCoordinates($coordinates);
        /*设置图片所在单元格的格式*/
        //$objDrawing->setOffsetX(10);
        //$objDrawing->setOffsetY(10);
        $objDrawing->setRotation(0);
        $objDrawing->getShadow()->setVisible(true);
        $objDrawing->getShadow()->setDirection(50);
        $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
        unset($objDrawing);
        return $img_height;
    }

}