<?php
declare(strict_types=1);

namespace ZYProSoft\Controller;

use ZYProSoft\Http\Request;
use Hyperf\Contract\ContainerInterface;
use ZYProSoft\Http\Response;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use ZYProSoft\Constants\ErrorCode;
use ZYProSoft\Exception\HyperfCommonException;

abstract class AbstractController
{
    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var ValidatorFactoryInterface
     */
    protected $validatorFactory;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->request = $container->get(Request::class);
        $this->response = $container->get(Response::class);
        $this->validatorFactory = $container->get(ValidatorFactoryInterface::class);
    }

    /**
     * 指定规则检测
     * @param $rules
     * @return array
     */
    public function validate($rules)
    {
        $validator = $this->validatorFactory->make($this->request->getParams(), $rules);
        $validator->validate();
        if ($validator->fails()) {
            $errorMsg = $validator->errors()->first();
            throw new HyperfCommonException(ErrorCode::PARAM_ERROR, $errorMsg);
        }
        return $validator->validated();
    }

    protected function getUserId()
    {
        return $this->request->getUserId();
    }

    protected function success($result = [])
    {
       return $this->response->success($result);
    }

    protected function weChatSuccess($result = '')
    {
        return $this->response->toWeChatXml($result);
    }
}