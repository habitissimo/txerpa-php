<?php

class TxerpaTest extends ztest\UnitTestCase {
  
    private $client_id;
    private $invoice_id;
    private $non_eu_fiscal_id;
    
    function setup() {
        $this->txerpa = new Txerpa();
    }
    
    function test_client_not_found() {
        $data = $this->txerpa->clientSearch('id', -1);
        ensure(is_array($data));
        assert_empty($data);
    }
    
    function test_client_create_ue() {
      $this->client_id = $this->txerpa->clientNew(array(
        'name'          => 'Batman',
        'contact_name'  => 'Bruce Wayne',
        'email'         => 'bruce@wayne.com',
        'zip'           => 07121,
        'country_id'    => 1,
        'street'        => 'Wayne Tower',
        'city'          => 'Gotham City',
      ));
      assert_equal(gettype($this->client_id), 'integer');
    }
    
    function test_client_search() {
      $data = $this->txerpa->clientSearch('id', $this->client_id);
      ensure(is_array($data));
      assert_not_empty($data);
      assert_equal($data[0]->id, $this->client_id);
    }
        
    function test_invoice_with_tax_create() {
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
            'invoice_product_unit_price'  => 98000,50,
          ),
        )
      ));
      
      assert_equal(gettype($data), 'array');
      assert_not_empty($data);
      assert_equal(gettype($data['id']), 'integer');
      assert_equal(gettype($data['number']), 'string');
      $this->invoice_id = $data['id'];
    }
    
    function test_invoice_created_with_taxes() {
      $invoice = $this->txerpa->invoiceById($this->invoice_id);
      assert_not_null($invoice);
      assert_equal($invoice->id, $this->invoice_id);
      assert_equal($invoice->lines[0]->name, 'Batmóvil');
      assert_not_equal($invoice->amount_untaxed, $invoice->amount_total);
      assert_not_equal(0, (int) $invoice->amount_tax);
    }
    
    function test_get_non_eu_fiscal_position() {
      $data = $this->txerpa->fiscalPositionSearch('name', 'extracomunitario');
      assert_array($data);
      assert_equal(1, count($data));
      $this->non_eu_fiscal_id = $data[0]->id;
    }
    
    function test_client_create_non_ue() {
      $this->client_id = $this->txerpa->clientNew(array(
        'name'                => 'Alfred',
        'contact_name'        => 'Alfred',
        'email'               => 'alfred@wayne.com',
        'zip'                 => 07121,
        'country_id'          => 1,
        'street'              => 'Wayne Tower',
        'city'                => 'Gotham City',
        'fiscal_position_id'  => $this->non_eu_fiscal_id,
      ));
      assert_equal(gettype($this->client_id), 'integer');
    }
    
    function test_invoice_without_tax_create() {
      $data = $this->txerpa->invoiceNew(array(
        'name'      => 'Factura Bat-Boomerang',
        'client_id' => $this->client_id,
        'is_paid'   => false,
        'lines'     => array(
          array(
            'product_id'  => 1,
            'quantity'    => 1,
            'note'        => 'Vuelve cuando lo tiras',
            'invoice_product_name'        => 'Bat-Boomerang',
            'invoice_product_description' => 'arma aturdidora para combatir el crimen.',
            'invoice_product_unit_price'  => 39,90,
          ),
        )
      ));
      assert_equal(gettype($data), 'array');
      assert_not_empty($data);
      assert_equal(gettype($data['id']), 'integer');
      assert_equal(gettype($data['number']), 'string');
      $this->invoice_id = $data['id'];
    }    
    
    function test_invoice_created_without_taxes() {
      $invoice = $this->txerpa->invoiceById($this->invoice_id);
      assert_not_null($invoice);
      assert_equal($invoice->id, $this->invoice_id);
      assert_equal($invoice->lines[0]->name, 'Bat-Boomerang');
      assert_equal($invoice->amount_untaxed, $invoice->amount_total);
      assert_equal(0, (int) $invoice->amount_tax);
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
