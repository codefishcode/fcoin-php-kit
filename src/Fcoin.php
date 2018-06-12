<?php

/**
 * codefishcode/fcoin-php-kit
 * PHP implement for Fcoin v2 API (https://developer.fcoin.com/en.htm)
 * @version   1.0
 * @link      https://github.com/codefishcode/fcoin-php-kit
 * @copyright Copyright (c) 2018 codefishcode
 * @license   https://github.com/codefishcode/fcoin-php-kit/blob/master/LICENSE (MIT License)
 * 
 */

namespace codefishcode;

use GuzzleHttp\Client as Client;

require __DIR__ .'/config.php';

class Fcoin
{
    private static function client()
    {
        return new Client(['base_uri' => CONFIG['API_URI'][CONFIG['MODE']]]);
    }

    //private static function getSignature($api, $time, $data = [])
    private static function getSignature($method, $url, $time, $data = [])
    {
        $uri = CONFIG['API_URI'][CONFIG['MODE']] . $url;
        $body = "";
        if(!empty($data))
        {
            //ksort($data);
            $body = http_build_query($data);
        }

        $signature = "";

        $signature .= $method . $uri ;
        
        if(strcmp('GET',$method) == 0)
        {
            if($body != ""){
                $signature .= '?' . $body .$time;
            }else{
                $signature .= $time;
            }
        }
        else
        {
            $signature .= $time . $body;
        }


        //echo "signature: " . $signature . PHP_EOL;

        $signature = base64_encode($signature);

        $signature = hash_hmac('sha1', $signature, CONFIG['API_SECRET'], true);

        $signature = base64_encode($signature);

        return $signature;
    }



    private static function genQueryString($data){
        if(empty($data))
        {
            return "";
        }
        ksort($data);
        $query = http_build_query($data);
        return '?' . $query;
    }

    

    private static function getHeaders($key, $signature, $time, $NOT_POST = TRUE)
    {
        if($NOT_POST)
        {
            return [ 
                'FC-ACCESS-KEY' => $key,
                'FC-ACCESS-SIGNATURE' => $signature,
                'FC-ACCESS-TIMESTAMP' => $time
            ];
        }

        return [ 
            'FC-ACCESS-KEY' => $key,
            'FC-ACCESS-SIGNATURE' => $signature,
            'FC-ACCESS-TIMESTAMP' => $time,
            'Content-Type' => 'application/json;charset=UTF-8'
        ];

    }

    //get timestamp with microseconds
    private static function getLocalTime(){
        $time = (string) (microtime(true) * 1000);
        $time = round($time);   //remove decimal point
        return $time;
    }

    /**
    *   Get server time
    *   GET https://api.fcoin.com/v2/public/server-time
    */
    public static function getServerTime()
    {
        $response = self::client()->request('GET', 'public/server-time');
        return $response->getBody()->getContents();
    }


    /**
    *   Get support symbols
    *   GET https://api.fcoin.com/v2/public/symbols
    */
    public static function getSymbols()
    {
        $response = self::client()->request('GET', 'public/symbols');
        return $response->getBody()->getContents();
    }


    /**
    *   Get support currencties
    *   GET https://api.fcoin.com/v2/public/currencies
    */
    public static function getCurrencies()
    {
        $response = self::client()->request('GET', 'public/currencies');
        return $response->getBody()->getContents();
    }


    /**
    *   Get Account balance
    *   GET https://api.fcoin.com/v2/accounts/balance
    */
    public static function getBalance()
    {
        $time = self::getLocalTime();

        $signature = self::getSignature('GET', 'accounts/balance', $time);
        $headers = self::getHeaders(CONFIG['API_KEY'], $signature ,$time);

        $response = self::client()->request('GET', 'accounts/balance',[
            'headers' => $headers
        ]);
        return $response->getBody()->getContents();

    }


    /**
    *   Create order
    *   POST https://api.fcoin.com/v2/orders
    *
    *   @param Array $order, 
    *    'symbol' order symbol  (required)
    *    'side' 'buy' or 'sell'  (required)
    *    'type' 'limit' or 'market' (required)
    *    'price' price of the order (required)
    *    'amount' amount of the order (required)
    *
    *   @return json result
    *    {
    *    "status":0,
    *    "data":"9d17a03b852e48c0b3920c7412867623"
    *    }
    *
    *   @example createOrders(['symbol' => 'btcustd', 'side' => 'buy', 'type' => 'limit', 'price' => '300', 'amount' => '0.01'])
    */
    public static function createOrders($order)
    {
        $time = self::getLocalTime();
        ksort($order);
        $signature = self::getSignature('POST', 'orders', $time, $order);
        $headers = self::getHeaders(CONFIG['API_KEY'], $signature ,$time, FALSE);
        $url_query = self::genQueryString($order);

        $response = self::client()->request('POST', 'orders',[
            'headers' => $headers,
            'json' => $order
        ]);

        return $response->getBody()->getContents();

    }


