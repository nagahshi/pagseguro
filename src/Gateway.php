<?php
namespace Nagahshi\Pagseguro;
use Nagahshi\Pagseguro\Connection;
class Gateway
{

  protected $url;
  protected $url_checkout;
  protected $url_payment;
  protected $email;
  protected $token;
  protected $client = array();
  protected $currency;
  protected $item = array();
  protected $client_id;

  function __construct()
  {
    $this->url = 'https://ws.pagseguro.uol.com.br/v2/checkout/';
    $this->url_checkout = 'https://pagseguro.uol.com.br/v2/checkout/payment.html?code=';
    $this->url_get_payment = 'https://ws.pagseguro.uol.com.br/v3/transactions/notifications/';
    $this->currency = 'BRL';
  }

  public function setItems($items){
    try {
      foreach ($items as $key => $item) {
        if(!isset($this->client_id)||($this->client_id+0)==0) throw new \Exception("client id not exists set a client", 1);
        if(!isset($item['itemDescription']) || $item['itemDescription'] =='') throw new \Exception("itemDescription(product name) is required", 1);
        if(!isset($item['itemAmount']) || $item['itemAmount'] =='') throw new \Exception("itemAmount(product price) is required", 1);
        $this->item['itemId'.($key+1)] = ($key+1);
        $this->item['itemDescription'.($key+1)] = trim($item['itemDescription']);
        $this->item['itemAmount'.($key+1)] = number_format($item['itemAmount'], 2, '.', '');
        $this->item['itemQuantity'.($key+1)] = (isset($item['itemQuantity'])&&$item['itemQuantity'] > 0)?$item['itemQuantity']:1;
        $this->item['itemWeight'.($key+1)] = (isset($item['itemWeight']))?$item['itemWeight']:0;
      }
    } catch (\Exception $e) {
      abort(500,$e->getMessage());
    }
  }

  public function setCredentials($email,$token){
    $this->email = $email;
    $this->token = $token;
  }

  public function setClient($client){
    try {
      if(!isset($client['senderName'])) throw new \Exception("senderName(client name) is required", 1);
      if(!isset($client['senderEmail'])) throw new \Exception("senderEmail(client email) is required", 1);
      if(!isset($client['senderClient'])) throw new \Exception("senderClient(client id) is required", 1);
      $this->client_id = $client['senderClient'];
      unset($client['senderClient']);
      $this->client['reference'] = $this->client_id;
      $this->client['senderName'] = trim($client['senderName']);
      $this->client['senderEmail'] = filter_var(trim($client['senderEmail']), FILTER_SANITIZE_STRING);
      $this->client['senderAreaCode'] = $this->verifyKey($client,'senderAreaCode');
      $this->client['senderPhone'] = $this->verifyKey($client,'senderPhone');
      $this->client['shippingType'] = 1;
      $this->client['shippingAddressStreet'] = $this->verifyKey($client,'shippingAddressStreet');
      $this->client['shippingAddressNumber'] = $this->verifyKey($client,'shippingAddressNumber');
      $this->client['shippingAddressComplement'] = $this->verifyKey($client,'shippingAddressComplement');
      $this->client['shippingAddressDistrict'] = $this->verifyKey($client,'shippingAddressDistrict');
      $this->client['shippingAddressPostalCode'] = $this->verifyKey($client,'shippingAddressPostalCode');
      $this->client['shippingAddressCity'] = $this->verifyKey($client,'shippingAddressCity');
      $this->client['shippingAddressState'] = $this->verifyKey($client,'shippingAddressState');
      $this->client['shippingAddressCountry'] = 'BRA';
    } catch (\Exception $e) {
      abort(500,$e->getMessage());
    }
  }

  private function verifyKey($array,$key){
    return (isset($array[$key]))?trim($array[$key]):null;
  }

  public function requestURLPayment($redirect_url){
    try {
      $params = $this->makeParams($redirect_url);
      $data = http_build_query($params);
      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_URL => $this->url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
          "charset: ISO-8859-1",
          "content-type: application/x-www-form-urlencoded"
        ),
      ));
      $xml = curl_exec($curl);
      if($xml == 'Unauthorized') throw new \Exception('Unauthorized', 1);
      $err = curl_error($curl);
      curl_close($curl);
      if($err) throw new \Exception($err, 1);
      $xml = simplexml_load_string($xml);
      if(count($xml->error) > 0) throw new \Exception('invalid credentials', 1);
      return $this->url_checkout . $xml->code;
    } catch (\Exception $e) {
      abort(500,$e->getMessage());
    }
  }

  private function makeParams($redirect_url){
    try {
      if(count($this->client) == 0) throw new \Exception("setClient is required", 1);
      if(count($this->item) == 0) throw new \Exception("setItem is required", 1);
      $data['email'] = $this->email;
      $data['token'] = $this->token;
      $data['currency'] = $this->currency;
      $data = array_merge($data,$this->item);
      $data = array_merge($data,$this->client);
      $data['redirectURL'] = $redirect_url;
      return $data;
    } catch (Exception $e) {
      abort(500,$e->getMessage());
    }
  }

  public function getData(){
    return [$this->client,$this->item];
  }

  public function callback($code){
    try {
      $query = http_build_query(array('email'=>$this->email,'token'=>$this->token));
      $url = $this->url_get_payment.$code."?$query";

      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
          "charset: ISO-8859-1",
          "content-type: application/x-www-form-urlencoded"
        ),
      ));
      $xml = curl_exec($curl);
      $err = curl_error($curl);
      curl_close($curl);
      if($err) throw new \Exception($err, 1);

      $xml = simplexml_load_string($xml);
      $id_client = $xml->reference;
      $items = $xml->items;
      return array('client_id'=>$id_client,'items'=>$xml->items);
    } catch (Exception $e) {
      abort(500,$e->getMessage());
    }
  }
}
