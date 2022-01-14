<?php
if (empty($modx->commerce) && !defined('COMMERCE_INITIALIZED')) {
    return;
}

$isSelectedPayment = !empty($order['fields']['payment_method']) && $order['fields']['payment_method'] == 'mollie';

switch ($modx->event->name) {
    case 'OnRegisterPayments': {
        $class = new \Commerce\Payments\MolliePayment($modx, $params);
        if (empty($params['title'])) {
            $lang = $modx->commerce->getUserLanguage('payneteasy');
            $params['title'] = $lang['mollie.caption'];
        }
        $modx->commerce->registerPayment('mollie', $params['title'], $class);
        break;
    }

    case 'OnBeforeOrderSending': {
        if ($isSelectedPayment) {
            $FL->setPlaceholder('extra', $FL->getPlaceholder('extra', '') . $modx->commerce->loadProcessor()->populateOrderPaymentLink());
        }

        break;
    }

    case 'OnManagerBeforeOrderRender': {
        if (isset($params['groups']['payment_delivery']) && $isSelectedPayment) {
            $lang = $modx->commerce->getUserLanguage('mollie');
            $params['groups']['payment_delivery']['fields']['payment_link'] = [
                'title'   => $lang['mollie.link_caption'],
                'content' => function($data) use ($modx) {
                    return $modx->commerce->loadProcessor()->populateOrderPaymentLink('@CODE:<a href="[+link+]" target="_blank">[+link+]</a>');
                },
                'sort' => 50,
            ];
        }

        break;
    }
}
