<?php
declare (strict_types=1);

namespace app\controller;

use app\model\Openlog;
use think\facade\Cache;

class Period
{
    //配置彩种信息
    //opensite：采集函数名（开奖）Loc（为自开），bnum：号码数（固定的），optime：流程时间（开盘时间~开奖时间），clstime：封盘时间~开奖时间的间隔（单位：分），oplst：到哪期开始加间隔时间做为下期的时间，numleg：期号规则，dmax ：一天开的最大期数，dstime：到指定期数后的间隔时间
    private $config = array(
        //澳洲幸运10(pka)
        'pka' => array('bnum' => 10, 'optime' => 5, 'clstime' => 0.5, 'clstime2' => 0.5, 'dstime' => 0, 'oplst' => '', 'dmax' => 288),
        //幸运飞艇(pkt)
        'pkt' => array('bnum' => 10, 'optime' => 5, 'clstime' => 1.17, 'clstime2' => 1.17, 'curl' => "", 'dstime' => '540', 'oplst' => 180, 'dmax' => 180, 'code2' => 'pk10'),
    );
    private static $instance;

    public function __construct()
    {
    }

    public static function getInstance()
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function __clone()
    {

    }

    /**
     * 获取最新一期数据
     */
    private function getLasOpenInfo($lotcode)
    {
        $openlogModel = new Openlog();
        $res = $openlogModel->where(['lotcode' => $lotcode])->order("cycleid desc")->limit(0, 1)->field("att,id,cycleid,cycleid_time,lotcode,close_time,close_time2,open_time")->select()->toArray();
        if (count($res) == 0) { //没有就采集最新的一期数据
            $caiji = Cai::getInstance();
            $caiArr = $caiji->caijiHistory($lotcode);
            $this->insertData($caiArr, $lotcode);
            $res = $openlogModel->where(['lotcode' => $lotcode])->order("cycleid desc")->limit(0, 1)->field("att,id,cycleid,cycleid_time,lotcode,close_time,close_time2,open_time")->select()->toArray();
            return !empty($res) ? $res[0] : [];
        }
        unset($openlogModel);
        return $res[0];
    }

    private function insertData($data, $lotcode)
    {
        $openlogModel = new Openlog();
        $insertData = [];
        foreach ($data as $key => $val) {
            if ($key != 'time') {
                //已经采集不处理,有期数没有数据就更新,没有期数就添加
                $isExit = $openlogModel->where(['lotcode' => $lotcode, "cycleid" => $key, 'att' => '1'])->find();
                if (!empty($isExit)) {
                    continue;
                }
                $tempArr = ['lotcode' => $lotcode, 'close_time' => '0', 'cycleid' => $key . '', 'cycleid_time' => $data['time'][$key] . '', 'att' => '1'];
                $bArr = ['b1' => isset($val["0"]) ? $val["0"] : "", 'b2' => isset($val["1"]) ? $val["1"] : "", 'b3' => isset($val["2"]) ? $val["2"] : "", 'b4' => isset($val["3"]) ? $val["3"] : '', 'b5' => isset($val["4"]) ? $val["4"] : '', 'b6' => isset($val["5"]) ? $val["5"] : '', 'b7' => isset($val["6"]) ? $val["6"] : '', 'b8' => isset($val["7"]) ? $val["7"] : '', 'b9' => isset($val["8"]) ? $val["8"] : '', 'b10' => isset($val["9"]) ? $val["9"] : ''];
                $isExit = $openlogModel->where(['lotcode' => $lotcode, "cycleid" => $key, 'att' => '0'])->find(); //有盘口就更新数据
                if (!empty($isExit)) {
                    $openlogModel->where(['lotcode' => $lotcode, "cycleid" => $key])->save(array_merge(['att' => '1', 'cycleid_time' => $data['time'][$key]], $bArr));
                    continue;
                }
                $insertData[] = array_merge($tempArr, $bArr);
            }
        }
        $openlogModel->insertAll($insertData);
        unset($openlogModel);
    }

    /**
     * 创建盘口
     * $lastopen 是最新一期的数据getLasOpenInfo方法获取
     */
    private function autocreateOpenByPks($nopen, $lastopen)
    {
        $lotcfn = $this->config[$nopen['lotcode']];
        if (empty($lastopen)) {
            //todo 添加失败的日志
            return -1;
        }
        //获得盘口其数
        $ncy2 = (int)$lastopen['cycleid'] + 1;
        if ($nopen['lotcode'] == 'pkt') {//隔天处理
            if ((int)substr($ncy2 . '', -3) > $lotcfn['dmax']) {
                $ncy2 = (int)date("Ymd", (int)$lastopen['cycleid_time']) . "001";
            }
        }
        $nopen['cycleid_time'] = $lastopen['cycleid_time'] + $lotcfn['optime'] * 60;
        $nopen['close_time'] = 0;
        $nopen['cycleid'] = $ncy2;
        $nopen['close_time2'] = 0;
        $nopen['open_time'] = 0;
        return $nopen;
    }

