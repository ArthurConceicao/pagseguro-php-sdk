<?php

namespace CRPTecnologia\PagSeguroBoleto;

use CRPTecnologia\PagSeguroBoleto\Core\Curl;
use Exception;

class Boleto
{
    private $curl;

    /**
     * @var Config
     */
    private $config;
    /**
     * @var BoletoBuilder
     */
    private $boletoBuilder;

    public function __construct(Curl $curl, Config $config, BoletoBuilder $boletoBuilder)
    {
        $this->curl = $curl;
        $this->config = $config;
        $this->boletoBuilder = $boletoBuilder;
    }

    /**
     * @return mixed|\SimpleXMLElement|string
     * @throws Exception
     */
    public function send()
    {
        $get = $this->prepareGet();
        $post = $this->preparePost();
        $this->prepareHeader();

        $this->curl->setUrl(
            'https://ws.pagseguro.uol.com.br/recurring-payment/boletos?' .
            http_build_query($get)
        );
        $this->requiredFields($post);
        $this->requiredFieldsButNot($post);
        $this->curl->setData($post);

        return $this->curl->exec();
    }

    private function prepareHeader()
    {
        $this->curl->setContentType('application/json;charset=ISO-8859-1');
        $this->curl->setAccept('application/json;charset=ISO-8859-1');
    }

    private function prepareGet(): array
    {
        $get = [];
        $get['email'] = $this->config->getEmail();
        $get['token'] = $this->config->getToken();
        return $get;
    }

    private function preparePost(): array
    {
        $post = [];
        $post['customer'] = $this->boletoBuilder->getCustomer();
        $post['reference'] = $this->boletoBuilder->getReference();
        $post['firstDueDate'] = $this->boletoBuilder->getFirstDueDate();
        $post['numberOfPayments'] = $this->boletoBuilder->getNumberOfPayments();
        $post['amount'] = $this->boletoBuilder->getAmount();
        $post['description'] = $this->boletoBuilder->getDescription();
        $post['instructions'] = $this->boletoBuilder->getInstructions();
        $post['notificationURL'] = $this->boletoBuilder->getNotificationURL();
        return $post;
    }

    /**
     * @throws Exception
     */
    protected function requiredFields($post)
    {
        if (!isset($post['amount'])) {
            //PagSeguro error code 1040
            throw new Exception('amount is required');
        }
        if (!isset($post['description'])) {
            //PagSeguro error code 1060
            throw new Exception('description is required');
        }
        if (!isset($post['customer']['document'])) {
            //PagSeguro error code 1110
            throw new Exception('customer document is required');
        }
        if (!isset($post['customer']['document']['type'])) {
            //prevents erro 500
            throw new Exception('customer document type is required');
        }
        if (!isset($post['customer']['document']['value'])) {
            //PagSeguro error code 1113
            throw new Exception('customer document value is required');
        }
        if (!isset($post['customer']['name'])) {
            //PagSeguro error code 1120
            throw new Exception('name is required');
        }
        if (!isset($post['customer']['email'])) {
            //PagSeguro error code 1130
            throw new Exception('email is required');
        }
        if (!isset($post['customer']['phone'])) {
            //PagSeguro error code 1140
            throw new Exception('customer phone is required');
        }
    }

    protected function requiredFieldsButNot($post)
    {
        if (!isset($post['periodicity'])) {
            $post['periodicity'] = 'monthly';
        }
        if (!isset($post['numberOfPayments'])) {
            $post['numberOfPayments'] = 1;
        }
        if (!isset($post['reference'])) {
            $post['reference'] = 'generated automatically in: ' . date('r');
        }
        if (!isset($post['firstDueDate'])) {
            $post['firstDueDate'] = date("Y-m-d", strtotime("+3 days", time()));
        }
    }
}
