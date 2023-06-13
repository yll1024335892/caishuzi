<?php
declare (strict_types=1);

namespace app\controller;
/**
 * 数据的采集的逻辑 https://1680380.com/view/xingyft/pk10kai.html
 * //幸运飞艇(pkt),澳洲幸运10(pka)
 */
class Cai
{
    private $curlobj;
    private $api = "https://api.apiose122.com/"; //需要添加"/"
    private $k168param = array(
        'pkt' => array('hisaddr' => 'pks/getPksHistoryList.do', 'onceaddr' => 'pks/getPksDoubleCount.do', 'param' => 'lotCode=10057'),
        'pka' => array('hisaddr' => 'pks/getPksHistoryList.do', 'onceaddr' => 'pks/getPksDoubleCount.do', 'param' => 'lotCode=10012'),
    );
    private static $instance;

    private function __construct()
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

    private function getCurlObj()
    {
        if (!$this->curlobj) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.89 Safari/537.36");
            //设置curl
            //curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            $this->curlobj = $curl;
        }
        return $this->curlobj;
    }

    private function getHtmlContent($url, $uobj = null)
    {
        if (empty($uobj)) {
            $uobj = $this->getCurlObj();
        }
        curl_setopt($uobj, CURLOPT_URL, $url);
        return curl_exec($uobj);
    }

    public function caijiHistory($lotcode, $stime = '')
    {
        if (empty($stime)) {
            $stime = time();
        }
        $sdate = date('Y-m-d', $stime);
        // 幸运飞艇的时间为凌晨6点前算上一天的时间
        if ($lotcode == 'pkt') {
            if (date('H', $stime) < 6) {
                $sdate = date('Y-m-d', $stime - 24 * 60 * 60);
            } else if ($sdate == date('Y-m-d')) {
                $sdate = '';
            }
        } else {
            if ($sdate == date('Y-m-d')) {
                $sdate = '';
            }
        }
        $gurl = $this->api;
        $gurl .= $this->k168param[$lotcode]['hisaddr'] . '?' . $this->k168param[$lotcode]['param'];
        $gurl .= '&date=' . $sdate . '&_=' . rand();
        $html = $this->getHtmlContent($gurl);
        if (empty($html)) {
            return -1;
        }
        $arr = json_decode($html, true);
        if (empty($arr['result']['data'])) {
            return -1;
        }
        $barrs = array();
        $data = $arr['result']['data'];
        foreach ($data as $rs) {
            $cycid = $rs['preDrawIssue'];
            $balls = explode(',', $rs['preDrawCode']);
            foreach ($balls as $k => $b) {
                if (isset($b[1]) && $b[0] == '0') {
                    $balls[$k] = $b[1];
                }
            }
            $barrs[$cycid] = $balls;
            $barrs['time'][$cycid] = !empty($rs['preDrawTime']) ? strtotime($rs['preDrawTime']) : time();
        }
        return $barrs;
    }

    public function caijiOnce($lotcode, $cycleid = '')
    {
        $gurl = $this->api;
        $gurl .= $this->k168param[$lotcode]['onceaddr'] . '?' . $this->k168param[$lotcode]['param'];
        $gurl .= '&_=' . rand();
        $html = $this->getHtmlContent($gurl);
        if (empty($html)) {
            return -1;
        }
        $arr = json_decode($html, true);
        if (empty($arr['result']['data'])) {
            return -2;
        }
        $barrs = array();
        $cycid = intval($arr['result']['data']['preDrawIssue']);
        if ($cycid < $cycleid) { //采集最新一期小于于数据库最新一期
            return -3;
        }
        if ($cycid > $cycleid) { //采集最新一期大于数据库最新一期
            return -4;
        }
        $balls = explode(',', $arr['result']['data']['preDrawCode']);
        foreach ($balls as $k => $b) {
            if (isset($b[1]) && $b[0] == '0') {
                $balls[$k] = $b[1];
            }
        }
        $barrs[$cycid] = $balls;
        $barrs['time'][$cycid] = !empty($arr['result']['data']['preDrawTime']) ? strtotime($arr['result']['data']['preDrawTime']) : time();
        return $barrs;
    }

    public function index($lotcode)
    {

        //pka不跨天
      //   $res = $this->caijiHistory('pka',time());
      //   var_dump($res);
        //  $hh = $this->caijiOnce('pkt');
        // halt($hh);
        //   halt($res);
    }
}
