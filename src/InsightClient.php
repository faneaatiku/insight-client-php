<?php
namespace BTCZ\Insight;

use BTCZ\Insight\Exception\BlockchainCallException;
use BTCZ\Insight\Exception\InvalidArgumentException;
use GuzzleHttp\Client;

class InsightClient
{
    const ADDRESS_STRING    = 'address';
    const BLOCK_STRING      = 'block';
    /** @var Client $client */
    private $client;

    /** @var  bool $exceptionOnNotOkResponse */
    private $exceptionOnNotOkResponse;

    protected static $transactionQueryAllowedOptions = [self::ADDRESS_STRING, self::BLOCK_STRING];

    /**
     * InsightClient constructor.
     * @param $base_uri
     * @param null $handler
     * @param bool $exceptionOnNotOkResponse
     */
    public function __construct($base_uri, $handler = null, $exceptionOnNotOkResponse = true)
    {
        $this->setExceptionOnNotOkResponse($exceptionOnNotOkResponse);

        $options = ['base_uri' => $base_uri];
        if (null !== $handler) {
            $options['handler'] = $handler;
        }

        $this->client = new Client($options);
      }

    /**
     * @param $hash
     * @return mixed
     */
    public function getBlock(string $hash)
    {
        if (!$hash) {
            $this->onInvalidArgument('Block hash parameter is required');
        }

        return $this->sendGet("/block/{$hash}");
    }

    /**
     * @param $index
     * @return mixed
     */
    public function getBlockByHeight($index)
    {
        if (!$index) {
            $this->onInvalidArgument('Block index parameter is required');
        }

        $body = $this->sendGet("/block-index/{$index}");

        return $this->getBlock($body['blockHash']);
    }

    /**
     * @param null $date
     * @param int $limit
     * @return mixed
     */
    public function getBlockSummaries($date = null, $limit = 100)
    {
        $queryParams = [
            'limit' => $limit
        ];

        if ($date) {
            $queryParams['date'] = $date;
        }

        return $this->sendGet("/blocks", $queryParams);
    }

    /**
     * @return mixed
     * @throws \Error
     */
    public function getCurrency()
    {
        $response = $this->sendGet('/currency');

        return $response['data']['bitstamp'];
    }

    /**
     * @param $hash
     * @return mixed
     */
    public function getRawBlock(string $hash)
    {
        if (!$hash) {
            $this->onInvalidArgument('Block hash parameter is required');
        }

        $body = $this->sendGet("/rawblock/{$hash}");

        return $body['rawblock'];
    }

    /**
     * @param $index
     * @return mixed
     */
    public function getRawBlockByHeight($index)
    {
        if (!$index) {
            $this->onInvalidArgument('Block index parameter is required');
        }

        $body = $this->sendGet("/block-index/{$index}");

        return $this->getRawBlock($body['blockHash']);
    }

    /**
     * @param $transactionId
     * @return mixed
     */
    public function getTransaction(string $transactionId)
    {
        if (!$transactionId) {
            $this->onInvalidArgument('Transaction Id parameter is required');
        }

        return $this->sendGet("/tx/{$transactionId}");
    }

    /**
     * @param $transactionId
     * @return mixed
     */
    public function getRawTransaction(string $transactionId)
    {
        if (!$transactionId) {
            $this->onInvalidArgument('Transaction Id parameter is required');
        }

        return $this->sendGet("/rawtx/{$transactionId}");
    }

    /**
     * @param $address
     * @param bool $noTxList
     * @param int $fromTx
     * @param int $toTx
     * @return mixed
     */
    public function getAddress(string $address, $noTxList = false, $fromTx = 0, $toTx = 1000)
    {
        if (!$address) {
            $this->onInvalidArgument('Address is required');
        }

        $queryParams = [
            'noTxList' => (int)$noTxList,
            'from' => $fromTx,
            'to' => $toTx,
        ];

        return $this->sendGet("/addr/{$address}", $queryParams);
    }

    /**
     * The response contains the value in Satoshis.
     * @param $address
     * @param $property
     * @return mixed
     */
    public function getAddressProperty(string $address, string $property)
    {
        if (!$address || !$property) {
            $this->onInvalidArgument('`address` and `property` arguments are required');
        }

        return $this->sendGet("/addr/{$address}/{$property}");
    }

    /**
     * The response contains the value in Satoshis.
     * @param $address
     * @return mixed
     */
    public function getAddressBalanceInSatoshi(string $address)
    {
        return $this->getAddressProperty($address, 'balance');
    }

