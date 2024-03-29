<?php

namespace ccxt;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

use Exception as Exception; // a common import

class dsx extends liqui {

    public function describe () {
        return array_replace_recursive (parent::describe (), array (
            'id' => 'dsx',
            'name' => 'DSX',
            'countries' => array ( 'UK' ),
            'rateLimit' => 1500,
            'version' => 'v2',
            'has' => array (
                'CORS' => false,
                'fetchOrder' => true,
                'fetchOrders' => true,
                'fetchOpenOrders' => true,
                'fetchClosedOrders' => false,
                'fetchOrderBooks' => false,
                'createDepositAddress' => true,
                'fetchDepositAddress' => true,
                'fetchTransactions' => true,
            ),
            'urls' => array (
                'logo' => 'https://user-images.githubusercontent.com/1294454/27990275-1413158a-645a-11e7-931c-94717f7510e3.jpg',
                'api' => array (
                    'public' => 'https://dsx.uk/mapi', // market data
                    'private' => 'https://dsx.uk/tapi', // trading
                    'dwapi' => 'https://dsx.uk/dwapi', // deposit/withdraw
                ),
                'www' => 'https://dsx.uk',
                'doc' => array (
                    'https://api.dsx.uk',
                    'https://dsx.uk/api_docs/public',
                    'https://dsx.uk/api_docs/private',
                    '',
                ),
            ),
            'fees' => array (
                'trading' => array (
                    'tierBased' => true,
                    'percentage' => true,
                    'maker' => 0.15 / 100,
                    'taker' => 0.25 / 100,
                ),
            ),
            'api' => array (
                // market data (public)
                'public' => array (
                    'get' => array (
                        'barsFromMoment/{id}/{period}/{start}', // empty reply :\
                        'depth/{pair}',
                        'info',
                        'lastBars/{id}/{period}/{amount}', // period is (m, h or d)
                        'periodBars/{id}/{period}/{start}/{end}',
                        'ticker/{pair}',
                        'trades/{pair}',
                    ),
                ),
                // trading (private)
                'private' => array (
                    'post' => array (
                        'info/account',
                        'history/transactions',
                        'history/trades',
                        'history/orders',
                        'orders',
                        'order/cancel',
                        'order/cancel/all',
                        'order/status',
                        'order/new',
                        'volume',
                        'fees', // trading fee schedule
                    ),
                ),
                // deposit / withdraw (private)
                'dwapi' => array (
                    'post' => array (
                        'deposit/cryptoaddress',
                        'withdraw/crypto',
                        'withdraw/fiat',
                        'withdraw/submit',
                        'withdraw/cancel',
                        'transaction/status', // see 'history/transactions' in private tapi above
                    ),
                ),
            ),
            'exceptions' => array (
                'exact' => array (
                    "Order wasn't cancelled" => '\\ccxt\\InvalidOrder', // non-existent order
                ),
            ),
            'options' => array (
                'fetchOrderMethod' => 'privatePostOrderStatus',
                'fetchMyTradesMethod' => 'privatePostHistoryTrades',
                'cancelOrderMethod' => 'privatePostOrderCancel',
                'fetchTickersMaxLength' => 250,
            ),
        ));
    }

    public function fetch_transactions ($code = null, $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $currency = null;
        $request = array ();
        if ($code !== null) {
            $currency = $this->currency ($code);
            $request['currency'] = $currency['id'];
        }
        if ($since !== null) {
            $request['since'] = $since;
        }
        if ($limit !== null) {
            $request['count'] = $limit;
        }
        $response = $this->privatePostHistoryTransactions (array_merge ($request, $params));
        //
        //     {
        //         "success" => 1,
        //         "return" => array (
        //             {
        //                 "id" => 1,
        //                 "timestamp" => 11,
        //                 "type" => "Withdraw",
        //                 "amount" => 1,
        //                 "$currency" => "btc",
        //                 "confirmationsCount" => 6,
        //                 "address" => "address",
        //                 "status" => 2,
        //                 "commission" => 0.0001
        //             }
        //         )
        //     }
        //
        $transactions = $this->safe_value($response, 'return', array ());
        return $this->parseTransactions ($transactions, $currency, $since, $limit);
    }

