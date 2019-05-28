<?php


namespace app\index\controller;

use think\Db;

class Aatest{

    public function index(){


        ini_set('memory_limit', '2048M');
        set_time_limit(0);
        $file_name = ROOT_PATH.'xxl.xlsx';
        vendor ('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
        $obj_PHPExcel = $objReader->load($file_name, $encode = 'utf-8');
        $excel_array = $obj_PHPExcel->getsheet(0)->toArray();
        
        $n_arr = [
            1 => 'name',
            2 => 'nation',
            3 => 'on_addr',
            4 => 'addr_log',
            5 => 'mobile',
            6 => 'haplogroup',
            7 => 'variation',
            8 => 'nomutation',
            9 => 'desc',
        ];
        
        if($excel_array){
            foreach($excel_array as $k=>$v){
                if($k > 0 && $k != 10){
                    foreach($v as $key=>$val){
                        $kk = isset($n_arr[$k]) ? $n_arr[$k] : $v[0];
                        if($v[0]){
                            $d[$key][$kk] = $val ? $val : '';   
                        }
                    }
                }
            }
            array_shift($d);

            $res_success = 0;
            $res_error = 0;
            foreach($d as $k => $v){
                if($v['name']){
                    $inar['user_id'] = 0;
                    $inar['name'] = $inar['surname'] = $v['name'];
                    $inar['variation'] = $v['variation'];
                    $inar['nomutation'] = $v['nomutation'];
                    $inar['desc'] = '现居：'.$v['on_addr'].'，故居：'.$v['addr_log'].'，联系电话：'.$v['mobile'] .'，'. $v['desc'];
                    $inar['haplogroup'] = $v['haplogroup'];
                    $inar['nation'] = $v['nation'];
                    $inar['region'] = $v['on_addr'];
                    unset($v['name'],$v['variation'],$v['nomutation'],$v['on_addr'],$v['addr_log'],$v['mobile'],$v['desc'],$v['haplogroup'],$v['nation']);

                    $inar['addtime'] = $inar['utime'] = time();
                    foreach($v as $k1=>$v1){
                        if(Standard_Gene($k1)){
                            $k1 = str_replace('-','_',$k1);
                            $inar[strtolower($k1)] = $v1 ? (double)$v1 * 100 : 0;
                        }else{
                            $inar['completion'][strtolower($k1)] = $v1 ? (double)$v1 * 100 : 0;
                        }
                    }
                    $inar['completion'] = isset($inar['completion']) ? json_encode($inar['completion']) : '';
                    
                    $res = Db::name('gene')->insert($inar);
                    if($res){
                        $res_success += 1;
                    }else{
                        $res_error += l;
                    }
                    $inar = null;
                    unset($d[$k]);
                }
            }
        }
        dump([$res_success + $res_error, $res_success, $res_error]);exit;



















        
        echo 1234;exit;

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

        $dar = array();

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
            $d['user_id'] = 31;
            $d['name'] = $d['nation'];
            $d['desc'] = $d['region'];
            if($d['dys19']){
                $dd[] = $d;
                // Db::name('gene1')->insert($d);
            }
            // Db::name('gene1')->insert($d);
            // $dd[] = $d;
            
            $d = '';
            $completion = '';
        }
        // foreach($dd as $v){
        //     // Db::name('gene')->insert($v);
        // }

        // dump($dd);exit;
    }








}