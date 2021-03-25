<?php
/**
 * This file is part of ZYProSoft/Hyperf-Common.
 *
 * @link     http://zyprosoft.lulinggushi.com
 * @document http://zyprosoft.lulinggushi.com
 * @contact  1003081775@qq.com
 * @Company  泽湾普罗信息技术有限公司(ZYProSoft)
 * @license  GPL
 */
declare(strict_types=1);
namespace ZYProSoft\Http;

use Hyperf\Utils\Arr;
use ZYProSoft\Constants\Constants;
use Hyperf\Validation\Request\FormRequest;
use Qbhy\HyperfAuth\AuthManager;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Psr\Container\ContainerInterface;
use ZYProSoft\Constants\ErrorCode;
use ZYProSoft\Exception\HyperfCommonException;
use App\Model\User;
use ZYProSoft\Log\Log;

/**
 * 普通请求的封装
 * 可以实现按照请求规则的检查
 * Class Request
 * @package ZYProSoft\Http
 */
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
     * 是不是上传请求
     * @return bool
     */
    public function isUpload()
    {
        return !empty($this->getHeaderLine(Constants::ZYPROSOFT_UPLOAD));
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
        return Arr::has($this->getParams(), $key);
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
        return Arr::get($this->getParams(), $key);
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
        if (empty($this->getToken())) {
            throw new HyperfCommonException(ErrorCode::USER_REQUEST_TOKEN_NOT_FOUND);
        }
        $result = $this->auth->check();
        if ($result == false) {
            //检查令牌和数据库的存储是否一致
            $token = $this->getToken();
            $user = User::query()->where('token',$token)->first();
            if(!$user instanceof User) {
                Log::error("用户所使用令牌和数据库存储不一致，已经处于多端登陆失效状态，需要重新登陆!");
                throw new HyperfCommonException(ErrorCode::USER_REQUEST_TOKEN_EXPIRED_AND_NOT_MATCH);
            }
            Log::error("用户所使用令牌和数据库一致，但是已经失效，需要刷新Token");
            throw new HyperfCommonException(ErrorCode::USER_REQUEST_TOKEN_EXPIRED);
        }
        return true;
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