    public function parse_transaction_status ($status) {
        $statuses = array (
            '1' => 'failed',
            '2' => 'ok',
            '3' => 'pending',
            '4' => 'failed',
        );
        return $this->safe_string($statuses, $status, $status);
    }

    public function parse_transaction ($transaction, $currency = null) {
        //
        //     {
        //         "id" => 1,
        //         "$timestamp" => 11, // 11 in their docs (
        //         "$type" => "Withdraw",
        //         "amount" => 1,
        //         "$currency" => "btc",
        //         "confirmationsCount" => 6,
        //         "address" => "address",
        //         "$status" => 2,
        //         "commission" => 0.0001
        //     }
        //
        $timestamp = $this->safe_integer($transaction, 'timestamp');
        if ($timestamp !== null) {
            $timestamp = $timestamp * 1000;
        }
        $type = $this->safe_string($transaction, 'type');
        if ($type !== null) {
            if ($type === 'Incoming') {
                $type = 'deposit';
            } else if ($type === 'Withdraw') {
                $type = 'withdrawal';
            }
        }
        $currencyId = $this->safe_string($transaction, 'currency');
        $code = null;
        if (is_array ($this->currencies_by_id) && array_key_exists ($currencyId, $this->currencies_by_id)) {
            $ccy = $this->currencies_by_id[$currencyId];
            $code = $ccy['code'];
        } else {
            $code = $this->common_currency_code($currencyId);
        }
        $status = $this->parse_transaction_status ($this->safe_string($transaction, 'status'));
        return array (
            'id' => $this->safe_string($transaction, 'id'),
            'txid' => $this->safe_string($transaction, 'txid'),
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'address' => $this->safe_string($transaction, 'address'),
            'type' => $type,
            'amount' => $this->safe_float($transaction, 'amount'),
            'currency' => $code,
            'status' => $status,
            'fee' => array (
                'currency' => $code,
                'cost' => $this->safe_float($transaction, 'commission'),
                'rate' => null,
            ),
            'info' => $transaction,
        );
    }

    public function fetch_markets ($params = array ()) {
        $response = $this->publicGetInfo ();
        $markets = $response['pairs'];
        $keys = is_array ($markets) ? array_keys ($markets) : array ();
        $result = array ();
        for ($i = 0; $i < count ($keys); $i++) {
            $id = $keys[$i];
            $market = $markets[$id];
            $baseId = $this->safe_string($market, 'base_currency');
            $quoteId = $this->safe_string($market, 'quoted_currency');
            $base = $this->common_currency_code($baseId);
            $quote = $this->common_currency_code($quoteId);
            $symbol = $base . '/' . $quote;
            $precision = array (
                'amount' => $this->safe_integer($market, 'decimal_places'),
                'price' => $this->safe_integer($market, 'decimal_places'),
            );
            $amountLimits = array (
                'min' => $this->safe_float($market, 'min_amount'),
                'max' => $this->safe_float($market, 'max_amount'),
            );
            $priceLimits = array (
                'min' => $this->safe_float($market, 'min_price'),
                'max' => $this->safe_float($market, 'max_price'),
            );
            $costLimits = array (
                'min' => $this->safe_float($market, 'min_total'),
            );
            $limits = array (
                'amount' => $amountLimits,
                'price' => $priceLimits,
                'cost' => $costLimits,
            );
            $hidden = $this->safe_integer($market, 'hidden');
            $active = ($hidden === 0);
            $result[] = array (
                'id' => $id,
                'symbol' => $symbol,
                'base' => $base,
                'quote' => $quote,
                'baseId' => $baseId,
                'quoteId' => $quoteId,
                'active' => $active,
                'precision' => $precision,
                'limits' => $limits,
                'info' => $market,
            );
        }
        return $result;
    }

