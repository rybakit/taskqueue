<?php

namespace TaskQueue\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowTaskCommand extends Command
{
    protected function configure()
    {
        $this
        ->setName('task_queue:task:show')
        ->setDescription('Shows a task.')
        ->setDefinition(array(
            new InputArgument('task-id', InputArgument::REQUIRED, 'The task id'),
        ))
        ->setHelp(<<<EOT
Shows a task.
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $this->getHelper('task_queue')->getQueue();
        /*
        $tasks = $queue->getBy(array('id' => $input->getArgument('task-id')));

        $tasks->rewind();
        $task = $tasks->current();

        $output->write(PHP_EOL . sprintf('Id: %s', $task->getId()) . PHP_EOL);
        */
    }
}