    /**
     * The response contains the value in Satoshis.
     * @param $address
     * @return mixed
     */
    public function getAddressTotalReceivedInSatoshi($address)
    {
        return $this->getAddressProperty($address, 'totalReceived');
    }

    /**
     * The response contains the value in Satoshis.
     * @param $address
     * @return mixed
     */
    public function getAddressTotalSentInSatoshi(string $address)
    {
        return $this->getAddressProperty($address, 'totalSent');
    }

    /**
     * The response contains the value in Satoshis.
     * @param $address
     * @return mixed
     */
    public function getAddressUnconfirmedBalanceInSatoshi(string $address)
    {
        return $this->getAddressProperty($address, 'unconfirmedBalance');
    }

    /**
     * @param $address
     * @return mixed
     */
    public function getAddressUnspentOutputs(string $address)
    {
        if (!$address) {
            $this->onInvalidArgument('Address argument is required ot get unspent outputs');
        }

        return $this->sendGet("/addr/{$address}/utxo");
    }

    /**
     * @param array $addresses
     * @return mixed
     */
    public function getMultipleAddressesUnspentOutputs(array $addresses)
    {
        if (empty($addresses) || !is_array($addresses)) {
            $this->onInvalidArgument('Argument `addresses` is not an array of addresses.');
        }

        return $this->sendGet(sprintf('/addrs/%s/utxo', implode(',', $addresses)));
    }

    /**
     * @param $option
     * @param $argument
     * @return mixed
     */
    public function getTransactions(string $option, string $argument)
    {
        if (!in_array($option, self::$transactionQueryAllowedOptions, true)) {
            $this->onInvalidArgument(
                sprintf(
                    'Transactions can be queried with options [%s] received [%s] ',
                    implode(',', self::$transactionQueryAllowedOptions),
                    $option
                )
            );
        }

        if (!$argument) {
            $this->onInvalidArgument(sprintf('Invalid %s passed to transaction querying.', $option));
        }

        $queryParams = [
            $option => $argument
        ];

        return $this->sendGet("/txs/", $queryParams);
    }

    /**
     * @param $hash
     * @return mixed
     */
    public function getTransactionsByBlock(string $hash)
    {
        return $this->getTransactions(self::BLOCK_STRING, $hash);
    }

    /**
     * @param $address
     * @return mixed
     */
    public function getTransactionsByAddress(string $address)
    {
        return $this->getTransactions(self::ADDRESS_STRING, $address);
    }

    /**
     * Will ignore `from` and `to` params if one of them is null.
     * If pagination params are not specified, the result is an array of transactions. otherwise it will return
     * pagination details and all transactions under `items` key.
     *
     * @param array $addresses
     * @param null $from
     * @param null $to
     * @return mixed
     */
    public function getTransactionsForMultipleAddresses(array $addresses, $from = null, $to = null)
    {
        if (empty($addresses) || !is_array($addresses)) {
            $this->onInvalidArgument('Argument `addresses` is not an array of addresses.');
        }

        $queryParams = [];
        if (null !== $from && null !== $to) {
            $queryParams['from'] = $from;
            $queryParams['to'] = $to;
        }

        return $this->sendGet(sprintf('/addrs/%s/txs', implode(',', $addresses)), $queryParams);
    }

    /**
     * @param $url
     * @param array $query
     * @return mixed
     * @throws BlockchainCallException
     */
    public function sendGet(string $url, array $query = [])
    {
        $response = $this->client->request(
            'GET',
            $url,
            ['query' => $query]
        );

        $code = $response->getStatusCode();
        if ($code !== 200 && true === $this->exceptionOnNotOkResponse) {
            throw new BlockchainCallException($response->getBody(), $response->getStatusCode());
        }

        $body = $response->getBody();

        return json_decode($body, true);
    }

    /**
     * @param string $errorMessage
     * @throws InvalidArgumentException
     */
    protected function onInvalidArgument(string $errorMessage = 'Invalid argument')
    {
        throw new InvalidArgumentException($errorMessage);
    }

    /**
     * @param bool $exceptionOnNotOkResponse
     * @return $this
     */
    public function setExceptionOnNotOkResponse(bool $exceptionOnNotOkResponse)
    {
        $this->exceptionOnNotOkResponse = (bool)$exceptionOnNotOkResponse;

        return $this;
    }
}
