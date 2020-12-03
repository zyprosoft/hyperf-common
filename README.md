####一、使用zyprosoft/hyperf-skeleton项目来创建脚手架
1. composer create-project zyprosoft/hyperf-skeleton
2. 完成后执行composer install

####ZGW协议接口开发
三段式接口名：大模块名.控制器.方法
使用AutoController("prefix=/大模块名/控制器")进行注解之后，
按照ZGW协议请求便可自动调用到对应的方法
如下示范:ZgwController下使用AutoController(prefix="/common/zgw")进行注解之后便可
请求到sayHello方法
```php
curl -d'{
    "version":"1.0",
    "seqId":"xxxxx",
    "timestamp":1601017327,
    "eventId":1601017327,
    "caller":"test",
    "interface":{
        "name":"common.zgw.sayHello",
        "param":{}
    }
}' http://127.0.0.1:9506
```

####普通协议开发可直接按照想要的路径做AutoController注解即可

####根据需求继承需要鉴权和不需要鉴权的Request
AdminRequest:需要管理员身份的请求
AuthedRequest:需要登陆身份的请求