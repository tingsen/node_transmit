<?php
namespace Drupal\node_transmit;

use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;

class NTStatisticsHelper{

    public static function getSettings(){
        $result = Database::getConnection()->query('SELECT * FROM {node_transmit_ip}')->fetchAllAssoc('id');
        return $result;
    }

    public static function truncate(){
        db_query("TRUNCATE TABLE {node_transmit_ip}")->execute();
    }

    public static function insert($table_name,array $fields){
        try{
            Database::getConnection()->insert("$table_name")
                ->fields($fields)
                ->execute();
        }
        catch ( \Exception $e){
            drupal_set_message(t('db_insert failed. Message = %message.', [
                    '%message' => $e-> getMessage()
                ]
            ), 'error');
        }
    }

    public static function update($yid, $updated){
        try {
            $update = Database::getConnection()->update('node_transmit_ip');
            $update ->fields($updated);
            $update ->condition('id', $yid);
            $update ->execute();
        }
        catch (\Exception $e) {
            drupal_set_message(t('db_insert failed. Message = %message.', [
                    '%message' => $e-> getMessage()
                ]
            ), 'error');
        }
    }

    public static function status($yid, $updated){
        try {
            $update = Database::getConnection()->update('node_transmit_verification');
            $update ->fields($updated);
            $update ->condition('id', $yid);
            $update ->execute();
        }
        catch (\Exception $e) {
            drupal_set_message(t('db_insert failed. Message = %message.', [
                    '%message' => $e-> getMessage()
                ]
            ), 'error');
        }
    }

    public static function query($id) {
        $select = Database::getConnection() -> select('node_transmit_verification', 'ys');
        $select -> fields('ys');
        $select -> condition('id', $id);
        return $select-> execute()->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function virif($user_id,$node_id) {
        $select = Database::getConnection() -> select('node_transmit_verification', 'ys');
        $select -> fields('ys');
        $select -> condition('user_id', $user_id);
        $select -> condition('node_id', $node_id);
        return $select-> execute()->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function deleteData($id){
        return (bool) Database::getConnection()
            ->delete('node_transmit_verification')
            ->condition('node_id', $id)
            ->execute();
    }

    public static function downloadPDF($entity){
        $title = $entity->getTitle();
        // create new PDF document
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR); //设置创建者
        $pdf->SetTitle($title); //设置文件的title
        $pdf->SetSubject($title); //设置主题
        // set default header data
        $pdf->SetHeaderData('tcpdf_logo_huadong.jpg', PDF_HEADER_LOGO_WIDTH, '', '', array(0, 0, 0), array(152, 41, 73)); //设置头部,比如header_logo，header_title，header_string及其属性
        $pdf->setFooterData(array(0, 0, 0), array(152, 41, 73));

        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN)); //设置页头字体
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA)); //设置页尾字体
        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED); //设置默认等宽字体
        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT); //设置margins 参考css的margins
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER); //设置页头margins
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER); //设置页脚margins
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM); //设置自动分页
        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO); //设置调整图像自适应比例

        $pdf->setFontSubsetting(true); //设置默认字体子集模式
        $pdf->setCellPaddings(0,0,0,0);
        $pdf->setCellMargins(0,0,0,0);

        $pdf->AddPage(); //增加一个页面
        // set text shadow effect  设置文字阴影效果


        //$pdf->Write(0,  $title, "", 0, 'C', true, 0, false, false, 0);
        $pdf->writeHTML("<h2>".$title."</h2>", true, false, false, false, 'C');
        $pdf->Write(0,  '', "", 0, 'L', true, 0, false, false, 0);
        $pdf->Write(0,  '', "", 0, 'L', true, 0, false, false, 0);
        if($entity->getType() == 'article'){
            $pdf->Write(8, '作者：' . $entity->get('field_author')->value, "", 0, 'L', true, 0, false, false, 0);
        }
        if($entity->getType() == 'law'){
            $pdf->Write(8, '文号：' . $entity->get('field_document_number')->value, "", 0, 'L', true, 0, false, false, 0);
        }elseif($entity->getType() == 'case'){
            $pdf->Write(8, '案号：' . $entity->get('field_document_number')->value, "", 0, 'L', true, 0, false, false, 0);
        }
        $keyword = $entity->get('field_keyword')->value;
        if(!empty($keyword)){
            $pdf->Write(8, '关键字：' . $keyword, "", 0, 'L', true, 0, false, false, 0);
        }
        $pdf->Write(8, '发布时间：' . $entity->get('field_published')->value, "", 0, 'L', true, 0, false, false, 0);
        if($entity->getType() == 'law'){
            $pdf->Write(8, '实施日期：' . $entity->get('field_effective_date')->value, "", 0, 'L', true, 0, false, false, 0);
        }
        if($entity->getType() == 'case'){
            $pdf->Write(8, '审理法院：' . $entity->get('field_court')->value, "", 0, 'L', true, 0, false, false, 0);
        }
        if($entity->getType() == 'law'){
            $values = $entity->get('field_promulgating_agency')->getValue();
            if(!empty($values)){
                foreach ($values as $v){
                   $t = !empty($t) ? $t.', '.$v['value'] : $v['value'];
                }
                $pdf->Write(8, '发布部门：' .$t , "", 0, 'L', true, 0, false, false, 0);
            }
        }

        $pdf->Write(0,  '', "", 0, 'R', true, 0, false, false, 0);
        $pdf->Write(0,  '', "", 0, 'R', true, 0, false, false, 0);

        $content = $entity->get('body')->value;

        $content = preg_replace('/<a.+?>|<\/a>/', '', $content);
        $content = preg_replace('/\<p\>\<p/is', '<p', $content);
        $content ='<style>.promulgatetitle{text-align: center}p { text-indent: 0mm; margin-bottom: 1rem;}p.promulgatesignatory{text-align: right;margin-top: 3rem;}p.promulgatedate{text-align: right;margin-bottom: 3rem;}p.footerStyle{text-align: right} span{line-height: 2} p.caseDocPart2 > span{text-indent: 0mm;}</style>'.$content;
        $pdf->writeHTML($content, false, false, false , false);
        $pdf->Write(0,  '', "", 0, 'R', true, 0, false, false, 0);
        $pdf->Write(0,  '', "", 0, 'R', true, 0, false, false, 0);
//        $link = \Drupal::service('path.alias_manager')->getAliasByPath('/node/'.$entity->id());
        $host = \Drupal::request()->getSchemeAndHttpHost();
        $link = $entity->toUrl()->toString();
        $pdf->writeHTML('<a style="text-align: right;" href="'.$host.$link.'">原文链接</a>', true, false, false , false);
        // Close and output PDF document
        // This method has several options, check the source code documentation for more information.
        return $pdf->Output($title.'.pdf', 'D'); //I输出在浏览器上
    }

    public static function failData(array $fields){
        try{
            Database::getConnection()->insert("node_import_status")
                ->fields($fields)
                ->execute();
        }
        catch ( \Exception $e){
            drupal_set_message(t('db_insert failed. Message = %message.', [
                    '%message' => $e-> getMessage()
                ]
            ), 'error');
        }
    }


}