<?php

namespace Rybakit\TaskQueue\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Rybakit\JobQueue\Job\Api\StandardInterface;

class ShowTaskQueueCommand extends Command
{
    protected function configure()
    {
        $this
        ->setName('task_queue:show')
        ->setDescription('Shows task queue.')
        ->setDefinition(array())
        ->setHelp(<<<EOT
Shows task queue.
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $taskQueue = $this->getHelper('task_queue')->getTaskQueue();

        $now = new \DateTime();
        $criteria = array(
/*
            'status'            => StandardInterface::STATUS_SCHEDULED,
            'scheduledAt:lte'   => $now,
            'completedAt:lte'   => $now,
*/
        );

        $tasks = $taskQueue->getBy($criteria, 50);

        foreach ($tasks as $task) {
            $output->write(PHP_EOL . sprintf('Id: %s', $task->getId()) . PHP_EOL);
        }
    }
}