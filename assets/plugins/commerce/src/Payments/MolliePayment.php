<?php

namespace Commerce\Payments;

use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient as MollieApiClient;

class MolliePayment extends Payment
{
    protected $client;
    public $debug = false;

    public function __construct($modx, array $params = [])
    {
        parent::__construct($modx, $params);
        $this->lang = $modx->commerce->getUserLanguage('mollie');
        $this->debug = !empty($this->getSetting('debug'));
        if (!class_exists('\Mollie\Api\MollieApiClient')) {
            include_once MODX_BASE_PATH . 'assets/plugins/mollie/vendor/autoload.php';
        }
        $this->client = new MollieApiClient();
        try {
            $this->client->setApiKey($this->getSetting('api_key'));
        } catch (ApiException $e) {
            $this->modx->logEvent(0, 3, 'Api error: ' . $e->getMessage(), 'Commerce Mollie Payment');
        }
    }

    public function getMarkup()
    {
        if (empty($this->getSetting('api_key'))) {
            return '<span class="error" style="color: red;">' . $this->lang['mollie.error_empty_api_key'] . '</span>';
        }
    }

    public function getPaymentLink()
    {
        $processor = $this->modx->commerce->loadProcessor();
        $order = $processor->getOrder();
        $currency = ci()->currency->getCurrency($order['currency']);
        $amount = (float) $order['amount'];
        $payment = $this->createPayment($order['id'], ci()->currency->convertToDefault($amount, $currency['code']));
        $description = ci()->tpl->parseChunk($this->lang['payments.payment_description'], [
            'order_id'  => $order['id'],
            'site_name' => $this->modx->getConfig('site_name'),
        ]);
        $data = [
            'description' => $description,
            'amount'      => [
                'currency' => ci()->currency->getDefaultCurrencyCode(),
                'value'    => number_format($payment['amount'], 2, '.', ''),
            ],
            'redirectUrl' => $this->modx->getConfig('site_url') . 'commerce/mollie/payment-success/',
            'webhookUrl'  => $this->modx->getConfig('site_url') . 'commerce/mollie/payment-process/',
            'metadata'    => [
                'orderId'     => $order['id'],
                'paymentId'   => $payment['id'],
                'paymentHash' => $payment['hash'],
            ]
        ];
        if ($this->debug) {
            $this->modx->logEvent(0, 3, 'Payment start: <pre>' . htmlentities(print_r($data, true)) . '</pre>',
                'Commerce Mollie Payment: start');
        }
        try {
            $_payment = $this->client->payments->create($data);
        } catch (ApiException $e) {
            if ($this->debug) {
                $this->modx->logEvent(0, 3, 'Api error: ' . $e->getMessage(), 'Commerce Mollie Payment');
            }
        }

        return !empty($_payment) ? $_payment->getCheckoutUrl() : false;
    }

    public function handleCallback()
    {
        if ($this->debug) {
            $this->modx->logEvent(0, 1, 'Callback data: <pre>' . htmlentities(print_r($_REQUEST, true)) . '</pre>', 'Commerce Mollie Payment: callback start');
        }
        if (!empty($_POST['id']) && is_scalar($_POST['id'])) {
            try {
                $_payment = $this->client->payments->get($_POST['id']);
            } catch (ApiException $e) {
                if ($this->debug) {
                    $this->modx->logEvent(0, 3, 'Api error: ' . $e->getMessage(), 'Commerce Mollie Payment');
                }
            }
            if (!empty($_payment->metadata->orderId) && !empty($_payment->metadata->paymentId) && !empty($_payment->metadata->paymentHash) && $_payment->isPaid()) {
                try {
                    ci()->commerce->loadProcessor()->processPayment($_payment->metadata->paymentId, (float)$_payment->amount);
                } catch (\Exception $e) {
                    if ($this->debug) {
                        $this->modx->logEvent(0, 3, 'Payment processing failed: ' . $e->getMessage(), 'Commerce Mollie Payment');
                    }

                    return false;
                }
            } else {
                return false;
            }
        }
    }
}
