<?php
	//Programmer: Shane B. (Xenland)
	//Date: Nov, 9th 2012
	//Purpose: To provide a drop-in library for php programmers that are not educated in the art of financial security and programming methods.
	
	//Define some Bitcoin client configuration settings
	$btcclient["https"] = "http"; //HTTPS is recommended....
	$btcclient["host"] = "localhost"; //Just the domainname don't put Http:// or https:// that is already taken care of.
	$btcclient["user"] = "username";
	$btcclient["pass"] = "password";
	$btcclient["port"] = 4367;
	
	//Include the JSON-RPC PHP script (Used for querying Bitcoind)
	include("jsonRPCClient.php");
	
	
	/*
	=================================================
	Begin Function(s)
	*/
	
	
		/*
			bitcoin_open_connection()
			Purpose: Setsup what we need nessecary to open a Bitcoin connection
		*/
		function bitcoin_open_connection(){
			global $btcclient;
			
			//Define local/private variables
			$output["return_status"] = -1;
			$output["connection"] = null;
			
			/*  Return status codes
				-1 = Failure to open connection
				1 = Success
			*/
				//Make sure we have all nessecary information to make a connection
				if($btcclient["host"] != '' && $btcclient["user"] != '' && $btcclient["pass"] != '' && $btcclient["https"] != '' && $btcclient["port"] > 0){
					//Open connection
					try{
						$output["connection"] = new jsonRPCClient($btcclient["https"].'://'.$btcclient["user"].':'.$btcclient["pass"].'@'.$btcclient["host"].':'.$btcclient["port"].'/');
					}catch(Exception $e){
						$output["return_status"] = -1;
						$output["connection"] = null;
					}
					
					if($output["connection"] != null && $output["connection"] != false){
						//Return success code
						$output["return_status"] = 1;
					}
				}
			
			
			return $output;
		}
	
		/*
			bitcoin_generate_new_address()
			Purpose: query Bitcoin and return address, returns -1 if no valid address was able to create
			
			Parameter(s) Explaination
			$new_address_label: If set (not required) will label this address with the set text, usefull for tracking #ids or cookies relationships, etc....
		*/
		function bitcoin_generate_new_address($new_address_label=''){
			
			//Define local/private variables
			$output["return_status"] = -1;
			$output["new_address"] = null;
			
			/* Return status codes
				-1 = Failure to generate address
				1 = Success (Address generated)
				
				100 = Failure to connect to Bitcoin client
				101 = Address is too long or too short
			*/
			
			//Open Bitcoin connection
				$new_btcclient_connection = bitcoin_open_connection();
				
			//Bitcoin connection open?
				if($new_btcclient_connection["return_status"] == 1){
					//Yes BTC client has been successfully opened
						//Attempt to query a new address....
						$tmp_new_address = '';
						try{
							$tmp_new_address = $new_btcclient_connection["connection"]->getnewaddress($new_address_label);
						}catch(Exception $e){
							$tmp_new_address = '';
						}
						
						
						if(strlen($tmp_new_address) > 20){
							//Strlen looks okay
							$output["return_status"] = 1;
							
							$output["new_address"] = $tmp_new_address;
						}else{
							//This address is too long or too short, either way its invalid, failure!
							$output["return_status"] = 101;
						}
				}else{
					//Report that we had connection issues
					$output["return_status"] = 100;
				}
				
			return $output;
		}	
	
	
	//Example Execution Below
	var_dump(bitcoin_generate_new_address('YAY'));
?>