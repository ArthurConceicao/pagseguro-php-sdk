<?php
/**
 * Created by PhpStorm.
 * User: arthur.conceicao
 * Date: 16/11/2018
 * Time: 16:46
 */

namespace CRPTecnologia\PagSeguroBoleto;

use CRPTecnologia\PagSeguroBoleto\Core\Utils;
use InvalidArgumentException;

class BoletoBuilder
{
    private $customer = [
        'document' => [
            'type' => null,
            'value' => null,
        ],
        'name' => null,
        'email' => null,
        'phone' => [
            'areaCode' => null,
            'number' => null,
        ],
        'address' => [
          'street' => null,
          'number' => null,
          'district' => null,
          'postalCode' => null,
          'city' => null,
          'state' => null,
        ],
    ];

    private $reference;
    private $firstDueDate;
    private $numberOfPayments;
    private $amount;
    private $description;
    private $instructions;
    private $notificationURL;
    private $periodicity;

    /**
     * @return mixed
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @return mixed
     */
    public function getFirstDueDate()
    {
        return $this->firstDueDate;
    }

    /**
     * @return mixed
     */
    public function getNumberOfPayments()
    {
        return $this->numberOfPayments;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getInstructions()
    {
        return $this->instructions;
    }

    /**
     * @return mixed
     */
    public function getNotificationURL()
    {
        return $this->notificationURL;
    }


    /**
     * @return array
     */
    public function getCustomer(): array
    {
        return $this->customer;
    }

    /**
     * @param array $customer
     */
    public function setCustomer(array $customer): void
    {
        $this->customer = $customer;
    }

    public function setCustomerDocument(string $type, string $documentNumber)
    {
        $documentNumber = Utils::onlyNumbers($documentNumber);

        if ($type != "CPF" || $type === "CNPJ") {
            throw new InvalidArgumentException('customer document value is invalid. it must be a valid CPF or CNPJ: ' . $documentNumber);
        }

        if ($type === "CPF" && !Utils::checkCPF($documentNumber)) {
            //PagSeguro error code 1114
            throw new InvalidArgumentException('customer document value is invalid. it must be a valid CPF: ' . $documentNumber);
        }

        if ($type === "CNPJ" && strlen($documentNumber) !== 14) {
            //PagSeguro error code 1115
            throw new InvalidArgumentException('customer document value is invalid. it must be a valid CNPJ: ' . $documentNumber);
        }

        $this->customer['document']['type'] = $type;
        $this->customer['document']['value'] = $documentNumber;
    }

    public function setCustomerName($data)
    {
        if (strlen($data) > 50) {
            //PagSeguro error code 1121
            throw new InvalidArgumentException('name size is invalid. the maximum size is 50 characters: ' . $data);
        }
        $this->customer['name'] = $data;
    }

    public function setCustomerEmail($data)
    {
        if (!filter_var($data, FILTER_VALIDATE_EMAIL)) {
            //PagSeguro error code 1132
            throw new InvalidArgumentException('email is invalid. it must be a valid format e-mail: ' . $data);
        }
        if (strlen($data) > 60) {
            //PagSeguro error code 1131
            throw new InvalidArgumentException('email size is invalid. the maximum size is 60 characters: ' . $data);
        }
        $this->customer['email'] = $data;
    }

    public function setCustomerPhone($areaCode, $number)
    {
        $areaCode = Utils::onlyNumbers($areaCode);
        $areaCode = substr($areaCode, 0, 2);

        $number = Utils::onlyNumbers($number);
        $number = substr($number, 0, 9);

        if (strlen($areaCode) !== 2) {
            //PagSeguro error code 1151
            throw new InvalidArgumentException('phone areaCode is invalid. it must be 2 digits: ' . $areaCode);
        }

        if (strlen($number) < 8 || strlen($number) > 9) {
            //PagSeguro error code 1161
            throw new InvalidArgumentException('phone number is invalid. it must be 8 or 9 digits without separator: ' . $number);
        }

        $this->customer['phone']['areaCode'] = $areaCode;
        $this->customer['phone']['number'] = $number;
    }

    public function setReference($data)
    {
        if (strlen($data) > 200) {
            //PagSeguro error code 1001
            throw new InvalidArgumentException('maximum reference size is 200: ' . $data);
        }
        $this->reference = $data;
    }

    public function setFirstDueDate($data)
    {
        $this->firstDueDate = $data;
    }

    public function setNumberOfPayments($data)
    {
        $data = Utils::onlyNumbers($data);

        if ($data < 1 || $data > 12) {
            //PagSeguro error code 1021
            throw new InvalidArgumentException('numberOfPayments is invalid. it must have only numbers (0-9) and value between 1 to 12.: ' . $data);
        }
        $this->numberOfPayments = $data;
    }

    public function setAmount($data)
    {
        $data = (double)$data;
        if ($data < 5 || $data > 1000000) {
            //PagSeguro error code 1041
            throw new InvalidArgumentException('amount is invalid. it is allowed value between 5.00 to 1000000.00.: ' . $data);
        }
        $this->amount = $data;
    }

    public function setDescription($data)
    {
        if (strlen($data) > 100) {
            //PagSeguro error code 1061
            throw new InvalidArgumentException('description is invalid. the maximum size is 100 characters: ' . $data);
        }
        $this->description = $data;
    }

    public function setInstructions($data)
    {
        if (strlen($data) > 100) {
            //PagSeguro error code 1050
            throw new InvalidArgumentException('instructions is invalid. the maximum size is 100 characters: ' . $data);
        }
        $this->instructions = $data;
    }

    public function setNotificationURL($data)
    {
        if (strlen($data) > 255 || !filter_var($data, FILTER_VALIDATE_URL)) {
            //PagSeguro error code 1070
            //erro 500 when dont use http and is close to maximum size
            throw new InvalidArgumentException('notificarionURL is invalid. the maximum size is 255 characters and should be a valid url ' . $data);
        }
        $this->notificationURL = $data;
    }

    /*
     * @todo check address erros.
     */
    public function setCustomerAddressStreet($data)
    {
        $this->customer['address']['street'] = $data;
    }

    public function setCustomerAddressNumber($data)
    {
        $this->customer['address']['number'] = $data;
    }

    public function setCustomerAddressDistrict($data)
    {
        $this->customer['address']['district'] = $data;
    }

    public function setCustomerAddressPostalCode($data)
    {
        $data = Utils::onlyNumbers($data);
        $this->customer['address']['postalCode'] = $data;
    }

    public function setCustomerAddressCity($data)
    {
        $this->customer['address']['city'] = $data;
    }

    public function setCustomerAddressState($data)
    {
        $data = strtoupper($data);
        if (strlen($data) === 2) {
            $this->customer['address']['state'] = $data;
        }
    }
}