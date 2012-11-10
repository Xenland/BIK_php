<?php
	//Programmer: Shane B. (Xenland)
	//Date: Nov, 9th 2012
	//Purpose: To provide a drop-in library for php programmers that are not educated in the art of financial security and programming methods.
	
	//Define BFWDK settings
	$bfwdk_settings["hash_type"] = "sha256"; //What should the hash() function use?
	
	//Define some Bitcoin client configuration settings
	$btcclient["https"] = "http"; //HTTPS is recommended....
	$btcclient["host"] = "localhost"; //Just the domainname don't put Http:// or https:// that is already taken care of.
	$btcclient["user"] = "username";
	$btcclient["pass"] = "password";
	$btcclient["port"] = 4367;
	
	//Define Integrity checks (checksum details)
	$bfwdk_integrity_check = 'TypeALongRandomStringHere'; //Generate a random string that is atleast 4096 characters long, Random number here:  http://textmechanic.com/Random-String-Generator.html
	
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

//--------------------------------------------------------------------------------------------------------------
//			HIGH LEVEL FUNCTIONS
//--------------------------------------------------------------------------------------------------------------
	
	
	/*
			bitcoin_generate_reciept()
			Purpose: Queries Bitcoin for a new address and labels that address with various reciept data to keep track of it.
			
			Parameter(s) Explaination
			$amount_due: This should be expressed in satoshi. For one Bitcoin 100000000 should be entered in.
	*/
	function bitcoin_generate_reciept($amount_due=0){
		global $bfwdk_integrity_check, $bfwdk_settings;
		
		//Define local/private variables
			$output["return_status"] = -1;
			$output["new_address"] = null;
			$output["checksum"] = null;
			
			/* Return status codes
				-1 = Failure to generate reciept
				1 = Success (Address generated)
				
				100 = Failure to connect to Bitcoin client
				101 = Address is too long or too short
				102 = Failure to generate address
			*/
			
			//Sanatize variables
				//amount_due
				$amount_due = intval($amount_due);
				
				if($amount_due <= 0){
					$amount_due = 0;
				}
				
			/* 
				Attempt to generate an address 
				and label that address with the required reciept information
			*/
				
			//Generate a new address with the label being the reciept details. Label format as follows: (timestamp of creation | satoshi due | timestamp when balance confirmed | checksum )
				$new_address_timestamp_of_creation = time();
				$new_address_label_checksum = hash($bfwdk_settings["hash_type"], $new_address_timestamp_of_creation."|".$amount_due);
				$new_address_label = $new_address_timestamp_of_creation."|".$amount_due."|".$new_address_label_checksum;
				
				$new_address_status = bitcoin_generate_new_address($new_address_label);
				
				if($new_address_status["return_status"] == 1){
					//Success!
						$output["new_address"] = $new_address_status["new_address"];
						$output["checksum"] = $new_address_label_checksum;
						
						//Report success
						$output["return_status"] = 1;
						
				}else if($new_address_status["return_status"] == -1){
					//Failed to generate an address
					$output["return_status"] = 102;
				}else if($new_address_status["return_status"] == 100){
					//Failed to connect to Bitcoin client
					$output["return_status"] = 100;
				}else if($new_address_status["return_status"] == 101){
					//Address failed to validate
					$output["return_status"] = 101;
				}else if($new_address_status["return_status"] != 1){
					//All else fails...
					$output["return_status"] = -1;
				}
				
		return $output;
	}

/*********************************************************************
	Clear Checksum memory to prevent any scripts out side this one. This is to prevent any attempts to inject checksum data into the labels and hacking confirmed reciepts status or anything of that nature.
*********************************************************************/
	$bfwdk_integrity_check = '00000000000000000000000000000000000000000000000000000000';
//Uncomment below to see this baby in action :)
//var_dump(bitcoin_generate_reciept(100));
?>