    /**
    *   Get order list
    *   GET https://api.fcoin.com/v2/orders
    *
    *   @param Array $criteria, 
    *    'symbol' order symbol  (required)
    *    'states' order states  (required), options: submitted, partial_filled, partial_canceled, filled, canceled, 
    *                    can pick more than one, e.g. 'filled,canceled'
    *    'before' the orders before page#
    *    'after' the orders after page#
    *    'limit' order per page, default is 20
    *
    *   @return json result
    *    {
    *        "status":0,
    *        "data":[
    *           {
    *               "id":"string",
    *               "symbol":"string",
    *               "type":"limit",
    *               "side":"buy",
    *               "price":"string",
    *               "amount":"string",
    *               "state":"submitted",
    *               "executed_value":"string",
    *               "fill_fees":"string",
    *               "filled_amount":"string",
    *               "created_at":0,
    *               "source":"web"
    *           }
    *       ]
    *   }
    *
    *   @example    getOrderslist(['symbol' => 'etcusdt', 'states' => 'filled'])
    */
    public static function getOrderslist($criteria)
    {
        $time = self::getLocalTime();
        ksort($criteria);
        $signature = self::getSignature('GET', 'orders', $time, $criteria);
        $headers = self::getHeaders(CONFIG['API_KEY'], $signature ,$time);
        $url_query = self::genQueryString($criteria);

        $response = self::client()->request('GET', 'orders' . $url_query,[
            'headers' => $headers
        ]);

        return $response->getBody()->getContents();

    }


    /**
    *   Get orders
    *   GET https://api.fcoin.com/v2/orders
    *
    *   @param Array $criteria, 
    *    'order_id' specific order_id
    *   
    *   @return json result
    *    {
    *       "status":0,
    *       "data":{
    *            "id":"9d17a03b852e48c0b3920c7412867623",
    *           "symbol":"string",
    *            "type":"limit",
    *            "side":"buy",
    *           "price":"string",
    *            "amount":"string",
    *            "state":"submitted",
    *            "executed_value":"string",
    *           "fill_fees":"string",
    *           "filled_amount":"string",
    *           "created_at":0,
    *           "source":"web"
    *       }
    *    }
    *
    *   @example    getOrder(['order_id' => 'w3EJQPkRs-UhP0STjUlnm-atrGafdW3qtdVkNWohs3g='])
    */
    public static function getOrder($criteria)
    {
        $time = self::getLocalTime();
        $signature = self::getSignature('GET', 'orders/'.$criteria['order_id'], $time);
        $headers = self::getHeaders(CONFIG['API_KEY'], $signature ,$time);

        $url_query = self::genQueryString($criteria);

        $response = self::client()->request('GET', 'orders/'.$criteria['order_id'] ,[
            'headers' => $headers
        ]);

        return $response->getBody()->getContents();

    }




    /**
    *   Cancel order
    *   POST https://api.fcoin.com/v2/orders/{order_id}/submit-cancel
    *
    *   @param Array $criteria, 
    *    'order_id' specific order_id
    *
    *   @return json result
    *    {
    *    "status": 0,
    *    "msg": "string",
    *    "data": true
    *    }
    *
    *   @example createOrders(['order_id' => 'VPWyi9POZsJ-7G6GQt8dvDCvMI00HBg0H5-ms0JBTkg='])
    */
    public static function cancelOrder($criteria)
    {

        $time = self::getLocalTime();
        ksort($criteria);
        $signature = self::getSignature('POST', 'orders/' . $criteria['order_id'] . '/submit-cancel', $time);
        $headers = self::getHeaders(CONFIG['API_KEY'], $signature ,$time, FALSE);
        $url_query = self::genQueryString($criteria);

        $response = self::client()->request('POST', 'orders/' . $criteria['order_id'] . '/submit-cancel',[
            'headers' => $headers
        ]);

        return $response->getBody()->getContents();

    }




