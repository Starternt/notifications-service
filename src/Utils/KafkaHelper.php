<?php

namespace App\Utils;

use Kafka\Consumer;
use Kafka\ConsumerConfig;

trait KafkaHelper
{
    /**
     * @param string $host
     * @param string $port
     * @param string $topic
     *
     * @return Consumer
     */
    public function configureConsumer(string $host, string $port, string $topic): Consumer
    {
        $config = ConsumerConfig::getInstance();
        $config->setMetadataRefreshIntervalMs(10000);
        $config->setMetadataBrokerList($host.':'.$port);
        $config->setGroupId($topic);
        $config->setBrokerVersion('1.0.0');
        $config->setTopics([$topic]);
        $config->setOffsetReset('earliest');

        return new Consumer();
    }
}