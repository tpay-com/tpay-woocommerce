<?php

namespace Tpay\Api;

use Tpay\Api\Dtos\Channel;
use Tpay\Api\Dtos\Constraint;
use Tpay\Api\Dtos\Group;
use Tpay\Api\Dtos\Image;
use Tpay\Helpers\Cache;
use Tpay\TpayLogger;

class Transactions
{
    protected const CHANNELS_CACHE_KEY = 'tpay_channels';

    protected $cache;
    protected $logger;
    protected $client;
    protected static $microCache = [];

    public function __construct(Client $client, Cache $cache)
    {
        $this->cache = $cache;
        $this->logger = new TpayLogger();
        $this->client = $client;
    }

    public function channels()
    {
        if (isset(self::$microCache['channels'])) {
            return self::$microCache['channels'];
        }

        $cached = $this->cache->get(self::CHANNELS_CACHE_KEY);
        if ($cached) {
            self::$microCache['channels'] = $cached;

            return $cached;
        }

        if (!$this->getTransactionsApi()) {
            return [];
        }

        $result = $this->getTransactionsApi()->getChannels();

        if (!isset($result['result']) || 'success' !== $result['result']) {
            $this->logger->error('Unable to retrieve channels from API');
        }

        $channels = array_map(function (array $channel) {
            return new Channel(
                (int) $channel['id'],
                $channel['name'],
                $channel['fullName'],
                new Image($channel['image']['url']),
                $channel['available'],
                $channel['onlinePayment'],
                $channel['instantRedirection'],
                array_map(function (array $group) {
                    return new Group((int) $group['id'], $group['name'], new Image($group['image']['url']));
                }, $channel['groups']),
                array_map(function (array $constraint) {
                    return new Constraint($constraint['field'], $constraint['type'], $constraint['value']);
                }, $channel['constraints'])
            );
        }, $result['channels']);

        $this->cache->set(self::CHANNELS_CACHE_KEY, $channels);
        self::$microCache['channels'] = $channels;

        return $channels;
    }

    private function getTransactionsApi()
    {
        return $this->client->connect() ? $this->client->connect()->transactions() : null;
    }
}
