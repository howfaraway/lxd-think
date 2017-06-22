<?php
/**
 * Excel操作类
 * @author ChenGuangdong
 *
 */
namespace app\common\library;

class ExcelHandle
{
    public static function initImport($file)
    {
        if (!file_exists($file)) {
            return false;
        }
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        //导入PHPExcel类库，因为PHPExcel没有用命名空间，只能import导入
        import("org.util.PHPExcel");
        import("org.util.PHPExcel.IOFactory");
        if ($extension == 'xls' || $extension == 'xlsx') {
            $objPHPExcel = \PHPExcel_IOFactory::load($file);
        } elseif ($extension == 'csv') {
            setlocale(LC_ALL, 'zh_CN');
            $objRead = \PHPExcel_IOFactory::createReader('CSV')
                        ->setDelimiter(',')
                        ->setInputEncoding('GBK')
                        ->setEnclosure('"')
                        ->setLineEnding('\r\n')
                        ->setSheetIndex(0);
            $objPHPExcel = $objRead->load($file);
        }
        return $objPHPExcel;
    }
    
    /**
     * 初始化Excel导出对象
     * @return \PHPExcel
     */
    public static function initExport()
    {
        //导入PHPExcel类库，因为PHPExcel没有用命名空间，只能import导入
        import("org.util.PHPExcel");
        //import("org.util.PHPExcel.Writer.Excel2007");
        import("org.util.PHPExcel.Writer.Excel5");
        import("org.util.PHPExcel.IOFactory");
        
        return new \PHPExcel();
    }
    
    /**
     * 下载Excel
     * @param string $filename
     * @param array $head_array
     * @param array $data
     * @param string $output  直接输出浏览器
     * @return boolean
     */
    public static function getExcel($filename, $head_array, $data, $type = 'xls', $output = true, $ext = true)
    {
        if (empty($filename) || empty($data) || !is_array($data)) {
            return false;
        }
        if (!in_array($type, ['xls', 'xlsx'])) {
            return false;
        }
        if ($ext === true) {
            $ext = date('YmdHis', time());
            $filename .= '_' . $ext;
        } elseif ($ext != '') {
            $filename .= '_' . $ext;
        }
        $filename .= '.' . $type;
        $dir = pathinfo($filename, PATHINFO_DIRNAME);
        if (!$output && !exist_dir($dir, true)) {
            return false;
        }
        
        // 创建新的PHPExcel对象
        $objPHPExcel = self::initExport();
        $objProps = $objPHPExcel->getProperties();
        
        // 设置表头
        $colum_num = 1;
        if (!empty($head_array)) {
            foreach($head_array as $v) {
                $pCoordinate = self::getColumnChar($colum_num) . 1;
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue($pCoordinate, $v);
                $colum_num ++;
            }
        }
        $row_num = empty($head_array) ? 1 : 2;
        $objActSheet = $objPHPExcel->getActiveSheet();
        foreach($data as $key => $rows) { //行写入
            if (!is_array($rows)) {
                continue;
            }
            $colum_num = 1;
            foreach($rows as $row) {// 列写入
                $pCoordinate = self::getColumnChar($colum_num) . $row_num;
                if (is_array($row)) {
                    $value = $row['value'];
                } else {
                    $value = $row;
                }
                $merge = isset($row['merge']) ? $row['merge'] : 1;
                if (isset($merge) && $merge > 1) {
                    $colum_num += ($merge - 1);
                    $pCoordinate2 = self::getColumnChar($colum_num) . $row_num;
                    $objActSheet->mergeCells($pCoordinate . ':' . $pCoordinate2);
                }
                //$objActSheet->setCellValue($j . $column, $value);
                $objActSheet->setCellValueExplicit($pCoordinate, $value, 's');
                $colum_num ++;
            }
            $row_num ++;
        }
        $filename = iconv("utf-8", "gb2312", $filename);
        //重命名表
        $objPHPExcel->getActiveSheet()->setTitle('WorKSheet');
        //设置活动单指数到第一个表,所以Excel打开这是第一个表
        $objPHPExcel->setActiveSheetIndex(0);
        if ($type == 'xls') {
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        } else {
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        }
        if ($output) {
            if ($type == 'xls') {
                //将输出重定向到一个客户端web浏览器(Excel5)
                header('Content-Type: application/vnd.ms-excel');
            } else {
                //将输出重定向到一个客户端web浏览器(Excel2007)
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            }
            //header('Content-Type: application/vnd.ms-excel');
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header('Cache-Control: max-age=0');
            $objWriter->save('php://output'); //文件通过浏览器下载
            exit;
        } else {
            $objWriter->save($filename); //脚本方式运行，保存在当前目录
            return $filename;
        }
    }
    
    /**
     * 检查是否为空行
     * @param \PHPExcel_Worksheet $sheet
     * @param int $row
     * @param int $columns
     * @return boolean
     */
    public static function checkNullRow($sheet, $row, $columns)
    {
        for ($i = 1; $i <= $columns; $i ++) {
            $column = self::getColumnChar($i);
            $value = trim($sheet->getCell($column . $row)->getValue());
            if ($value !== '') {
                return true;
            }
        }
        return false;
    }
    
    /**
     * 根据EXCEL列数字编码获取列字母符号
     * @param int $column
     * @return boolean|string
     */
    private static function getColumnChar($column)
    {
        if (!is_int($column) || $column <= 0) {
            return false;
        }
        $chars = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
        $char_num = ceil($column / 26);
        $char = '';
        if ($char_num > 1) {
            $char .= $chars[$char_num - 2];
        }

        $last_index = (($column - 1) % 26);
        $char .= $chars[$last_index];
        
        return $char;
    }
}