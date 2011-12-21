<?php

class Txerpa {
  
  private $username;
  private $password;
  private $base_url;
  private $curl;
  
  public function __construct()
  {
    $this->username = 'username';
    $this->password = 'secret';
    $this->base_url = 'http://api.txerpa.com/api';
    $this->curl = new Curl();
    $this->curl->setAuth($this->username, $this->password);
    $this->curl->headers['Content-Type'] = 'application/json';
  }
  
  /**
   * La consulta es realizada por semejanza y no es sensible a
   * mayúsculas/minúsculas (ilike) excepto con el campo id, que
   * se realiza por igualdad.
   * 
   * @param   string  $field: puede ser uno de: city, vat, name, phone, mobile, fax o id.
   * @param   string  $value: Es la cadena de texto a utilizar en la consulta.
   *
   * @retuns  Array   an array of clients matching the criteria.
   * @throws  TxerpaException
   */
  public function clientSearch($field, $value)
  {
    try {
      $response = $this->get('/client/', array('key' => $field, 'q' => $value));
    } catch (TxerpaException $e) {
      if (404 != $e->getCode()) throw $e;
      return array();
    }
    $data = json_decode($response->body);
    return (array) $data->clients;
  }

  /**
   * Nos permite obtener rápidamente un cliente por su CIF.
   * El formato del CIF tiene que ser: CCXXXXXXXXX donde CC es el codigo del
   * pais y XXXXXXXXX es el CIF, por ejemplo: ES12345678Z.
   * 
   * @param   string  $cif: puede ser uno de: city, vat, name, phone, mobile, fax o id.
   *
   * @retuns  Array   an array of clients matching the criteria.
   * @throws  TxerpaException
   */  
  public function clientByCIF($cif)
  {
    try {
      $response = $this->get('/cif/'.$cif);
    } catch (TxerpaException $e) {
      if (404 != $e->getCode()) throw $e;
      return array();
    }
    $data = json_decode($response->body);
    return (array) $data->clients;
  }
  
  /**
   * El único campo requerido es name, se pueden usar los siguientes campos:
   * name, vat, comment, fax, contact_name, phone, mobile, email, zip,
   * country_id, street, street2, city
   *
   * - Si vat contienen un CIF válido, el cliente es marcado como "sujeto a IVA".
   * - El cliente se marca como activo por defecto.
   * - Para obtener un valor de country_id se puede usar el API de País detallado mas adelante.
   * - A los clientes dados de alta con el API de Txerpa se les asigna la categoria de empresa: "API TXERPA"
   *
   * @param   array  $client_data
   *
   * @retuns  int    The new client ID
   * @throws  TxerpaException
   */    
  public function clientNew(array $client_data)
  {
    $response = $this->post('/client/', $client_data);
    $data = json_decode($response->body);
    return (int) $data->id;
  }
  
  /**
   * La consulta es realizada por semejanza y no es sensible a
   * mayúsculas/minúsculas (ilike) excepto con el campo id, que es realizada
   * por igualdad.
   *
   * @param   string  $field puede ser uno de: number, date_invoice, invoice_number, name, origin o id.
   * @param   string  $value Es la cadena de texto a utilizar en la consulta.
   *
   * @retuns  Array   an array of invoices matching the criteria.
   * @throws  TxerpaException
   */
  public function invoiceSearch($field, $value)
  {
    try {
      $response = $this->get('/invoice/', array('key' => $field, 'q' => $value));
    } catch (TxerpaException $e) {
      if (404 != $e->getCode()) throw $e;
      return array();
    }
    $data = json_decode($response->body);
    return (array) $data->invoices;    
  }
  
  /**
   * Si existe factura con el id indicado devuelve el contenido del PDF
   * codificado como base64 o un código 404 en caso contrario.
   *
   * @param   string  $id id de la factura a recuperar.
   *
   * @retuns  string|null the base64 representation of the pdf file.
   * @throws  TxerpaException
   */
  public function invoicePDF($id)
  {
    try {
      $response = $this->get('/invoice/'.$id.'/pdf');
    } catch (TxerpaException $e) {
      if (404 != $e->getCode()) throw $e;
      return null;
    }
    $data = json_decode($response->body);
    return $data->base64;
  }
  
