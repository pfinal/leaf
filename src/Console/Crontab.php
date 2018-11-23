<?php

namespace Leaf\Console;

use Carbon\Carbon;
use Leaf\Cache;
use Leaf\DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class Crontab extends Command
{
    protected function configure()
    {
        $this->setName('crontab');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        //异常处理
        $this->getApplication()->setCatchExceptions(false);

        //"mtdowling/cron-expression": "^1.2",
        //symfony/process": "2.*",

        // DROP TABLE IF EXISTS pre_crontab;
        // CREATE TABLE `pre_crontab` (
        //  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        //  `name` varchar(50) DEFAULT '',
        //  `time` varchar(255) DEFAULT '',
        //  `cmd` varchar(255) DEFAULT '',
        //  `out` text,
        //  `err` text,
        //  PRIMARY KEY (`id`),
        //  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '新增时间',
        //  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间'
        // ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        // 1.操作系统中加入一条每分钟执行的计划任务
        // crontab -e
        // /data/webroot/demo/console crontab

        // 2. 项目中的计划任务，在数据库中维护
        //INSERT INTO `pre_crontab` (`name`, `time`, `cmd`) VALUES ('hello', '* * * * *', './console hello -a 20');
        $crontabArr = DB::table('crontab')->findAll();

        //$output->writeln('crontab:' . count($crontabArr) . Carbon::now());

        //单条任务允许执行的最长时间 秒
        $timeout = 60 * 30;

        $mutexKey = 'crontabMutex';

        //上次任务没有结束
        if (Cache::get($mutexKey)) {
            return;
        }

        //加一个过期时间，防止出错时，没有删除$mutexKey导致死锁
        //缓存时间为所有任务允许执行的最大时间之和 + 10s
        Cache::set($mutexKey, true, $timeout * count($crontabArr) + 10);

        try {
            foreach ($crontabArr as $crontab) {

                $output->writeln('start:' . $crontab['name']);

                // $cron = \Cron\CronExpression::factory('*/2 * * * *');
                // echo $cron->getPreviousRunDate()->format('Y-m-d H:i:s');
                // echo $cron->getNextRunDate()->format('Y-m-d H:i:s');
                // var_dump($cron->isDue());

                if (empty($crontab['time']) || empty($crontab['cmd'])) {
                    continue;
                }

                $cron = \Cron\CronExpression::factory($crontab['time']);

                if ($cron->isDue()) {

                    $output->writeln('execute:' . $crontab['name']);

                    $process = new Process($crontab['cmd'], COMMAND_PATH, null, null, $timeout);

                    $out = $err = '';
                    $process->run(function ($type, $buffer) use (&$out, &$err, $output) {

                        if (Process::ERR === $type) {
                            $err .= $buffer;
                        } else {
                            $out .= $buffer;
                        }

                        $output->writeln($buffer);

                    });

                    $updated_at = Carbon::now();
                    $res = DB::table('crontab')->wherePk($crontab['id'])->update(compact('err', 'out', 'updated_at'));

                    $output->writeln('update db:' . $res);

                }

               // $output->writeln('end:' . $crontab['name']);

            }
        } catch (\Exception $ex) {
            DB::table('crontab')->wherePk($crontab['id'])->update(['out' => '', 'err' => $ex->getMessage(), 'updated_at' => Carbon::now()]);

        } catch (\Throwable $ex) { // php7+
            DB::table('crontab')->wherePk($crontab['id'])->update(['out' => '', 'err' => $ex->getMessage(), 'updated_at' => Carbon::now()]);
        }

        Cache::delete($mutexKey);

        //$output->writeln('======' . count($crontabArr) . Carbon::now());

        //$output->writeln("success");
    }

}