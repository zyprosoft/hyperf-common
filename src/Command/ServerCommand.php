<?php

declare(strict_types=1);

namespace ZYProSoft\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @Command
 */
class ServerCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('server');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Server control command');
        $this->addArgument("action", InputArgument::REQUIRED, "control action");
    }

    public function handle()
    {
        $action = $this->input->getArgument("action");
        if (empty($action) || !in_array($action, ["stop","restart","start"])) {
            $this->line("action type require in (stop,restart,start)");
        }
        $this->executeAction($action);
    }

    private function executeAction($action)
    {
        $name = env("APP_NAME");
        $shellPath = BASE_PATH."/bin";
        $command = "cd $shellPath && bash service.sh -t $action -n $name";
        $result = system($command);
        if (!$result) {
            $this->line("run action $action fail!");
        }
    }
}
