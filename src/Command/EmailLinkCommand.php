<?php

namespace App\Command;

use App\Utils\KafkaHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package App\Command
 */
class EmailLinkCommand extends Command
{
    use KafkaHelper;

    /**
     * @var string
     */
    protected static $defaultName = 'email:link';

    /**
     * @var string
     */
    protected $kafkaHost;

    /**
     * @var string
     */
    protected $kafkaPort;

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setDescription('Run kafka listener for sending emails with activation links');
    }

    /**
     * @param string $kafkaHost
     * @param string $kafkaPort
     */
    public function __construct(string $kafkaHost = '', string $kafkaPort = '')
    {
        parent::__construct();

        $this->kafkaHost = $kafkaHost;
        $this->kafkaPort = $kafkaPort;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $consumer = $this->configureConsumer($this->kafkaHost, $this->kafkaPort);
        $consumer->start(
            function ($topic, $part, $message) {

                $value = $message['message']['value'];
                dump($value);
            }
        );

        return 1;
    }
}
