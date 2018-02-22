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

    /** @var  bool $throwExceptionOnNotOkResponse */
    private $throwExceptionOnNotOkResponse;

    protected static $transactionQueryAllowedOptions = [self::ADDRESS_STRING, self::BLOCK_STRING];

    /**
     * InsightClient constructor.
     * @param $base_uri
     * @param null $handler
     * @param bool $exceptionOnNotOkResponse
     */
    public function __construct(string $base_uri, $handler = null, bool $exceptionOnNotOkResponse = true)
    {
        $this->setThrowExceptionOnNotOkResponse($exceptionOnNotOkResponse);

        $options = ['base_uri' => $base_uri, 'http_errors'     => false,];
        if (null !== $handler) {
            $options['handler'] = $handler;
        }

        $this->client = new Client($options);
      }

    /**
     * @param string $hash
     * @return array
     */
    public function getBlock(string $hash) : array
    {
        if (!$hash) {
            $this->onInvalidArgument('Block hash parameter is required');
        }

        return $this->sendGet("/block/{$hash}");
    }

    /**
     * @param int $index
     * @return array
     */
    public function getBlockByHeight(int $index) : array
    {
        if ($index < 0) {
            $this->onInvalidArgument('Block index parameter is required');
        }

        $body = $this->sendGet("/block-index/{$index}");

        return $this->getBlock($body['blockHash']);
    }

    /**
     * @param null $date
     * @param int $limit
     * @return array
     */
    public function getBlockSummaries($date = null, $limit = 100) : array
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
     * @return string
     */
    public function getCurrency() : string
    {
        $response = $this->sendGet('/currency');

        return $response['data']['bitstamp'];
    }

    /**
     * @param string $hash
     * @return string
     */
    public function getRawBlock(string $hash) : string
    {
        if (!$hash) {
            $this->onInvalidArgument('Block hash parameter is required');
        }

        $body = $this->sendGet("/rawblock/{$hash}");

        return $body['rawblock'];
    }

    /**
     * @param $index
     * @return string
     */
    public function getRawBlockByHeight(int $index) : string
    {
        if ($index < 0) {
            $this->onInvalidArgument('Block index parameter is required');
        }

        $body = $this->sendGet("/block-index/{$index}");

        return $this->getRawBlock($body['blockHash']);
    }

    /**
     * @param string $transactionId
     * @return array
     */
    public function getTransaction(string $transactionId) : array
    {
        if (!$transactionId) {
            $this->onInvalidArgument('Transaction Id parameter is required');
        }

        return $this->sendGet("/tx/{$transactionId}");
    }

    /**
     * @param string $transactionId
     * @return array
     */
    public function getRawTransaction(string $transactionId) : array
    {
        if (!$transactionId) {
            $this->onInvalidArgument('Transaction Id parameter is required');
        }

        return $this->sendGet("/rawtx/{$transactionId}");
    }

    /**
     * @param string $address
     * @param bool $noTxList
     * @param int $fromTx
     * @param int $toTx
     * @return array
     */
    public function getAddress(string $address, $noTxList = false, $fromTx = 0, $toTx = 1000) : array
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
     *
     * @param string $address
     * @param string $property
     * @return int
     */
    public function getAddressProperty(string $address, string $property) : int
    {
        if (!$address || !$property) {
            $this->onInvalidArgument('`address` and `property` arguments are required');
        }

        return $this->sendGet("/addr/{$address}/{$property}");
    }

    /**
     * The response contains the value in Satoshis.
     * @param string $address
     * @return int
     */
    public function getAddressBalanceInSatoshi(string $address) : int
    {
        return $this->getAddressProperty($address, 'balance');
    }

    /**
     * The response contains the value in Satoshis.
     * @param $address
     * @return int
     */
    public function getAddressTotalReceivedInSatoshi($address) : int
    {
        return $this->getAddressProperty($address, 'totalReceived');
    }

    /**
     * The response contains the value in Satoshis.
     * @param string $address
     * @return int
     */
    public function getAddressTotalSentInSatoshi(string $address)
    {
        return $this->getAddressProperty($address, 'totalSent');
    }

    /**
     * The response contains the value in Satoshis.
     * @param string $address
     * @return int
     */
    public function getAddressUnconfirmedBalanceInSatoshi(string $address)
    {
        return $this->getAddressProperty($address, 'unconfirmedBalance');
    }

    /**
     * @param string $address
     * @return int
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
     * @return array
     */
    public function getMultipleAddressesUnspentOutputs(array $addresses) : array
    {
        if (empty($addresses) || !is_array($addresses)) {
            $this->onInvalidArgument('Argument `addresses` is not an array of addresses.');
        }

        return $this->sendGet(sprintf('/addrs/%s/utxo', implode(',', $addresses)));
    }

    /**
     * @param string $option
     * @param string $argument
     * @return array
     */
    public function getTransactions(string $option, string $argument) : array
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
     * @param string $hash
     * @return array
     */
    public function getTransactionsByBlock(string $hash) : array
    {
        return $this->getTransactions(self::BLOCK_STRING, $hash);
    }

    /**
     * @param string $address
     * @return array
     */
    public function getTransactionsByAddress(string $address) : array
    {
        return $this->getTransactions(self::ADDRESS_STRING, $address);
    }

    /**
     * Will ignore `from` and `to` params if one of them is null.
     * If pagination params are not specified, the result is an array of transactions. otherwise it will return
     * pagination details and all transactions under `items` key.
     * @param array $addresses
     * @param null $from
     * @param null $to
     * @return array
     */
    public function getTransactionsForMultipleAddresses(array $addresses, $from = null, $to = null) : array
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
     * @param string $url
     * @param array $query
     * @return array|int
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
        if (true === $this->shouldThrowExceptionOnNotOkResponse() && ($code < 200 || $code > 299)) {
            throw new BlockchainCallException();
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
     * @param bool $throwExceptionOnNotOkResponse
     * @return InsightClient
     */
    public function setThrowExceptionOnNotOkResponse(bool $throwExceptionOnNotOkResponse) : InsightClient
    {
        $this->throwExceptionOnNotOkResponse = (bool)$throwExceptionOnNotOkResponse;

        return $this;
    }

    /**
     * @return bool
     */
    public function shouldThrowExceptionOnNotOkResponse() : bool
    {
        return $this->throwExceptionOnNotOkResponse;
    }
}
