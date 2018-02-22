<?php
namespace BTCZ\Insight\Test;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/InsightClient.php';
require_once __DIR__ . '/../src/Exception/BlockchainCallException.php';
require_once __DIR__ . '/../src/Exception/InvalidArgumentException.php';

use BTCZ\Insight\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use BTCZ\Insight\InsightClient;
use BTCZ\Insight\Exception\BlockchainCallException;

final class InsightClientTest extends TestCase
{
    /**
     * Data provider for @see testSetExceptionOnNotOkResponseWithValidParameter
     * @return array
     */
    public function setExceptionOnNotOkResponseWithValidParameterDataProvider()
    {
        return [
            [true, InsightClient::class],
            [false, InsightClient::class],
        ];
    }

    /**
     * @param $setParameter
     * @param $expectedClass
     * @dataProvider setExceptionOnNotOkResponseWithValidParameterDataProvider
     */
    public function testSetExceptionOnNotOkResponseWithValidParameter($setParameter, $expectedClass)
    {
        $client = $this->createInsightClient();
        $return = $client->setThrowExceptionOnNotOkResponse($setParameter);

        $this->assertEquals($setParameter, $client->shouldThrowExceptionOnNotOkResponse());
        $this->assertInstanceOf($expectedClass, $return);
    }

    /**
     * @return array
     */
    public function sendGetResponseOkDataProvider()
    {
        return [
            ['/get', [], 200, '{"response": "ok"}', true],
            ['/get', ['key' => 'value'], 200, '{"response": "ok"}', false],
        ];
    }

    /**
     * @param $url
     * @param $query
     * @param $code
     * @param $jsonResponse
     * @param $exceptionOnNotOk
     * @dataProvider sendGetResponseOkDataProvider
     */
    public function testSendGetResponseOk($url, $query, $code, $jsonResponse, $exceptionOnNotOk)
    {
        $client = $this->createInsightClient($code, $jsonResponse, $exceptionOnNotOk);

        $response = $client->sendGet($url, $query);
        $this->baseArrayResponseAsserts($response, $jsonResponse);
    }

    public function sendGetResponseExceptionDataProvider()
    {
        return [
            ['/api', [], 400, '{"response": "ok"}'],
            ['/api', ['key' => 'value'], 500, '{"response": "ok"}'],
            ['/api', ['key' => 'value'], 300, '{"response": "ok"}'],
            ['/api', ['key' => 'value'], 102, '{"response": "ok"}'],
        ];
    }

    /**
     * @param $url
     * @param $query
     * @param $code
     * @param $jsonResponse
     * @dataProvider sendGetResponseExceptionDataProvider
     */
    public function testSendGetResponseException($url, $query, $code, $jsonResponse)
    {
        $client = $this->createInsightClient($code, $jsonResponse);

        $this->expectException(BlockchainCallException::class);
        $client->sendGet($url, $query);
    }

    public function testGetTransactionForMultipleAddressesInvalidArgument()
    {
        $client = $this->createInsightClient(200);

        $this->expectException(InvalidArgumentException::class);
        $client->getTransactionsForMultipleAddresses([]);
    }

    public function getTransactionForMultipleAddressesDataProvider()
    {
        return [
            [['a','b', 'c'], '0000-00-00 00:00:00', '0000-00-00 00:00:00', '{"response": "ok"}']
        ];
    }

    /**
     * @param $addresses
     * @param $from
     * @param $to
     * @param $jsonResponse
     * @dataProvider getTransactionForMultipleAddressesDataProvider
     */
    public function testGetTransactionForMultipleAddresses($addresses, $from, $to, $jsonResponse)
    {
        $client = $this->createInsightClient(200, $jsonResponse);

        $response = $client->getTransactionsForMultipleAddresses($addresses, $from, $to);
        $this->baseArrayResponseAsserts($response, $jsonResponse);
    }

    /**
     * @return array
     */
    public function basicJsonResponseDataProvider()
    {
        return [
            ['{"response":"ok"}']
        ];
    }

    /**
     * @param string $jsonResponse
     * @dataProvider basicJsonResponseDataProvider
     */
    public function testGetTransactionsByAddress($jsonResponse)
    {
        $client = $this->createInsightClient(200, $jsonResponse);

        $response = $client->getTransactionsByAddress('/a');
        $this->baseArrayResponseAsserts($response, $jsonResponse);
    }

    /**
     * @return array
     */
    public function getTransactionsSuccessDataProvider()
    {
        return [
            [InsightClient::ADDRESS_STRING, 'aaaa', $this->basicJsonResponseDataProvider()[0][0]],
            [InsightClient::BLOCK_STRING, 'aaaa', $this->basicJsonResponseDataProvider()[0][0]],
        ];
    }

    /**
     * @param $option
     * @param $argument
     * @param $jsonResponse
     * @dataProvider getTransactionsSuccessDataProvider
     */
    public function testGetTransactionsSuccess($option, $argument, $jsonResponse)
    {
        $client = $this->createInsightClient(200, $jsonResponse);
        $response = $client->getTransactions($option, $argument);
        $this->baseArrayResponseAsserts($response, $jsonResponse);
    }

