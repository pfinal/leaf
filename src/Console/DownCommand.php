<?php

namespace Leaf\Console;

use Leaf\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DownCommand extends Command
{
    //运行  php console down
    protected function configure()
    {
        $this->setName('down')
            ->setDescription('Put the application into maintenance mode')
            ->setDefinition([

                //消息
                new InputOption('message', 'm', InputOption::VALUE_OPTIONAL, 'The message for the maintenance mode.', 'Be right back.'),

                //预计维护时长(秒)
                new InputOption('retry', 'r', InputOption::VALUE_OPTIONAL, 'The number of seconds after which the request may be retried.'),

            ]);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        file_put_contents(
            Application::$app->getRuntimePath('down'),
            json_encode($this->getDownFilePayload($input), JSON_PRETTY_PRINT)
        );

        $output->writeln('Application is now in maintenance mode.');
    }

    /**
     * @return array
     */
    protected function getDownFilePayload(InputInterface $input)
    {
        return array(
            'time' => time(),
            'message' => $input->getOption('message'),
            'retry' => $this->getRetryTime($input),
        );
    }

    /**
     * 客户端多少秒后重试
     *
     * @return int|null
     */
    protected function getRetryTime(InputInterface $input)
    {
        $retry = $input->getOption('retry');

        return is_numeric($retry) && $retry > 0 ? (int)$retry : null;
    }
}