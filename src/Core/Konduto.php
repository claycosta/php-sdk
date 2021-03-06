<?php namespace Konduto\Core;

use \Konduto\Models as Models;
use \Konduto\Exceptions as Exceptions;

/**
 * Konduto SDK core component
 * 
 * This class performs Konduto API functions as described in Konduto documentation
 * at http://docs.konduto.com/.
 * Behind the static methods, it uses php cURL library to perform HTTP requests
 * to Konduto API endpoint. It automatically generates and parses messages exchanged
 * with the API, leaving only to the client code SDK models objects.
 * 
 * The available methods are:
 * - setApiKey
 * - setVersion
 * - sendOrder
 * - analyze
 * - updateOrderStatus
 * - getOrder
 *
 * @api v1
 *
 * @version v2
 */
abstract class Konduto extends ApiControl {

    /**
     * Sets the version of Konduto API that will be used for performing requests.
     *
     * @param ver version
     *
     * @throws InvalidVersionException if version is not existent
     */
    public static function setVersion($ver = CURRENT_VERSION) {
        self::validate_version($ver);
        self::$version = $ver;
    }

    /**
     * Sets an API key to be used for authenticating Konduto API in requests.
     * 
     * @param key API key
     * 
     * @throws InvalidAPIKeyException if key is not valid
     */
    public static function setApiKey($key) {
        if (is_string($key) and strlen($key) == 21 and ($key[0] == 'T' or $key[0] == 'P')) {
            self::$key = $key;
            return true;
        }
        throw new Exceptions\InvalidAPIKeyException($key);
    }

    /**
     * Queries an order previously analyzed by Konduto API given its id
     *
     * @param id
     *
     * @throws InvalidOrderException if param id is not a valid id
     *
     * @return order \Konduto\Models\Order object with order data
     */
    public static function getOrder($id) {
        if (!Models\ValidationSchema::validateField('order' ,'id', $id)) {
            throw new Exceptions\InvalidOrderException("id");
        }

        $order_array = self::sendRequest(null, METHOD_GET, "/orders/{$id}");
        
        // Do a check in the response for an error 404.
        self::was_order_found($order_array, $id);
        
        return new Models\Order(array_key_exists("order", $order_array) ? $order_array["order"] : $order_array);
    }

    /**
     * Sends an order for analysis using Konduto and returns a recomnendation
     *
     * When an order is sent for analysis, the property 'recommendation' inside order param is populated
     * with the recommendation returned by Konduto.
     *
     * @param order a valid \Konduto\Models\Order object
     * @param analyze boolean. If set to false, just send order to Konduto, but do not analyse it.
     *
     * @throws InvalidOrderException if the provided order does not contain all valid fields.
     *
     * @return true if success
     */
    public static function analyze(Models\Order &$order, $analyze = true) {

        if (!$order->is_valid()) {
            throw new Exceptions\InvalidOrderException($order->getErrors());
            return;
        }

        $order_array = $order->to_array();

        if ($analyze === false) {
            $order_array["analyze"] = false;
        }

        $response = self::sendRequest(json_encode($order_array), 
                        METHOD_POST, '/orders');

        if (self::check_post_response($response, $order->id()) 
            and $analyze === true) {
            $orderAssoc = array_key_exists("order", $response) 
                            ? $response["order"] : $response;
            $order->set($orderAssoc);
        }

        return true;
    }

    /**
     * Persists an order without analyzing it
     *
     * It is an alias for Konduto::analyze($order, false)
     */ 
    public static function sendOrder(Models\Order &$order) {
        return self::analyze($order, false);
    }

    /**
     * Updates the status of an existing order
     *
     * Sends to Konduto system the information regarding the outcome of this order. This action
     * is required to improve Konduto recommendation algorithm.
     *
     * @param order_id id of the order being updated
     * @param status string containing 'approved', 'declined' or 'fraud'
     * @param comments string containing comments of why the status is being updated as so
     *
     * @throws InvalidOrderException if the provided order_id or status are not valid
     *
     * @return boolean whether the operation is successfull
     */
    public static function updateOrderStatus($order_id, $status, $comments = "") {

        if (!in_array($status, [Models\STATUS_APPROVED, Models\STATUS_DECLINED, Models\STATUS_FRAUD])) {
            throw new Exceptions\InvalidOrderException("status");
        }

        if (!Models\ValidationSchema::validateField("order", 'id', $order_id)) {
            throw new Exceptions\InvalidOrderException("id");
        }

        $json_msg = [
            "order_id" => $order_id,
            "status" => $status,
            "comments" => "$comments",
        ];

        $json_msg = json_encode($json_msg);

        $response = self::sendRequest($json_msg, METHOD_PUT, "/orders/$order_id");

        return self::was_order_found($response, $order_id);
    }
}