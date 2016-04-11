<?php
	
	/*
	* Firstly, go to PagSeguroLibrary/config/PagSeguroConfigWrapper.php
	* and set your data based on your PagSeguro account
	*/
	
	// Just for debug and see what I'm receiving from $_POST
	$ret = file_put_contents('mydata.txt', "\n\n\n----------BEGIN----------", FILE_APPEND | LOCK_EX);
	$ret = file_put_contents('mydata.txt', "\n\nIPN PAGSEGURO = FORM POST => " . date('Y-m-d H:i:s') . " => " . json_encode($_POST), FILE_APPEND | LOCK_EX);
	
	if (isset($_POST['notificationType']) && $_POST['notificationType'] == 'transaction') { // It's a PagSeguro IPN RESPONSE
			
	    $email = 'YOUR_PAGSEGURO_EMAIL';
	    $token = 'YOUR_PAGSEGURO_TOKEN';
	
	    $url = 'https://ws.pagseguro.uol.com.br/v2/transactions/notifications/' . $_POST['notificationCode'] . '?email=' . $email . '&token=' . $token;
	
	    $curl = curl_init($url);
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    $transaction = curl_exec($curl);
	    curl_close($curl);
	
		// Check if handshake return Unauthorized
	    if ($transaction == 'Unauthorized') {
		   	$ret = file_put_contents('mydata.txt', "\n\nIPN PAGSEGURO = UNAUTHORIZED => " . date('Y-m-d H:i:s') . " => " . json_encode($_POST), FILE_APPEND | LOCK_EX);
		   	$ret = file_put_contents('mydata.txt', "\n\n-----------END-----------", FILE_APPEND | LOCK_EX);
			mail('YOUR_EMAIL', 'PAGSEGURO POST - UNAUTHORIZED', print_r($_POST, TRUE));
	        exit;
	    } else { // It's a valid handshake
		    
		    $transaction = simplexml_load_string($transaction);
		    
		    $ret = file_put_contents('mydata.txt', "\n\nIPN PAGSEGURO = AUTHORIZED => " . date('Y-m-d H:i:s') . " => " . json_encode($transaction), FILE_APPEND | LOCK_EX);
			
			$paymentForm = '';
			
			switch ($transaction->paymentMethod->type) {
				case 1:
					$paymentForm = 'Cartão de crédito';
					break;
				case 2:
					$paymentForm = 'Boleto';
					break;
				case 3:
					$paymentForm = 'Débito online (TEF)';
					break;
				case 4:
					$paymentForm = 'Saldo PagSeguro';
					break;
				case 5:
					$paymentForm = 'Oi Paggo';
					break;
				case 6:
					$paymentForm = 'Depósito em conta';
					break;
				default:
					$paymentForm = 'Forma de pagamento desconhecida';
					break;
			}
			
			$paymentStatus = '';
			
			switch ($transaction->status) {
				case 1:
					$paymentStatus = 'Aguardando pagamento';
					break;
				case 2:
					$paymentStatus = 'Em análise';
					break;
				case 3:
					$paymentStatus = 'Paga';
					break;
				case 4:
					$paymentStatus = 'Disponível';
					break;
				case 5:
					$paymentStatus = 'Em disputa';
					break;
				case 6:
					$paymentStatus = 'Devolvida';
					break;
				case 7:
					$paymentStatus = 'Cancelada';
					break;
				default:
					$paymentStatus = 'Forma de pagamento desconhecida';
					break;
			}
				
			// Put your code here for save the $transaction objects variables in database
			// You can see all variables that you can use in https://pagseguro.uol.com.br/v3/guia-de-integracao/api-de-notificacoes.html
			
			if ('INSERTED_IN_DATABASE' == TRUE) {
				if ($paymentStatus == 'Paga') {
					
					// You receive your payment successfuly. Send email to buyer and for you
					
				} else if ($paymentStatus == 'Devolvida') {
					
					// Payment was refunded. Send email to buyer and for you
					
				}
								
			} else {
				$ret = file_put_contents('mydata.txt', "\n\nIPN PAGSEGURO = FALHOU AO INSERIR NO BANCO DE DADOS => " . date('Y-m-d H:i:s'), FILE_APPEND | LOCK_EX);
				mail('YOUR_EMAIL', 'PAGSEGURO POST - FALHOU AO INSERIR NO BANCO DE DADOS', json_encode($transaction));
			}
			
			$ret = file_put_contents('mydata.txt', "\n\n-----------END-----------", FILE_APPEND | LOCK_EX);
			exit;
		}		
	} else if (isset($_POST['amount']) AND isset($_SESSION['user']['code'])) { // It's a PagSeguro REQUEST

		require_once('PagSeguroLibrary/PagSeguroLibrary.php');
		
		// Begin PagSeguro payment request
		$paymentrequest = new PagSeguroPaymentRequest();
		
		// Define the product array, if you have multiple products, just do a foreach into your cart
		$data = Array(
			'id' => $orderAux['code'], // ID of the product
			'description' => 'Depósito no ShoutOuts', // Description of the product
			'quantity' => 1, // Quantity of the product
			'amount' => $_POST['amount'], // Price of the product
		);
		
		$item = new PagSeguroItem($data);
		$paymentrequest->addItem($item);		
		$paymentrequest->setCurrency('BRL');
		 
		// 1 PAC
		// 2 SEDEX
		// 3 NOT_SPECIFIED (Digital Products)
		
		$paymentrequest->setShipping(3);		 
		$credentials = PagSeguroConfig::getAccountCredentials();		 
		$url = $paymentrequest->register($credentials);
		
		$ret = file_put_contents('mydata.txt', "\n\n-----------END-----------", FILE_APPEND | LOCK_EX);
		header('location: ' . $url);
		exit;
		
	} else { // Humm, somethig it's wrong
		$ret = file_put_contents('mydata.txt', "\n\n-----------END-----------", FILE_APPEND | LOCK_EX);
		header('location: SOMEWHERE');
		exit;
	}
	
?>
