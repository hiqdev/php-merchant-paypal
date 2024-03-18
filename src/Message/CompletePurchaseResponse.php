<?php

/*
 * PayPal driver for Omnipay PHP payment library
 *
 * @link      https://github.com/hiqdev/omnipay-paypal
 * @package   omnipay-paypal
 * @license   MIT
 * @copyright Copyright (c) 2015-2016, HiQDev (http://hiqdev.com/)
 */

namespace Omnipay\PayPal\Message;

use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;

/**
 * PayPal Complete Purchase Response.
 */
class CompletePurchaseResponse extends AbstractResponse
{
    /**
     * @var CompletePurchaseRequest
     */
    public $request;

    public function __construct(RequestInterface $request, $data)
    {
        parent::__construct($request, $data);

        if ($this->getResult() !== 'VERIFIED') {
            throw new InvalidResponseException('Not verified');
        }

        if ($this->request->getTestMode() !== $this->getTestMode()) {
            throw new InvalidResponseException('Invalid test mode');
        }

        if ($this->getTransactionStatus() !== 'Completed') {
            throw new InvalidResponseException('Invalid payment status');
        }
    }

    /**
     * Whether the payment is successful.
     * @return boolean
     */
    public function isSuccessful()
    {
        return true;
    }

    /**
     * Whether the payment is test.
     * @return boolean
     */
    public function getTestMode(): bool
    {
        return (bool)($this->data['test_ipn'] ?? null);
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->data['item_number'];
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    public function getTransactionReference(): string
    {
        return $this->data['txn_id'];
    }

    /**
     * Returns the transaction status.
     * @return string
     */
    public function getTransactionStatus(): string
    {
        return $this->data['payment_status'];
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    public function getAmount(): string
    {
        return $this->data['payment_gross'] ?? $this->data['mc_gross'];
    }

    /**
     * Returns the result, injected by [[CompletePurchaseRequest::sendData()]].
     * @return mixed
     */
    public function getResult()
    {
        return $this->data['_result'];
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    public function getFee(): string
    {
        return $this->data['payment_fee'] ?? $this->data['mc_fee'];
    }

    /**
     * Returns the currency.
     * @return string
     */
    public function getCurrency(): string
    {
        return strtoupper($this->data['mc_currency']);
    }

    /**
     * Returns the payer "name/email".
     * @return string
     */
    public function getPayer(): string
    {
        $payer = $this->data['address_name'] . '/' . $this->data['payer_email'];
        $charset = isset($this->data['charset']) ? strtoupper(str_replace("_", "-", $this->data['charset'])) : mb_detect_encoding($payer, 'auto');
        if ($charset !== 'UTF-8') {
            $payer = iconv($charset, 'UTF-8//IGNORE', $payer);
        }

        return $payer;
    }

    /**
     * Returns the payment date.
     * @return string
     */
    public function getTime(): string
    {
        return date('c', strtotime($this->data['payment_date']));
    }
}
