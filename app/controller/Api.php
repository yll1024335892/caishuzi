<?php
declare (strict_types=1);

namespace app\controller;

use app\model\Openlog;
use think\facade\Cache;
use think\facade\Session;

class Api
{
    private function msg($data, $code = 200, $msg = 'ok')
    {
        $arr = array(
            'code' => $code,
            'message' => $msg,
            'data' => $data
        );
        return json_encode($arr);
    }

    public function lastData($lotcode)
    {
        headerAjax();
        $openlogModel = new Openlog();
        $res = $openlogModel->where(['lotcode' => $lotcode, "att" => '1'])->order("cycleid desc")->field("id,lotcode,cycleid,cycleid_time,b1,b2,b3,b4,b5,b6,b7,b8,b9,b10")->limit(0, 1)->select()->toArray();
        unset($openlogModel);
        $data = $this->msg($res);
        return $data;
    }

    public function allData($lotcode)
    {
        headerAjax();
        $openlogModel = new Openlog();
        $res = $openlogModel->where(['lotcode' => $lotcode, "att" => '1'])->order("cycleid desc")->field("id,lotcode,cycleid,cycleid_time,b1,b2,b3,b4,b5,b6,b7,b8,b9,b10")->limit(0, 280)->select()->toArray();
        unset($openlogModel);
        $data = $this->msg($res);
        return $data;
    }

    public function repair($lotcode)
    {
        headerAjax();
        if (empty(Cache::get('repair' . $lotcode))) {
            Cache::set('repair' . $lotcode, strtotime("+300 seconds"), 300);
            $periodObj = Period::getInstance();
            $periodObj->repair($lotcode);
            $data = $this->msg([], 200);
            return $data;
        }
        $data = $this->msg(date("Y-m-d H:i:s", (int)Cache::get('repair' . $lotcode)), 400);
        return $data;
    }

    public function forceThreeDay($lotcode)
    {
        headerAjax();
        $periodObj = Period::getInstance();
        $data = $this->msg($periodObj->forceThreeDay($lotcode), 200);
        return $data;
    }
}
