<?php

namespace Kanata\Services;

use Exception;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class Console
{
    private static function get_output_instance(): ConsoleOutputInterface {
        return new ConsoleOutput;
    }

    private static function get_input_instance(ConsoleOutputInterface $output): InputInterface {
        global $argv;

        $definition = new InputDefinition([
            new InputOption(HTTP_PORT_PARAM, null, InputOption::VALUE_OPTIONAL, '', HTTP_SERVER_PORT),

            // websocket
            new InputOption(WEBSOCKET_CONSOLE_OPTION, null, InputOption::VALUE_NONE),
            new InputOption(WEBSOCKET_PORT_PARAM, null, InputOption::VALUE_OPTIONAL, '', WS_SERVER_PORT),

            // queues
            new InputOption(QUEUE_CONSOLE_OPTION, null, InputOption::VALUE_NONE),
            new InputOption(QUEUE_NAME_CONSOLE_OPTION, null, InputOption::VALUE_REQUIRED),

            // fresh
            new InputOption(FRESH_CONSOLE_OPTION, null, InputOption::VALUE_NONE),

            // fresh-plugins
            new InputOption(FRESH_PLUGINS_CONSOLE_OPTION, null, InputOption::VALUE_NONE),

            // arguments
            new InputArgument('arg', InputArgument::OPTIONAL),
            new InputArgument('arg2', InputArgument::OPTIONAL),
            new InputArgument('arg3', InputArgument::OPTIONAL),
            new InputArgument('arg4', InputArgument::OPTIONAL),
            new InputArgument('arg5', InputArgument::OPTIONAL),
        ]);

        try {
            $input = new ArgvInput($argv, $definition);

            if ($input->getOption(QUEUE_CONSOLE_OPTION) && null === $input->getOption(QUEUE_NAME_CONSOLE_OPTION)) {
                throw new Exception('Queues must have queue-name option specified!');
            }

            if ($input->getOption('queue') && $input->getOption(WEBSOCKET_CONSOLE_OPTION)) {
                throw new Exception('A single execution can\'t have queues and websockets at the same time specified!');
            }
        } catch (Exception $e) {
            $output->writeln('');
            $output->writeln('<error>There was an error while starting application: ' . $e->getMessage() . '</error>');
            $output->writeln('');
            exit(1);
        }

        return $input;
    }

    public static function start(): void
    {
        $container = container();
        $output = self::get_output_instance();
        $container['output'] = $output;
        $container['input'] = self::get_input_instance($output);
    }
}