    public function testGetAddressUnspentOutputsInvalidArgument()
    {
        $client = $this->createInsightClient(400);

        $this->expectException(InvalidArgumentException::class);
        $client->getAddressUnspentOutputs('0');
    }

    /**
     * @param $jsonResponse
     * @dataProvider basicJsonResponseDataProvider
     */
    public function testGetAddressUnspentOutputsSuccess($jsonResponse)
    {
        $client = $this->createInsightClient(200, $jsonResponse);

        $response = $client->getAddressUnspentOutputs('aaabbbccc');
        $this->baseArrayResponseAsserts($response, $jsonResponse);
    }

    public function testGetMultipleAddressesUnspentOutputsInvalidArgument()
    {
        $client = $this->createInsightClient();

        $this->expectException(InvalidArgumentException::class);
        $client->getAddressUnspentOutputs('0');
    }

    /**
     * @return array
     */
    public function getMultipleAddressesUnspentOutputsSuccessDataProvider()
    {
        return [
            [['a', 'b'], $this->basicJsonResponseDataProvider()[0][0]],
        ];
    }

    /**
     * @param $addresses
     * @param $jsonResponse
     * @dataProvider getMultipleAddressesUnspentOutputsSuccessDataProvider
     */
    public function testGetMultipleAddressesUnspentOutputsSuccess($addresses, $jsonResponse)
    {
        $client = $this->createInsightClient(200, $jsonResponse);

        $response = $client->getMultipleAddressesUnspentOutputs($addresses);
        $this->baseArrayResponseAsserts($response, $jsonResponse);
    }

    public function testGetTransactionsInvalidArgument()
    {
        $client = $this->createInsightClient();
        $this->expectException(InvalidArgumentException::class);
        $client->getTransactions('a', '');
    }

    /**
     * @param $jsonResponse
     * @dataProvider basicJsonResponseDataProvider
     */
    public function testGetTransactionsByBlock($jsonResponse)
    {
        $client = $this->createInsightClient(200, $jsonResponse);
        $response = $client->getTransactionsByBlock('aaa');
        $this->baseArrayResponseAsserts($response, $jsonResponse);
    }

    public function testGetAddressUnconfirmedBalanceInSatoshi()
    {
        $client = $this->createInsightClient(200, '1321');

        $response = $client->getAddressUnconfirmedBalanceInSatoshi('dsadas');
        $this->assertEquals('1321', $response);
    }

    public function testGetAddressTotalSentInSatoshi()
    {
        $client = $this->createInsightClient(200, '1321');

        $response = $client->getAddressTotalSentInSatoshi('dsadas');
        $this->assertEquals('1321', $response);
    }

    public function testGetAddressTotalReceivedInSatoshi()
    {
        $client = $this->createInsightClient(200, '1321');

        $response = $client->getAddressTotalReceivedInSatoshi('dsadas');
        $this->assertEquals('1321', $response);
    }

    public function testGetAddressBalanceInSatoshi()
    {
        $client = $this->createInsightClient(200, '1321');

        $response = $client->getAddressBalanceInSatoshi('dsadas');
        $this->assertEquals('1321', $response);
    }

    public function testGetAddressPropertySuccess()
    {
        $client = $this->createInsightClient(200, '3333');
        $result = $client->getAddressProperty('aa', 'aa');
        $this->assertEquals('3333', $result);
    }

    public function testGetAddressPropertyInvalidArgument()
    {
        $client = $this->createInsightClient();
        $this->expectException(InvalidArgumentException::class);
        $client->getAddressProperty('0', 'aa');
    }

    public function getAddressSuccessDataProvider()
    {
        return [
            [$this->basicJsonResponseDataProvider()[0][0], 'aaa', false, 0, 1000],
            [$this->basicJsonResponseDataProvider()[0][0], 'aaa', true, 321, 1000],
        ];
    }

    /**
     * @param $jsonResponse
     * @param $address
     * @param $noTxList
     * @param $from
     * @param $to
     * @dataProvider getAddressSuccessDataProvider
     */
    public function testGetAddressSuccess($jsonResponse, $address, $noTxList, $from, $to)
    {
        $client = $this->createInsightClient(200, $jsonResponse);
        $response = $client->getAddress($address, $noTxList, $from, $to);
        $this->baseArrayResponseAsserts($response, $jsonResponse);
    }

    public function testGetAddressInvalidArgument()
    {
        $client = $this->createInsightClient();
        $this->expectException(InvalidArgumentException::class);
        $client->getAddress('0');
    }

    /**
     * @param $jsonResponse
     * @dataProvider basicJsonResponseDataProvider
     */
    public function testGetRawTransactionSuccess($jsonResponse)
    {
        $client = $this->createInsightClient(200, $jsonResponse);
        $response = $client->getRawTransaction('transaction-id');
        $this->baseArrayResponseAsserts($response , $jsonResponse);
    }