    /**
     * 将创建的盘口入库
     */
    private function autocreateOpenByIntoTable($lotcode)
    {
        $lastopen = $this->getLasOpenInfo($lotcode); //更新当前时间后再次获取最新一期数据
        $data = array('lotcode' => $lotcode, 'att' => 0);
        $openData = $this->autocreateOpenByPks($data, $lastopen);
        $this->saveOpen($openData);
    }

    private function saveOpen($data)
    {
        $openlogModel = new Openlog();
        if (isset($data['cycleid'])) {
            //看是否存在盘口数据,已经存在就不创建
            $cnt = $openlogModel->field("id")->where(array('cycleid' => $data['cycleid'], 'lotcode' => $data['lotcode']))->find();
            if (!empty($cnt)) {
                return false;
            }
        }
        //保存数据到数据库中 todo
        $openlogModel->save($data);
        unset($openlogModel);
    }

    public function repair($lotcode)
    {
        $caiji = Cai::getInstance();
        $caiArr = $caiji->caijiHistory($lotcode, time());
        if ($caiArr == -1) {
            return;
        }
        $this->insertData($caiArr, $lotcode);
    }

    public function clear()
    {
        $openlogModel = new Openlog();
        $openlogModel->where('cycleid_time', '<', strtotime("-3 day"))->delete();
        unset($openlogModel);
    }

    public function forceThreeDay($lotcode)
    {
        if (empty(Cache::get('code' . $lotcode))) {
            Cache::set('update' . $lotcode, strtotime("+2 hours") . '', 7200);
            Cache::set('code' . $lotcode, $lotcode, 7200);
            $caiCls = Cai::getInstance();
            $caiArr = $caiCls->caijiHistory($lotcode, strtotime("-1 day"));
            if ($caiArr != -1) {
                $this->insertData($caiArr, $lotcode);
            }
            $caiArr = $caiCls->caijiHistory($lotcode, strtotime("-2 day"));
            if ($caiArr != -1) {
                $this->insertData($caiArr, $lotcode);
            }
            $caiArr = $caiCls->caijiHistory($lotcode, strtotime("-3 day"));
            if ($caiArr != -1) {
                $this->insertData($caiArr, $lotcode);
            }
            return "ok";
        } else {
            return date("Y-m-d H:i:s", (int)Cache::get('update' . $lotcode));
        }
    }

    public function caiji($lotcode)
    {
        $lastopen = $this->getLasOpenInfo($lotcode);
        if (empty($lastopen)) {
            return '最新一期为空';
        }
        $caiCls = Cai::getInstance();
        $caiOnceData = $caiCls->caijiOnce($lotcode, intval($lastopen['cycleid']));
        if ($caiOnceData == -1 || $caiOnceData == -2 || $caiOnceData == -3) {
            return '采集最新一期数据为空或已经采集过';
        }
        if ($caiOnceData == -4) { //采集当天时间,然后创建盘口
            $caiArr = $caiCls->caijiHistory($lotcode, time()); //采集当前天时间
            if ($caiArr == -1) {
                return "没有采集到所有数据";
            }
            $this->insertData($caiArr, $lotcode); //将采集的数据存储
            $this->autocreateOpenByIntoTable($lotcode);
        } else {
            $this->insertData($caiOnceData, $lotcode);
            $this->autocreateOpenByIntoTable($lotcode);
        }
        return "更新数据并创建下一期盘口";
    }

    public function index($lotcode)
    {
        //  $data = array('lotcode' => $lotcode, 'att' => 0);
        //   $nopen = $this->autocreateOpenByPks($data);
        //  $this->saveOpen($nopen);
        //  $caiClass = new Cai();
        // $res = $caiClass->caijiOnce($lotcode, '20210330128');
        //   var_dump($res);
        //  $res=  $this->getLasOpenInfo($lotcode);


        //   $caiji = Cai::getInstance();
        // $caiArr = $caiji->caijiHistory($lotcode);

        //  $this->insertData($caiArr, $lotcode);
    }
}