    public function fetch_balance ($params = array ()) {
        $this->load_markets();
        $response = $this->privatePostInfoAccount ();
        //
        //     {
        //       "success" : 1,
        //       "return" : {
        //         "$funds" : {
        //           "BTC" : array (
        //             "total" : 0,
        //             "available" : 0
        //           ),
        //           "USD" : array (
        //             "total" : 0,
        //             "available" : 0
        //           ),
        //           "USDT" : array (
        //             "total" : 0,
        //             "available" : 0
        //           }
        //         ),
        //         "rights" : array (
        //           "info" : 1,
        //           "trade" : 1
        //         ),
        //         "transactionCount" : 0,
        //         "openOrders" : 0,
        //         "serverTime" : 1537451465
        //       }
        //     }
        //
        $balances = $response['return'];
        $result = array ( 'info' => $balances );
        $funds = $balances['funds'];
        $ids = is_array ($funds) ? array_keys ($funds) : array ();
        for ($c = 0; $c < count ($ids); $c++) {
            $id = $ids[$c];
            $code = $this->common_currency_code($id);
            $account = array (
                'free' => $funds[$id]['available'],
                'used' => 0.0,
                'total' => $funds[$id]['total'],
            );
            $account['used'] = $account['total'] - $account['free'];
            $result[$code] = $account;
        }
        return $this->parse_balance($result);
    }

    public function create_deposit_address ($code, $params = array ()) {
        $request = array (
            'new' => 1,
        );
        $response = $this->fetch_deposit_address ($code, array_merge ($request, $params));
        return $response;
    }

    public function fetch_deposit_address ($code, $params = array ()) {
        $this->load_markets();
        $currency = $this->currency ($code);
        $request = array (
            'currency' => $currency['id'],
        );
        $response = $this->dwapiPostDepositCryptoaddress (array_merge ($request, $params));
        $result = $this->safe_value($response, 'return', array ());
        $address = $this->safe_string($result, 'address');
        $this->check_address($address);
        return array (
            'currency' => $code,
            'address' => $address,
            'tag' => null, // not documented in DSX API
            'info' => $response,
        );
    }

    public function parse_ticker ($ticker, $market = null) {
        //
        //   {    high =>  0.03492,
        //         low =>  0.03245,
        //         avg =>  29.46133,
        //         vol =>  500.8661,
        //     vol_cur =>  17.000797104,
        //        $last =>  0.03364,
        //         buy =>  0.03362,
        //        sell =>  0.03381,
        //     updated =>  1537521993,
        //        pair => "ethbtc"       }
        //
        $timestamp = $ticker['updated'] * 1000;
        $symbol = null;
        // dsx has 'pair' in the $ticker, liqui does not have it
        $marketId = $this->safe_string($ticker, 'pair');
        $market = $this->safe_value($this->markets_by_id, $marketId, $market);
        if ($market !== null) {
            $symbol = $market['symbol'];
        }
        // dsx $average is inverted, liqui $average is not
        $average = $this->safe_float($ticker, 'avg');
        if ($average !== null) {
            if ($average > 0) {
                $average = 1 / $average;
            }
        }
        $last = $this->safe_float($ticker, 'last');
        return array (
            'symbol' => $symbol,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'high' => $this->safe_float($ticker, 'high'),
            'low' => $this->safe_float($ticker, 'low'),
            'bid' => $this->safe_float($ticker, 'buy'),
            'bidVolume' => null,
            'ask' => $this->safe_float($ticker, 'sell'),
            'askVolume' => null,
            'vwap' => null,
            'open' => null,
            'close' => $last,
            'last' => $last,
            'previousClose' => null,
            'change' => null,
            'percentage' => null,
            'average' => $average,
            'baseVolume' => $this->safe_float($ticker, 'vol'), // dsx shows baseVolume in 'vol', liqui shows baseVolume in 'vol_cur'
            'quoteVolume' => $this->safe_float($ticker, 'vol_cur'), // dsx shows baseVolume in 'vol_cur', liqui shows baseVolume in 'vol'
            'info' => $ticker,
        );
    }

