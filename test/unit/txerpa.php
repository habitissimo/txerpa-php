<?php

class TxerpaTest extends ztest\UnitTestCase {
  
    private $client_id;
    private $invoice_id;
    
    function setup() {
        $this->txerpa = new Txerpa();
    }
    
    function test_client_not_found() {
        $data = $this->txerpa->clientSearch('id', -1);
        ensure(is_array($data));
        assert_empty($data);
    }
    
    function test_client_create() {
      $this->client_id = $this->txerpa->clientNew(array(
        'name'          => 'Batman',
        'contact_name'  => 'Bruce Wayne',
        'email'         => 'batman@robin.com',
        'zip'           => 07121,
        'country_id'    => 1,
        'street'        => 'Wayne Tower',
        'city'          => 'Gotham City',
      ));
      assert_equal(gettype($this->client_id), 'integer');
    }
    
    function test_client_created_exists() {
      $data = $this->txerpa->clientSearch('id', $this->client_id);
      ensure(is_array($data));
      assert_not_empty($data);
      assert_equal($data[0]->id, $this->client_id);
    }
    
    function test_invoice_create() {
      $data = $this->txerpa->invoiceNew(array(
        'name'      => 'Factura Batmóvil',
        'client_id' => $this->client_id,
        'is_paid'   => false,
        'lines'     => array(
          array(
            'product_id'  => 1,
            'quantity'    => 1,
            'note'        => 'Pintado en negro',
            'invoice_product_name'        => 'Batmóvil',
            'invoice_product_description' => 'transporte que Batman utiliza comúnmente para desplazarse en Gotham City.',
            'invoice_product_unit_price'  => 1000000,
          ),
        )
      ));
      
      assert_equal(gettype($data), 'array');
      assert_not_empty($data);
      assert_equal(gettype($data['id']), 'integer');
      assert_equal(gettype($data['number']), 'string');
      $this->invoice_id = $data['id'];
    }
    
    function test_invoice_created_exists() {
      $invoice = $this->txerpa->invoiceById($this->invoice_id);
      assert_not_null($invoice);
      assert_equal($invoice->id, $this->invoice_id);
      assert_equal($invoice->lines[0]->name, 'Batmóvil');
    }
    
    function test_invoice_pdf()
    {
      $data = $this->txerpa->invoicePDF($this->invoice_id);
      $invoice_path = '/tmp/factura.pdf';
      file_put_contents($invoice_path, base64_decode($data));
      assert_equal(file_exists($invoice_path), true);
      assert_not_equal(filesize($invoice_path), 0);
      unlink($invoice_path);
    }
    
}
