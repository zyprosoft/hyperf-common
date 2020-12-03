<?php


namespace ZYProSoft\Http;

use ZYProSoft\Constants\Constants;
use Hyperf\Validation\Request\FormRequest;
use Qbhy\HyperfAuth\AuthManager;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Psr\Container\ContainerInterface;

class Request extends FormRequest
{
    /**
     * @var AuthManager
     */
    protected $auth;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->auth = $this->container->get(AuthManager::class);
    }

    /**
     * 是否ZGW协议
     * @return bool
     */
    public function isZgw()
    {
        return !empty($this->getHeaderLine(Constants::ZYPROSOFT_ZGW));
    }

    /**
     * 获取请求参数
     * @return array|mixed
     */
    public function getParams()
    {
        if (!$this->isMethod('POST')) {
            return  $this->getQueryParams();
        }
        if ($this->isZgw()) {
            return $this->post("interface.param");
        }
        return $this->post();
    }

    /**
     * 是否有传某个参数
     * @param string $key
     * @return bool
     */
    public function hasParam(string $key)
    {
        return isset($this->getParams()[$key]);
    }

    /**
     * 取参数
     * @param string $key
     * @param null $default
     * @return mixed|null
     */
    public function param(string $key, $default = null)
    {
        if (!$this->hasParam($key)) {
            return $default;
        }
        return $this->getParams()[$key];
    }

    public function getUserId()
    {
        return $this->auth->user()->getId();
    }

    public function getToken()
    {
        return $this->input("token");
    }

    public function isLogin()
    {
        return $this->auth->check();
    }

    /**
     * 需要重写
     * @return bool
     */
    protected function isAdmin()
    {
        return  false;
    }

    /**
     * 根据协议修改验证内容
     * @return array
     */
    protected function validationData(): array
    {
        return  $this->getParams();
    }

    /**
     * 将请求转成easyWeChat的请求
     */
    public function easyWeChatRequest()
    {
        $get = $this->getQueryParams();
        $post = $this->getParsedBody();
        $cookie = $this->getCookieParams();
        $uploadFiles = $this->getUploadedFiles() ?? [];
        $server = $this->getServerParams();
        $xml = $this->getBody()->getContents();
        $files = [];
        /** @var \Hyperf\HttpMessage\Upload\UploadedFile $v */
        foreach ($uploadFiles as $k => $v) {
            $files[$k] = $v->toArray();
        }
        $request = new SymfonyRequest($get, $post, [], $cookie, $files, $server, $xml);
        $request->headers = new HeaderBag($this->getHeaders());
        return $request;
    }
}