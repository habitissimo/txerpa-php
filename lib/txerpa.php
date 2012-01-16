<?php

class Txerpa {
  
  private $username;
  private $password;
  private $base_url;
  private $curl;
  
  public function __construct()
  {
    // load config.php if not loaded (this could happen if the class was autoloaded)
    if (!defined('TXERPAAPI_BASEURL')) {
      require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config.php';
    }
    
    $this->username = TXERPAAPI_USERNAME;
    $this->password = TXERPAAPI_PASSWORD;
    $this->base_url = TXERPAAPI_BASEURL;
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
   * @retuns  StdClass|null   client matching the criteria or null.
   * @throws  TxerpaException
   */  
  public function clientByCIF($cif)
  {
    try {
      $response = $this->get('/client/'.$cif.'/');
    } catch (TxerpaException $e) {
      if (404 != $e->getCode()) throw $e;
      return null;
    }
    $data = json_decode($response->body);
    return $data->client;
  }
  
  /**
   * El único campo requerido es name, se pueden usar los siguientes campos:
   * name, vat, comment, fax, contact_name, phone, mobile, email, zip,
   * country_id, street, street2, city, fiscal_position_id
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
   * Uses the same parameters as clientNew.
   * Overwrites the existing client data with the values of $client_data,
   * all non defined values already set on the client will update to Null
   * if not defined in the $client_data.
   *
   * @param   array $client_data
   *
   * @retuns  integer the client ID.
   * @throws  TxerpaException
   */
  public function clientUpdate(array $client_data)
  {
    assert(key_exists('id', $client_data));
    $response = $this->put('/client/', $client_data);
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
   * Returns the invoice for an id.
   * This is just a wrapper for invoiceSearch.
   * 
   * @param   integer $id la id de factura.
   *
   * @retuns  StdClass|null the matching invoice.
   * @throws  TxerpaException
   */
  public function invoiceById($id)
  {
    $invoices = $this->invoiceSearch('id', $id);
    if (!$invoices) {
      return null;
    }
    return $invoices[0];
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
   * invoice_product_unit_price, invoice_product_name, invoice_product_description,
   * date_invoice ( yyyy-mm-dd ), journal_code, invoice_number.
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
   * Nos permite recuperar el listado completo de paises de Txerpa.
   * 
   * @retuns  Array   an array of countries matching the criteria.
   * @throws  TxerpaException
   */  
  public function countryAll()
  {
    $response = $this->get('/countries/');
    $data = json_decode($response);
    return (array) $data->countries;
  }
  
  /**
   * Hace una búsqueda en las posiciones fiscales existentes.
   * La consulta es realizada por semejanza y no es sensible a mayúsculas/minúsculas (ilike)
   * excepto con el campo id, que se realiza por igualdad.
   *
   * @param   string  $field puede ser uno de: id, name.
   * @param   string  $value es la cadena de texto a utilizar en la consulta.
   *
   * @retuns  Array   an array of countries matching the criteria.
   * @throws  TxerpaException
   */
  public function fiscalPositionSearch($field, $value)
  {
    $response = $this->get('/posiciones_fiscales/', array('key' => $field, 'q' => $value));
    $data = json_decode($response->body);
    return (array) $data->fiscal_positions;
  }
  
  /**
   * Obtención de todas las monedas: Nos permite recuperar el listado completo
   * de monedas de Txerpa.
   * 
   * @retuns  array   All the currencies.
   * @throws  TxerpaException
   */      
  public function currencyAll()
  {
    $response = $this->get('/monedas/');
    $data = json_decode($response->body);
    return (array) $data->monedas;
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
      case 'PUT':
        $response = $this->curl->put($this->base_url.$url, $data, 'application/json');
        break;      
      default:
        $response = $this->curl->request($method, $this->base_url.$url, $data);
    }
    
    if (200 != $response->headers['Status'])
    {
      $msg = sprintf('%s: %s', strtoupper($method), $url);
      if ('POST' == strtoupper($method))
      {
        $msg .= "\nPOST_DATA: ".str_replace('\"', '"', json_encode($data));
      }
      $msg .= "\n\nResponse body:\n".$response->body;
      throw new TxerpaException($msg, $response->headers['Status']);
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
  
  private function put($url, $data=array())
  {
    return $this->request('PUT', $url, json_encode($data));
  }
  
}
