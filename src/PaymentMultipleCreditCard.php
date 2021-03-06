<?php

namespace Rockbuzz\SDKYapay;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Rockbuzz\SDKYapay\Contract\Payment;
use Rockbuzz\SDKYapay\Exception\PaymentException;
use Rockbuzz\SDKYapay\Payment\Billing;
use Rockbuzz\SDKYapay\Payment\CreditCard;
use Rockbuzz\SDKYapay\Payment\Items;
use Rockbuzz\SDKYapay\Payment\MultipleCreditCard;
use Rockbuzz\SDKYapay\Payment\TransactionCreditCard;
use Rockbuzz\SDKYapay\Payment\TransactionMultipleCreditCard;

class PaymentMultipleCreditCard extends BasePayment implements Payment
{
    /**
     * @var TransactionCreditCard
     */
    protected $transaction;

    /**
     * @var CreditCard
     */
    protected $creditCard;

    public function __construct(
        Config $config,
        int $methodCode,
        TransactionMultipleCreditCard $transaction,
        MultipleCreditCard $creditCard,
        Items $items,
        Billing $billing
    ) {
        parent::__construct($config, $methodCode, $items, $billing);
        $this->transaction = $transaction;
        $this->creditCard = $creditCard;
    }

    public function done(ClientInterface $client = null): Result
    {
        try {
            return new Result($this->getContents($client ?? new Client()));
        } catch (\Exception $exception) {
            throw new Paymentexception(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    private function getContents(ClientInterface $client)
    {

        $body = json_encode([
            'codigoEstabelecimento' => (int) $this->config->getStoreCode(),
            'codigoFormaPagamento' => (int) $this->methodCode,
            'dadosMultiplosCartoes' => [
                $this->creditCard,
            ],
            'itensDoPedido' => $this->items,
            'transacao' => $this->transaction,
            'dadosCobranca' => $this->billing,
        ]);

        $response = $client->request('POST', $this->config->getEndpoint(), [
            'headers' => [
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Cache-Control' => 'no-cache',
            ],
            'auth'    => [
                '0' => $this->config->getUsername(),
                '1' => $this->config->getPassword(),
            ],
            'body'    => $body,
        ]);

        return $response->getBody()->getContents();
    }
}