    public function sign_body_with_secret ($body) {
        return $this->decode ($this->hmac ($this->encode ($body), $this->encode ($this->secret), 'sha512', 'base64'));
    }

    public function get_version_string () {
        return '';
    }

    public function get_private_path ($path, $params) {
        return '/' . $this->version . '/' . $this->implode_params($path, $params);
    }

    public function get_order_id_key () {
        return 'orderId';
    }

    public function create_order ($symbol, $type, $side, $amount, $price = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        if ($type === 'market' && $price === null) {
            throw new ArgumentsRequired ($this->id . ' createOrder requires a $price argument even for $market orders, that is the worst $price that you agree to fill your order for');
        }
        $request = array (
            'pair' => $market['id'],
            'type' => $side,
            'volume' => $this->amount_to_precision($symbol, $amount),
            'rate' => $this->price_to_precision($symbol, $price),
            'orderType' => $type,
        );
        $price = floatval ($price);
        $amount = floatval ($amount);
        $response = $this->privatePostOrderNew (array_merge ($request, $params));
        //
        //     {
        //       "success" => 1,
        //       "return" => {
        //         "received" => 0,
        //         "remains" => 10,
        //         "funds" => {
        //           "BTC" => array (
        //             "total" => 100,
        //             "available" => 95
        //           ),
        //           "USD" => array (
        //             "total" => 10000,
        //             "available" => 9995
        //           ),
        //           "EUR" => array (
        //             "total" => 1000,
        //             "available" => 995
        //           ),
        //           "LTC" => array (
        //             "total" => 1000,
        //             "available" => 995
        //           }
        //         ),
        //         "orderId" => 0, // https://github.com/ccxt/ccxt/issues/3677
        //       }
        //     }
        //
        $status = 'open';
        $filled = 0.0;
        $remaining = $amount;
        $responseReturn = $this->safe_value($response, 'return');
        $id = $this->safe_string_2($responseReturn, 'orderId', 'order_id');
        if ($id === '0') {
            $id = $this->safe_string($responseReturn, 'initOrderId', 'init_order_id');
            $status = 'closed';
        }
        $filled = $this->safe_float($responseReturn, 'received', 0.0);
        $remaining = $this->safe_float($responseReturn, 'remains', $amount);
        $timestamp = $this->milliseconds ();
        return array (
            'info' => $response,
            'id' => $id,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'lastTradeTimestamp' => null,
            'status' => $status,
            'symbol' => $symbol,
            'type' => $type,
            'side' => $side,
            'price' => $price,
            'cost' => $price * $filled,
            'amount' => $amount,
            'remaining' => $remaining,
            'filled' => $filled,
            'fee' => null,
            // 'trades' => $this->parse_trades(order['trades'], $market),
        );
    }

    public function parse_order_status ($status) {
        $statuses = array (
            '0' => 'open', // Active
            '1' => 'closed', // Filled
            '2' => 'canceled', // Killed
            '3' => 'canceling', // Killing
            '7' => 'canceled', // Rejected
        );
        return $this->safe_string($statuses, $status, $status);
    }

