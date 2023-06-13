<?php
namespace app\controller;

use app\BaseController;
use app\model\Openlog;

class Index extends BaseController
{
    public function index()
    {
        $openlogModel=new Openlog();
      //  $obj2=$openlogModel;
       // $openlogModel1=new Openlog();
       // $openlogModel2=new Openlog();
     // $d=  date("Y-m-d",time());
      var_dump(time());
      //  var_dump($openlogModel2);
      // $res= $openlogModel->select();


//                $dbo = FLEA::getDBO();
//        if ($this->is_bjl) {
//            $sql = 'select cycleid2 as cycleid,close_time,close_time2,open_time,lotcode,user_id ';
//        } else {
//            $sql = 'select * ';
//        }
//        $sql .= ' from ' . getSkTable('gamsys_openlog') . ' where lotcode="' . $lotcode . '" and user_id=' . $user_id;
//        if ($table_id > 0)
//            $sql .= ' and table_id=' . $table_id;
//        $sql .= ' order by close_time desc limit 0,1';
//        return $dbo->getRow($sql);

    // $res= $openlogModel->where(['lotcode'=>'pkta',"cycleid"=>'20210330002'])->find();


    ///  $res=  $openlogModel->where(['lotcode'=>'pkt'])->order("cycleid desc")->limit(0,1)->field("att,id,cycleid,lotcode,close_time,close_time2,open_time")->select()->toArray();
    //   echo  $openlogModel->getLastSql();

   // $id=  $res[0]['lotcode'];

   // var_dump($id);




       //var_dump($res);
     }

    public function hello($name = 'ThinkPHP6')
    {
        return 'hello,' . $name;
    }
}
