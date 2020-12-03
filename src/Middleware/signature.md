####签名算法，针对zgw协议

```
{
    "seqId":"xxx",
    "version":"xxx",
    "caller":"xxx",
    "eventId":"xxx",
    "timestamp":123445,
    "auth":{
        "timestamp":12345,
        "signature":"xxxxx",
        "appId":"xxxxx",
        "nonce":"xxxxx"
    },
    "interface":{
        "name":"zyprosoft.plan.getPlanList",
        "param":{
            "key1":"xxx",
            "key2":"xxxx",
        }
    }
}
```

1. 得到参数数组
```
$param = [
    "key1":"xxx",
    "key2":"xxx",
];
```
2. 加入接口信息
```
$param["interfaceName"] = "zyprosoft.plan.getPlanList";
```
3. 按照参数名升序
```
ksort($param);
```
4. json编码和MD5
```
$paramString = md5(json_encode($param));
```
5. 拼接签名字符串，固定格式
```$xslt
$timestamp = time();
$nonce = rand();
$appId = "test";
$appSecret = "abcdefg";
$base = "appId=$appId&appSecret=$appSecret&nonce=$nonce&timestamp=$timestamp&$paramString";
```
6. 加密
```$xslt
hash_hmac("sha256", $base, $appSecret)
```