    public function parse_trade ($trade, $market = null) {
        //
        // fetchTrades (public)
        //
        //     {
        //         "$amount" : 0.0128,
        //         "$price" : 6483.99000,
        //         "$timestamp" : 1540334614,
        //         "tid" : 35684364,
        //         "$type" : "ask"
        //     }
        //
        // fetchMyTrades (private)
        //
        //     {
        //         "number" => "36635882", // <-- this is present if the $trade has come from the '/order/status' call
        //         "$id" => "36635882", // <-- this may have been artifically added by the parseTrades method
        //         "pair" => "btcusd",
        //         "$type" => "buy",
        //         "volume" => 0.0595,
        //         "rate" => 9750,
        //         "$orderId" => 77149299,
        //         "$timestamp" => 1519612317,
        //         "commission" => 0.00020825,
        //         "commissionCurrency" => "btc"
        //     }
        //
        $timestamp = $this->safe_integer($trade, 'timestamp');
        if ($timestamp !== null) {
            $timestamp = $timestamp * 1000;
        }
        $side = $this->safe_string($trade, 'type');
        if ($side === 'ask') {
            $side = 'sell';
        } else if ($side === 'bid') {
            $side = 'buy';
        }
        $price = $this->safe_float_2($trade, 'rate', 'price');
        $id = $this->safe_string_2($trade, 'number', 'id');
        $orderId = $this->safe_string($trade, 'orderId');
        if (is_array ($trade) && array_key_exists ('pair', $trade)) {
            $marketId = $this->safe_string($trade, 'pair');
            $market = $this->safe_value($this->markets_by_id, $marketId, $market);
        }
        $symbol = null;
        if ($market !== null) {
            $symbol = $market['symbol'];
        }
        $amount = $this->safe_float_2($trade, 'amount', 'volume');
        $type = 'limit'; // all trades are still limit trades
        $takerOrMaker = null;
        $fee = null;
        $feeCost = $this->safe_float($trade, 'commission');
        if ($feeCost !== null) {
            $feeCurrencyId = $this->safe_string($trade, 'commissionCurrency');
            $feeCurrencyId = strtoupper ($feeCurrencyId);
            $feeCurrency = $this->safe_value($this->currencies_by_id, $feeCurrencyId);
            $feeCurrencyCode = null;
            if ($feeCurrency !== null) {
                $feeCurrencyCode = $feeCurrency['code'];
            } else {
                $feeCurrencyCode = $this->common_currency_code($feeCurrencyId);
            }
            $fee = array (
                'cost' => $feeCost,
                'currency' => $feeCurrencyCode,
            );
        }
        $isYourOrder = $this->safe_value($trade, 'is_your_order');
        if ($isYourOrder !== null) {
            $takerOrMaker = 'taker';
            if ($isYourOrder) {
                $takerOrMaker = 'maker';
            }
            if ($fee === null) {
                $fee = $this->calculate_fee($symbol, $type, $side, $amount, $price, $takerOrMaker);
            }
        }
        $cost = null;
        if ($price !== null) {
            if ($amount !== null) {
                $cost = $price * $amount;
            }
        }
        return array (
            'id' => $id,
            'order' => $orderId,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'symbol' => $symbol,
            'type' => $type,
            'side' => $side,
            'takerOrMaker' => $takerOrMaker,
            'price' => $price,
            'amount' => $amount,
            'cost' => $cost,
            'fee' => $fee,
            'info' => $trade,
        );
    }

