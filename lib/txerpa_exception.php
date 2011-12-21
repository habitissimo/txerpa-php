<?php

class TxerpaException extends Exception {

	function __construct( $txerpa_error_message, $response_status_code )
	{
		parent::__construct( $txerpa_error_message, (int) $response_status_code );
	}
    
}