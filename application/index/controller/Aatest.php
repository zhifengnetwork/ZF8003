<?php


namespace app\index\controller;

use think\Db;

class Aatest{

    public function index(){

        echo 123;exit;

        ini_set('memory_limit', '2048M');
        set_time_limit(0);
        $file_name = ROOT_PATH.'a1.xlsx';
        // echo $file_name;exit;
        // dump( file_exists($file_name) );exit;
        vendor ('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
        $obj_PHPExcel = $objReader->load($file_name, $encode = 'utf-8');
        $excel_array = $obj_PHPExcel->getsheet(0)->toArray();

        foreach($excel_array[0] as $v){
            if($v){
                $keys[] = $v;
            }
        }


        foreach($excel_array as $k => $v){

            if($k > 0){
                for($i=0;$i<count($keys);$i++){
                    $vals[$k][$keys[$i]] = $v[$i] ? $v[$i] : '';
                }
            }
        }



        

        $gene = Standard_Gene();

        
        $g = ['ref', 'nation','region','variation','haplogroup','nomutation'];

        $re = [
            'dys389ab' => 'dys389i',
            'dys389cd' => 'dys389b',
            'gata_h4' => 'gata-h4',
        ];

        foreach($vals as $k=>$v){
            $completion = '';
            foreach($v as $k1 => $v1){
                $k1 = strtolower($k1);
                if(isset($re[$k1])){
                    $k1 = $re[$k1];
                }
                if(in_array($k1, $gene)){
                    if($k1 == 'gata-h4'){
                        $k1 = 'gata_h4';
                    }
                    $d[$k1] = $v1 ? intval((double)$v1*100) : 0;
                }else if(in_array($k1, $g)){
                    $d[$k1] = $v1 ? trim($v1) : '';
                }else{
                    $completion[$k1] = $v1 ? intval((double)$v1*100) : 0;
                }
            }
            if($completion){
                $d['completion'] = json_encode($completion);
            }else{
                $d['completion'] = '';
            }

            Db::name('gene')->insert($d);
            $d = '';
        }

        
    }








}