  /**
   * Los campos requeridos son name, client_id y lines pero se pueden usar los
   * siguientes campos: name, client_id, lines, is_paid.
   * El campo lines es una colección de lineas para la factura, donde cada
   * linea require los siguientes campos: product_id y quantity, pero están
   * disponibles los siguientes: product_id, quantity, discount, note,
   * invoice_product_unit_price, invoice_product_name y invoice_product_description.
   *
   * - Las facturas creadas con el API Txerpa tienen como origen "API TXERPA".
   * - Los campos invoice_product_unit_price, invoice_product_name y invoice_product_description
   *   son utilizados para sobreescribir el precio, nombre y descripción del producto para la
   *   linea de factura actual, es decir: si se usan estos campos se ignora el valor almacenado
   *   en el producto.
   * - Si el campo is_paid es True, la factura será pagada y conciliada automáticamente.
   */
  public function invoiceNew(array $invoice_data)
  {
    $response = $this->post('/invoice/', $invoice_data);
    $data = json_decode($response->body);
    return (array) $data;
  }
    
  /**
   * La consulta es realizada por semejanza y no es sensible a mayúsculas/minúsculas (ilike)
   * excepto con el campo id, que se realiza por igualdad.
   * Si no se pasa ningún parámetro esta consulta devuelve todos los productos.
   *
   * @param   string  $field puede ser uno de: id, name, list_price, description.
   * @param   string  $value Es la cadena de texto a utilizar en la consulta.
   *
   * @retuns  Array   an array of invoices matching the criteria.
   * @throws  TxerpaException
   */  
  public function productSearch($field=null, $value=null)
  {
    $response = $this->get('/invoice/', array('key' => $field, 'q' => $value));
    $data = json_decode($response->body);
    return (array) $data->products;
  }
  
  /**
   * La consulta es realizada por semejanza y no es sensible a mayúsculas/minúsculas (ilike)
   * excepto con el campo id, que se realiza por igualdad.
   *
   * @param   string  $field puede ser uno de: id, name, code.
   * @param   string  $value Es la cadena de texto a utilizar en la consulta.
   *
   * @retuns  Array   an array of countries matching the criteria.
   * @throws  TxerpaException
   */
  public function countrySearch($field=null, $value=null)
  {
    if (array(null, null) == array($field, $value))
    {
      return $this->countriesAll();
    }
    
    $response = $this->get('/country/', array('key' => $field, 'q' => $value));
    $data = json_decode($response->body);
    return (array) $data->countries;
  }
  
  /**
   * Obtención de todas las monedas: Nos permite recuperar el listado completo
   * de monedas de Txerpa.
   * 
   * @retuns  array   All the currencies.
   * @throws  TxerpaException
   */      
  public function currenciesAll()
  {
    $response = $this->get('/monedas/');
    $data = json_decode($response->body);
    return (array) $data->monedas;
  }

  /**
   * Nos permite recuperar el listado completo de paises de Txerpa.
   */  
  private function countriesAll()
  {
    $response = $this->get('/countries/');
    $data = json_decode($response);
    return (array) $data->countries;
  }

  private function request($method, $url, $data=array())
  {
    switch (strtoupper($method))
    {
      case 'GET':
        $response = $this->curl->get($this->base_url.$url, $data);
        break;
      case 'POST':
        $response = $this->curl->post($this->base_url.$url, $data, 'application/json');
        break;
      default:
        $response = $this->curl->request($method, $this->base_url.$url, $data);
    }
    
    if (200 != $response->headers['Status'])
    {
      throw new TxerpaException($response->body, $response->headers['Status']);
    }
    return $response;
  }
  
  private function get($url, $data=array())
  {
    return $this->request('GET', $url, $data);
  }
  
  private function post($url, $data=array())
  {
    return $this->request('POST', $url, json_encode($data));
  }
  
}
