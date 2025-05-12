<?php


namespace Model;


use Lib\components\GingerPSPConfig;

class GingerGateway
{
    private $db;

    public function __construct(\DbCore $db)
    {
        $this->db = $db;
    }

    /**
     * save instance to PSP table
     *
     * @param Ginger $ginger
     */
    public function save(Ginger $ginger)
    {
        if($ginger->getGingerOrderId() !== null)
        {
            $this->_deleteByCartId($ginger->getIdCart());
            $this->_saveOrder(
                $ginger->getGingerOrderId(),
                $ginger->getIdCart(),
                $ginger->getKey(),
                $ginger->getPaymentMethod(),
                $ginger->getIdOrder(),
                $ginger->getReference()
            );
        }
    }

    private function _deleteByCartId($cartId)
    {
        try {
            $this->db->Execute("DELETE FROM `" . \_DB_PREFIX_ .GingerPSPConfig::PSP_PREFIX. "` WHERE `id_cart` = " . $cartId);
        } catch (\Exception $e) {

        }
    }

    private function _saveOrder($responseId, $cartId, $customerSecureKey, $type, $currentOrder = null, $reference = null)
    {
        try {
            $fields = ['`id_cart`',
                '`ginger_order_id`',
                '`key`',
                '`payment_method`'];
            $values = ['"' . $cartId . '"',
                '"' . $responseId . '"',
                '"' . $customerSecureKey . '"',
                '"' . $type . '"'];
            if ($currentOrder !== null)
            {
                array_push($fields, '`id_order`');
                array_push($values, '"' . $currentOrder . '"');
            }
            if ($reference !== null)
            {
                array_push($fields, '`reference`');
                array_push($values, '"' . $reference . '"');
            }
            $this->db->Execute("INSERT INTO `" . \_DB_PREFIX_ .GingerPSPConfig::PSP_PREFIX."` (" . implode(',', $fields) . ") VALUES (" . implode(',', $values) . ");");
        } catch (\Exception $e) {

        }
    }

    /**
     * fetch order by cart id
     *
     * @param int $cartId
     * @return array
     */
    public function getByCartId($cartId)
    {
        $row = $this->db->getRow(
            sprintf(
                'SELECT * FROM `%s` WHERE `id_cart` = \'%s\'', _DB_PREFIX_.GingerPSPConfig::PSP_PREFIX, $cartId
            )
        );
        $ginger = new Ginger();
        if (is_array($row) && count($row))
        {
            $ginger->setGingerOrderId(isset($row['ginger_order_id']) ? $row['ginger_order_id'] : null)
                ->setIdCart(isset($row['id_cart']) ? $row['id_cart'] : null)
                ->setIdOrder(isset($row['id_order']) ? $row['id_order'] : null)
                ->setKey(isset($row['key']) ? $row['key'] : null)
                ->setPaymentMethod(isset($row['payment_method']) ? $row['payment_method'] : null)
                ->setReference(isset($row['reference']) ? $row['reference'] : null);
        }

        return $ginger;
    }

    public function getByOrderId($orderID)
    {
        $row = $this->db->getRow(
            sprintf(
                'SELECT * FROM `%s` WHERE `id_order` = \'%s\'', _DB_PREFIX_.GingerPSPConfig::PSP_PREFIX, $orderID
            )
        );
        $ginger = new Ginger();
        if (is_array($row) && count($row))
        {
            $ginger->setGingerOrderId(isset($row['ginger_order_id']) ? $row['ginger_order_id'] : null)
                ->setIdCart(isset($row['id_cart']) ? $row['id_cart'] : null)
                ->setIdOrder(isset($row['id_order']) ? $row['id_order'] : null)
                ->setKey(isset($row['key']) ? $row['key'] : null)
                ->setPaymentMethod(isset($row['payment_method']) ? $row['payment_method'] : null)
                ->setReference(isset($row['reference']) ? $row['reference'] : null);
        }

        return $ginger;
    }
    /**
     *
     * @param int $cartId
     * @param int $orderId
     */
    public function update($cartId, $orderId)
    {
        try {
            $this->db->Execute(
                "UPDATE  `" . \_DB_PREFIX_ .GingerPSPConfig::PSP_PREFIX."` "
                . "SET `id_order` =  $orderId  "
                . "WHERE `id_cart` = " . $cartId
            );
        } catch (\Exception $e) {
            return false;
        }
    }

}