    /**
    *   Get order transaction
    *   GET https://api.fcoin.com/v2/orders/{order_id}/match-results
    *
    *   @param Array $criteria, 
    *    'order_id' specific order_id
    *   
    *   @return json result
    *    {
    *       "status": 0,
    *       "data": [
    *        {
    *           "price": "string",
    *            "fill_fees": "string",
    *            "filled_amount": "string",
    *            "side": "buy",
    *            "type": "limit",
    *            "created_at": 0
    *        }
    *        ]
    *    }
    *
    *   @example    getOrderTransaction(['order_id' => 'w3EJQPkRs-UhP0STjUlnm-atrGafdW3qtdVkNWohs3g='])
    */
    public static function getOrderTransaction($criteria)
    {
        $time = self::getLocalTime();
        $signature = self::getSignature('GET', 'orders/'.$criteria['order_id'] . '/match-results', $time);
        $headers = self::getHeaders(CONFIG['API_KEY'], $signature ,$time);

        $url_query = self::genQueryString($criteria);

        $response = self::client()->request('GET', 'orders/'.$criteria['order_id'] . '/match-results' ,[
            'headers' => $headers
        ]);

        return $response->getBody()->getContents();

    }


    /**
    *   Get tick data of a symbol
    *   GET https://api.fcoin.com/v2/market/ticker/$symbol
    *
    *   @param Array $criteria, 
    *    'symbol' order symbol  (required)
    *
    *   @return json result
    *    {
    *       "status": 0,
    *       "data": {
    *           "type": "ticker.btcusdt",
    *           "seq": 680035,
    *           "ticker": [
    *               7140.890000000000000000,       //latest price
    *               1.000000000000000000,          //latest amount
    *               7131.330000000,                //highest bid price
    *               233.524600000,                 //highest bid volume 
    *               7140.890000000,                //lowest ask price
    *               225.495049866,                 //lowset ask volumn
    *               7140.890000000,                //last 24hours close price
    *               7140.890000000,                //last 24hours highest price
    *               7140.890000000,                //last 24hours lowest price
    *               1.000000000,                   //last 24hours buy side volume
    *               7140.890000000000000000        //last 24hours sell side volume
    *           ]
    *       }
    *    }
    *
    *   @example getTickData(['symbol' => 'btcusdt'])
    */

    public static function getTickData($criteria)
    {
        $response = self::client()->request('GET', 'market/ticker/' . $criteria['symbol']);
        return $response->getBody()->getContents();
    }




    /**
    *   Get market depth status
    *   GET https://api.fcoin.com/v2/market/depth/$level/$symbol
    *
    *   @param Array $criteria, 
    *    'level' data level, oprions: [L20, L100, full] (required)
    *    'symbol' order symbol  (required)
    *
    *   @return json result
    *   {
    *    "type": "topics",
    *    "topics": ["depth.L20.ethbtc", "depth.L100.btcusdt"]
    *   }
    *
    *   @example getMarketDepthStatus(['symbol' => 'btcustd', 'level' => 'L20'])
    */

    public static function getMarketDepthStatus($criteria)
    {
        $response = self::client()->request('GET', 'market/depth/' . $criteria['level'] . '/' . $criteria['symbol']);
        return $response->getBody()->getContents();
    }


    /**
    *   Get Market committed transactions
    *   GET https://api.fcoin.com/v2/market/trades/$symbol
    *
    *   @param Array $criteria, 
    *    'symbol' order symbol  (required)
    *    'before_id' the transaction before that id
    *    'limit' limited transaction return
    *
    *   @return json result
    *    {"id":null,
    *    "ts":1523693400329,
    *    "data":[
    *       {
    *            "amount":1.000000000,
    *            "ts":1523419946174,
    *            "id":76000,
    *            "side":"sell",
    *            "price":4.000000000
    *       },
    *       {
    *            "amount":1.000000000,
    *            "ts":1523419114272,
    *            "id":74000,
    *            "side":"sell",
    *            "price":4.000000000
    *       },
    *       {
    *            "amount":1.000000000,
    *            "ts":1523415182356,
    *            "id":71000,
    *            "side":"sell",
    *            "price":3.000000000
    *       }
    *      ]
    *    }
    *
    *   @example getMarketTransaction(['symbol' => 'btcustd', 'before_id' => '25688775000', 'limit' => 20])
    */

    public static function getMarketTransaction($criteria)
    {
        $symbol = $criteria['symbol'];
        unset($criteria['symbol']);
        ksort($criteria);
        $url_query = self::genQueryString($criteria);   
        $response = self::client()->request('GET', 'market/trades/' . $symbol . $url_query);
        return $response->getBody()->getContents();
    }
    
}