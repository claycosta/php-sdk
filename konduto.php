<?php
/*
 * Import this class to your project if you don't want to use autoloader.
 * e.g.
 * require_once "konduto/sdk/konduto.php";
 */
require_once "src/Exceptions/KondutoException.php";
require_once "src/Exceptions/CommunicationErrorException.php";
require_once "src/Exceptions/DuplicateOrderException.php";
require_once "src/Exceptions/InvalidAPIKeyException.php";
require_once "src/Exceptions/InvalidOrderException.php";
require_once "src/Exceptions/InvalidVersionException.php";
require_once "src/Exceptions/KondutoAPIErrorException.php";
require_once "src/Exceptions/OperationNotAllowedException.php";
require_once "src/Exceptions/OrderNotFoundException.php";
require_once "src/Exceptions/TimeoutException.php";
require_once "src/Exceptions/TransactionLimitExceededException.php";
require_once "src/Models/ValidationSchema.php";
require_once "src/Models/Entity.php";
require_once "src/Models/Model.php";
require_once "src/Models/Customer.php";
require_once "src/Models/Device.php";
require_once "src/Models/Geolocation.php";
require_once "src/Models/Address.php";
require_once "src/Models/Payment.php";
require_once "src/Models/CreditCard.php";
require_once "src/Models/Item.php";
require_once "src/Models/Navigation.php";
require_once "src/Models/Boleto.php";
require_once "src/Models/Order.php";
require_once "src/Core/ApiControl.php";
require_once "src/Core/Konduto.php";