    public function parse_order ($order, $market = null) {
        //
        // fetchOrder
        //
        //   {
        //     "number" => 36635882,
        //     "pair" => "btcusd",
        //     "type" => "buy",
        //     "remainingVolume" => 10,
        //     "volume" => 10,
        //     "rate" => 1000.0,
        //     "timestampCreated" => 1496670,
        //     "$status" => 0,
        //     "$orderType" => "limit",
        //     "$deals" => array (
        //       {
        //         "pair" => "btcusd",
        //         "type" => "buy",
        //         "$amount" => 1,
        //         "rate" => 1000.0,
        //         "orderId" => 1,
        //         "$timestamp" => 1496672724,
        //         "commission" => 0.001,
        //         "commissionCurrency" => "btc"
        //       }
        //     )
        //   }
        //
        $id = $this->safe_string($order, 'id');
        $status = $this->parse_order_status($this->safe_string($order, 'status'));
        $timestamp = $this->safe_integer($order, 'timestampCreated');
        if ($timestamp !== null) {
            $timestamp = $timestamp * 1000;
        }
        $marketId = $this->safe_string($order, 'pair');
        $market = $this->safe_value($this->markets_by_id, $marketId, $market);
        $symbol = null;
        if ($market !== null) {
            $symbol = $market['symbol'];
        }
        $remaining = $this->safe_float($order, 'remainingVolume');
        $amount = $this->safe_float($order, 'volume');
        $price = $this->safe_float($order, 'rate');
        $filled = null;
        $cost = null;
        if ($amount !== null) {
            if ($remaining !== null) {
                $filled = $amount - $remaining;
                $cost = $price * $filled;
            }
        }
        $orderType = $this->safe_string($order, 'orderType');
        $side = $this->safe_string($order, 'type');
        $fee = null;
        $deals = $this->safe_value($order, 'deals', array ());
        $numDeals = is_array ($deals) ? count ($deals) : 0;
        $trades = null;
        $lastTradeTimestamp = null;
        if ($numDeals > 0) {
            $trades = $this->parse_trades($deals);
            $feeCost = null;
            $feeCurrency = null;
            for ($i = 0; $i < count ($trades); $i++) {
                $trade = $trades[$i];
                if ($feeCost === null) {
                    $feeCost = 0;
                }
                $feeCost .= $trade['fee']['cost'];
                $feeCurrency = $trade['fee']['currency'];
                $lastTradeTimestamp = $trade['timestamp'];
            }
            if ($feeCost !== null) {
                $fee = array (
                    'cost' => $feeCost,
                    'currency' => $feeCurrency,
                );
            }
        }
        return array (
            'info' => $order,
            'id' => $id,
            'symbol' => $symbol,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'lastTradeTimestamp' => $lastTradeTimestamp,
            'type' => $orderType,
            'side' => $side,
            'price' => $price,
            'cost' => $cost,
            'amount' => $amount,
            'remaining' => $remaining,
            'filled' => $filled,
            'status' => $status,
            'fee' => $fee,
            'trades' => $trades,
        );
    }

    public function fetch_order ($id, $symbol = null, $params = array ()) {
        $this->load_markets();
        $request = array (
            'orderId' => intval ($id),
        );
        $response = $this->privatePostOrderStatus (array_merge ($request, $params));
        //
        //     {
        //       "success" => 1,
        //       "return" => {
        //         "pair" => "btcusd",
        //         "type" => "buy",
        //         "remainingVolume" => 10,
        //         "volume" => 10,
        //         "rate" => 1000.0,
        //         "timestampCreated" => 1496670,
        //         "status" => 0,
        //         "orderType" => "limit",
        //         "deals" => array (
        //           {
        //             "pair" => "btcusd",
        //             "type" => "buy",
        //             "amount" => 1,
        //             "rate" => 1000.0,
        //             "orderId" => 1,
        //             "timestamp" => 1496672724,
        //             "commission" => 0.001,
        //             "commissionCurrency" => "btc"
        //           }
        //         )
        //       }
        //     }
        //
        return $this->parse_order(array_merge (array (
            'id' => $id,
        ), $response['return']));
    }

    public function parse_orders_by_id ($orders, $symbol = null, $since = null, $limit = null) {
        $ids = is_array ($orders) ? array_keys ($orders) : array ();
        $result = array ();
        for ($i = 0; $i < count ($ids); $i++) {
            $id = $ids[$i];
            $order = $this->parse_order(array_merge (array (
                'id' => (string) $id,
            ), $orders[$id]));
            $result[] = $order;
        }
        return $this->filter_by_symbol_since_limit($result, $symbol, $since, $limit);
    }

