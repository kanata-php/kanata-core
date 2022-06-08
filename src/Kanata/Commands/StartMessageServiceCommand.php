<?php

namespace Kanata\Commands;

use Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use voku\helper\Hooks;

class StartMessageServiceCommand extends Command
{
    protected static $defaultName = 'message';

    protected static $defaultDescription = 'Start Message Service';

    protected function configure(): void
    {
        $this
            ->setHelp(self::$defaultDescription)
            ->addOption(QUEUE_NAME_CONSOLE_OPTION, null, InputOption::VALUE_OPTIONAL, 'Message queue name. Default: "default"', 'default');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        container()->set('context', 'message');
        container()->set('input', $input);
        container()->set('output', $output);

        $connection = new AMQPStreamConnection(
            QUEUE_SERVER_HOST,
            QUEUE_SERVER_PORT,
            QUEUE_SERVER_USER,
            QUEUE_SERVER_PASSWORD
        );
        $channel = $connection->channel();

        $default_queue_attributes = [
            'exchange' => 'default',
            'exchange_type' => 'topic',
            'queue' => 'default',
            'routing_key' => '',
            'option' => '--default',
            'callback' => null,
        ];

        /**
         * This is the default queue.
         */
        $queues = [];
        if ($input->getOption(QUEUE_NAME_CONSOLE_OPTION) === 'default') {
            $queues = !DEFAULT_QUEUE ? [] : [
                'default' => [
                    'callback' => function ($msg) {
                        container()->logger->info("Default handler received: " . $msg->body . PHP_EOL);
                    },
                ],
            ];
        }

        /**
         * Important: don't forget to register the option as a new supervisor service for each extra queue added through
         * this hook. The `option` parameter here is the option passed to the command there.
         *
         * Command on supervisor: /usr/bin/php /var/www/html/index.php --queue --queue-name=example-option
         *
         * Format:
         *     [
         *         [
         *             'queue' => 'queue-name',
         *             'exchange' => 'example-exchange',
         *             'routing-key' => 'example-routing-key',
         *             'option' => 'example-option',
         *             'callback' => Callable,
         *         ],
         *         ...
         *     ]
         */
        $queues = Hooks::getInstance()->apply_filters('queues', $queues);

        foreach ($queues as $queue) {
            $queue = array_merge($default_queue_attributes, $queue);

            if ($input->getOption(QUEUE_NAME_CONSOLE_OPTION) !== $queue['option']) {
                continue;
            }

            try {
                $channel->exchange_declare($queue['exchange'], $queue['exchange_type'], false, false, false);
                $channel->queue_declare($queue['queue'], false, false, false, false);
                $channel->queue_bind($queue['queue'], $queue['exchange'], $queue['routing_key']);
                $channel->basic_consume($queue['queue'], '', false, true, false, false, $queue['callback']);
            } catch (Exception $e) {
                logger()->error(
                    'There was an error while starting queues: ' . $e->getMessage() . PHP_EOL
                    . 'Data: ' . json_encode($queue)
                );
                continue;
            }

            logger()->info('Queue Service: [' . $queue['queue'] . '] Waiting for messages.' . PHP_EOL);
        }

        while ($channel->is_open()) {
            $channel->wait();
        }

        return Command::SUCCESS;
    }
}