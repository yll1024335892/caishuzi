<?php
// 应用公共文件

function headerAjax(){
    header("access-control-allow-headers: Accept,Authorization,Cache-Control,Content-Type,DNT,If-Modified-Since,Keep-Alive,Origin,User-Agent,X-Mx-ReqToken,X-Requested-With");
    header("access-control-allow-methods: GET, POST, PUT, DELETE, HEAD, OPTIONS");
    header("access-control-allow-credentials: true");
    header("access-control-allow-origin: *");
    header('X-Powered-By: WAF/2.0');
}