    public function fetch_open_orders ($symbol = null, $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $request = array (
            // 'count' => 10, // Decimal, The maximum number of orders to return
            // 'fromId' => 123, // Decimal, ID of the first order of the selection
            // 'endId' => 321, // Decimal, ID of the last order of the selection
            // 'order' => 'ASC', // String, Order in which orders shown. Possible values are "ASC" — from first to last, "DESC" — from last to first.
        );
        $response = $this->privatePostOrders (array_merge ($request, $params));
        //
        //     {
        //       "success" => 1,
        //       "return" => {
        //         "0" => {
        //           "pair" => "btcusd",
        //           "type" => "buy",
        //           "remainingVolume" => 10,
        //           "volume" => 10,
        //           "rate" => 1000.0,
        //           "timestampCreated" => 1496670,
        //           "status" => 0,
        //           "orderType" => "$limit"
        //         }
        //       }
        //     }
        //
        return $this->parse_orders_by_id ($this->safe_value($response, 'return', array ()), $symbol, $since, $limit);
    }

    public function fetch_orders ($symbol = null, $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $request = array (
            // 'count' => 10, // Decimal, The maximum number of orders to return
            // 'fromId' => 123, // Decimal, ID of the first order of the selection
            // 'endId' => 321, // Decimal, ID of the last order of the selection
            // 'order' => 'ASC', // String, Order in which orders shown. Possible values are "ASC" — from first to last, "DESC" — from last to first.
        );
        $response = $this->privatePostHistoryOrders (array_merge ($request, $params));
        //
        //     {
        //       "success" => 1,
        //       "return" => {
        //         "0" => {
        //           "pair" => "btcusd",
        //           "type" => "buy",
        //           "remainingVolume" => 10,
        //           "volume" => 10,
        //           "rate" => 1000.0,
        //           "timestampCreated" => 1496670,
        //           "status" => 0,
        //           "orderType" => "$limit"
        //         }
        //       }
        //     }
        //
        return $this->parse_orders_by_id ($this->safe_value($response, 'return', array ()), $symbol, $since, $limit);
    }

    public function parse_trades ($trades, $market = null, $since = null, $limit = null, $params = array ()) {
        $result = array ();
        if (gettype ($trades) === 'array' && count (array_filter (array_keys ($trades), 'is_string')) == 0) {
            for ($i = 0; $i < count ($trades); $i++) {
                $result[] = $this->parse_trade($trades[$i], $market);
            }
        } else {
            $ids = is_array ($trades) ? array_keys ($trades) : array ();
            for ($i = 0; $i < count ($ids); $i++) {
                $id = $ids[$i];
                $trade = $this->parse_trade($trades[$id], $market);
                $result[] = array_merge ($trade, array ( 'id' => $id ), $params);
            }
        }
        $result = $this->sort_by($result, 'timestamp');
        $symbol = ($market !== null) ? $market['symbol'] : null;
        return $this->filter_by_symbol_since_limit($result, $symbol, $since, $limit);
    }

    public function sign ($path, $api = 'public', $method = 'GET', $params = array (), $headers = null, $body = null) {
        $url = $this->urls['api'][$api];
        $query = $this->omit ($params, $this->extract_params($path));
        if ($api === 'private' || $api === 'dwapi') {
            $url .= $this->get_private_path ($path, $params);
            $this->check_required_credentials();
            $nonce = $this->nonce ();
            $body = $this->urlencode (array_merge (array (
                'nonce' => $nonce,
                'method' => $path,
            ), $query));
            $signature = $this->sign_body_with_secret($body);
            $headers = array (
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Key' => $this->apiKey,
                'Sign' => $signature,
            );
        } else if ($api === 'public') {
            $url .= $this->get_version_string() . '/' . $this->implode_params($path, $params);
            if ($query) {
                $url .= '?' . $this->urlencode ($query);
            }
        } else {
            $url .= '/' . $this->implode_params($path, $params);
            if ($method === 'GET') {
                if ($query) {
                    $url .= '?' . $this->urlencode ($query);
                }
            } else {
                if ($query) {
                    $body = $this->json ($query);
                    $headers = array (
                        'Content-Type' => 'application/json',
                    );
                }
            }
        }
        return array ( 'url' => $url, 'method' => $method, 'body' => $body, 'headers' => $headers );
    }
}