    public function testGetRawTransactionInvalidArgument()
    {
        $client = $this->createInsightClient();
        $this->expectException(InvalidArgumentException::class);
        $client->getRawTransaction('0');
    }

    /**
     * @param $jsonResponse
     * @dataProvider basicJsonResponseDataProvider
     */
    public function testGetTransactionSuccess($jsonResponse)
    {
        $client = $this->createInsightClient(200, $jsonResponse);
        $response = $client->getTransaction('transaction-id');
        $this->baseArrayResponseAsserts($response , $jsonResponse);
    }

    public function testGetTransactionInvalidArgument()
    {
        $client = $this->createInsightClient();
        $this->expectException(InvalidArgumentException::class);
        $client->getTransaction('0');
    }

    public function testGetRawBlockByHeightSuccess()
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"blockHash": "111"}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"rawblock": "1234"}'),
        ]);

        $client = new InsightClient(
            'http://localhost:3001/api/',
            HandlerStack::create($mock)
        );


        $response = $client->getRawBlockByHeight(0);
        $this->assertNotEmpty($response);
        $this->assertTrue(is_string($response));
    }

    public function testGetRawBlockByHeightInvalidArgument()
    {
        $client = $this->createInsightClient();
        $this->expectException(InvalidArgumentException::class);
        $client->getRawBlockByHeight(-1);
    }

    public function testGetRawBlockSuccess()
    {
        $client = $this->createInsightClient(200, '{"rawblock": "1asdwwead"}');
        $response = $client->getRawBlock('aaa');
        $this->assertEquals('1asdwwead', $response);
    }

    public function testGetRawBlockInvalidArgument()
    {
        $client = $this->createInsightClient();
        $this->expectException(InvalidArgumentException::class);
        $client->getRawBlock('0');
    }

    public function testGetCurrency()
    {
        $client = $this->createInsightClient(200, '{"data": {"bitstamp": "123"}}');
        $result = $client->getCurrency();
        $this->assertEquals("123", $result);
    }

    public function getBlockSummariesSuccessDataProvider()
    {
        return [
            ['2017-09-30 00:00:00', 10, $this->basicJsonResponseDataProvider()[0][0]],
            [null, 1, $this->basicJsonResponseDataProvider()[0][0]],
        ];
    }

    /**
     * @param $date
     * @param $limit
     * @param $jsonResponse
     * @dataProvider  getBlockSummariesSuccessDataProvider
     */
    public function testGetBlockSummariesSuccess($date, $limit, $jsonResponse)
    {
        $client = $this->createInsightClient(200, $jsonResponse);
        $response = $client->getBlockSummaries($date, $limit);
        $this->baseArrayResponseAsserts($response, $jsonResponse);
    }

    /**
     * @param $jsonResponse
     * @dataProvider basicJsonResponseDataProvider
     */
    public function testGetBlockByHeightSuccess($jsonResponse)
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"blockHash": "111"}'),
            new Response(200, ['Content-Type' => 'application/json'], $jsonResponse),
        ]);

        $client = new InsightClient(
            'http://localhost:3001/api/',
            HandlerStack::create($mock)
        );

        $response = $client->getBlockByHeight(1);
        $this->baseArrayResponseAsserts($response, $jsonResponse);
    }

    public function testGetBlockByHeightInvalidArgument()
    {
        $client = $this->createInsightClient();
        $this->expectException(InvalidArgumentException::class);
        $client->getBlockByHeight(-1);
    }

    /**
     * @param $jsonResponse
     * @dataProvider basicJsonResponseDataProvider
     */
    public function testGetBlockSuccess($jsonResponse)
    {
        $client = $this->createInsightClient(200, $jsonResponse);
        $response = $client->getBlock('aa');
        $this->baseArrayResponseAsserts($response, $jsonResponse);
    }

    public function testGetBlockInvalidArgument()
    {
        $client = $this->createInsightClient();
        $this->expectException(InvalidArgumentException::class);
        $client->getBlock('0');
    }


    /**
     * @param int $code
     * @param null $jsonResponse
     * @param bool $exceptionOnNotOkResponse
     * @return InsightClient
     */
    private function createInsightClient($code = 200, $jsonResponse = null, $exceptionOnNotOkResponse = true)
    {
        return new InsightClient(
            'http://localhost:3001/api/',
            $this->createMockHandler($code, $jsonResponse),
            $exceptionOnNotOkResponse
        );
    }

    /**
     * @param $response
     * @param $expectedJsonResponse
     */
    private function baseArrayResponseAsserts($response, $expectedJsonResponse)
    {
        $this->assertNotEmpty($response);
        $this->assertTrue(is_array($response));
        $this->assertEquals(json_decode($expectedJsonResponse, true), $response);
    }

    /**
     * @param $code
     * @param $jsonData
     * @return HandlerStack
     */
    private function createMockHandler($code, $jsonData = null)
    {
        $mock = new MockHandler([
            new Response($code, ['Content-Type' => 'application/json'], $jsonData)
        ]);

        return HandlerStack::create($mock);
    }
}
