<?php


namespace ZYProSoft\Listener;


use ZYProSoft\Service\SensitiveService;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\Event\ValidatorFactoryResolved;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Event\Annotation\Listener;
use ZYProSoft\Log\Log;

/**
 * @Listener
 * Class SensitiveValidatorListener
 * @package App\Listener
 */
class SensitiveValidatorListener implements ListenerInterface
{
    /**
     * @Inject
     * @var SensitiveService
     */
    private SensitiveService $service;

    public function listen(): array
    {
        return [
            ValidatorFactoryResolved::class,
        ];
    }

    public function process(object $event)
    {
        /**  @var ValidatorFactoryInterface $validatorFactory */
        $validatorFactory = $event->validatorFactory;
        // 注册了 foo 验证器
        $validatorFactory->extend('sensitive', function ($attribute, $value, $parameters, $validator) {
            Log::info("process sensitive rule with value:$value");
            return $this->service->isSensitive($value) == false;
        });
        // 当创建一个自定义验证规则时，你可能有时候需要为错误信息定义自定义占位符这里扩展了 :foo 占位符
        $validatorFactory->replacer('sensitive', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, $message);
        });
    }
}