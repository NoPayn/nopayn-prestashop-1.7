<?php

namespace Model;

class Ginger
{

    private $id;
    private $id_cart;
    private $id_order;
    private $key;
    private $ginger_order_id;
    private $payment_method;
    private $reference;

    public function getId() {
        return $this->id;
    }

    public function getIdCart() {
        return $this->id_cart;
    }

    public function getIdOrder() {
        return $this->id_order;
    }

    public function getKey() {
        return $this->key;
    }

    public function getGingerOrderId() {
        return $this->ginger_order_id;
    }

    public function getPaymentMethod() {
        return $this->payment_method;
    }

    public function getReference() {
        return $this->reference;
    }

    public function setIdCart($id_cart) {
        $this->id_cart = $id_cart;
        return $this;
    }

    public function setIdOrder($id_order) {
        $this->id_order = $id_order;
        return $this;
    }

    public function setKey($key) {
        $this->key = $key;
        return $this;
    }

    public function setGingerOrderId($ginger_order_id) {
        $this->ginger_order_id = $ginger_order_id;
        return $this;
    }

    public function setPaymentMethod($payment_method)
    {
        $this->payment_method = $payment_method;
        return $this;
    }

    public function setReference($reference)
    {
        $this->reference = $reference;
        return $this;
    }

}
