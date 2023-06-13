## 采集玩法

### 数据库的表的创建

```sql
CREATE TABLE `pk_openlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lotcode` char(5) NOT NULL DEFAULT '',
  `cycleid` varchar(25) NOT NULL DEFAULT '',
  `cycleid_time` int(11) NOT NULL DEFAULT '0',
  `open_time` int(11) NOT NULL DEFAULT '0',
  `close_time` int(11) NOT NULL DEFAULT '0',
  `close_time2` int(11) NOT NULL DEFAULT '0',
  `b1` varchar(2) NOT NULL DEFAULT '',
  `b2` varchar(2) NOT NULL DEFAULT '',
  `b3` varchar(2) NOT NULL DEFAULT '',
  `b4` varchar(2) NOT NULL DEFAULT '',
  `b5` varchar(2) NOT NULL DEFAULT '',
  `b6` varchar(2) NOT NULL DEFAULT '',
  `b7` varchar(2) NOT NULL DEFAULT '',
  `b8` varchar(2) NOT NULL DEFAULT '',
  `b9` varchar(2) NOT NULL DEFAULT '',
  `b10` varchar(2) NOT NULL DEFAULT '',
  `att` tinyint(2) NOT NULL DEFAULT '0' COMMENT '0:预设盘口，1：已经开奖',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `cycleid` (`cycleid`) USING BTREE,
  KEY `open_time` (`open_time`) USING BTREE,
  KEY `close_time` (`close_time`) USING BTREE,
  KEY `lotcode` (`lotcode`) USING BTREE,
  KEY `att` (`att`),
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1477 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

```

###  采集数据的规划方案

> 添加计时器后拉取下当天最新数据

- 每10s采集一次数据
- 每3个小数同步下当天所有数据
- 每天凌晨1点清楚三天后的数据

- 采集开奖的地址:http://www.caiji.caiji:8080/period/caiji/lotcode/pka | pkt

> linux下的计时器的配置

```shell
#caiji.sh文件
#!/bin/bash 
step=10 #间隔的秒数，不能大于60 
for (( i = 0; i < 60; i=(i+step) )); do
  curl http://www.caiji.caiji:8080/period/caiji/lotcode/pka
  curl http://www.caiji.caiji:8080/period/caiji/lotcode/pkt
  sleep $step 
done
exit 0
```

```shell
# 处理每间隔10s执行一次采集数据
* * * * * bash /home/shell/caiji.sh 
# 每三天清除数据库的内容
3 0 */3 * * curl http://www.caiji.caiji:8080/period/clear
```

shell文件放在/home/shell的文件夹下
dos2unix caiji.sh要转换编码

> 服务器的的部署

- 用ip+端口的方式 端口建议是偶数
- firewall-cmd --zone=public --add-port=12/tcp --permanent   后台的端口
- firewall-cmd --zone=public --add-port=14/tcp --permanent   前台的端口
- systemctl restart firewalld.service  重启防火墙的服务
- firewall-cmd --reload  重启规则
- firewall-cmd --list-all  查看所有的开发的端口

```
#后台的nginx的配置
 server {
        listen       12;
        server_name  localhost;
		root  /usr/local/nginx/html/caiji.admin/public;
        #charset koi8-r;

        access_log  logs/caiji.admin.access.log  main;
        location / {
            index index.php index.html  index.htm;
			if (!-e $request_filename) {
				rewrite  ^(.*)$  /index.php?s=/$1  last;
				break;
			}
        }
        error_page  404              /404.html;
        # redirect server error pages to the static page /50x.html
        #
        error_page   500 502 503 504  /50x.html;
        location = /50x.html {
            root   html;
        }
        # proxy the PHP scripts to Apache listening on 127.0.0.1:80
        #
        #location ~ \.php$ {
        #    proxy_pass   http://127.0.0.1;
        #}
        # pass the PHP scripts to FastCGI server listening on 127.0.0.1:9000
        #
        location ~ \.php$ {
            fastcgi_pass   127.0.0.1:9000;
            fastcgi_index  index.php;
            include    fastcgi.conf;
        }
        # deny access to .htaccess files, if Apache's document root
        # concurs with nginx's one
        #
        location ~ /\.ht {
            deny  all;
        }
    }
#前端的配置
 server {
    listen       14;
    server_name  localhost;
    root  /usr/local/nginx/html/caiji.front;
    #charset koi8-r;

    access_log  logs/caiji.front.log  main;
    index index.html  index.htm;
    location ~ /\.ht {
        deny  all;
    }
}

```

