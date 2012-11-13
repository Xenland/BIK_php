<?php
	//Programmer: Shane B. (Xenland)
	//Date: Nov, 9th 2012
	//Purpose: To provide a drop-in library for php programmers that are not educated in the art of financial security and programming methods.
	
	//Define BFWDK settings
	$bfwdk_settings["hash_type"] = "sha256"; //What should the hash() function use?
	
	//Define some Bitcoin client configuration settings
	$btcclient["https"]	= "http"; //HTTPS is recommended....
	$btcclient["host"]	= "127.0.0.1"; //Just the domainname don't put Http:// or https:// that is already taken care of.
	$btcclient["user"]	= "username";
	$btcclient["pass"]	= "password";
	$btcclient["port"]	= "4367";
	
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
		
		
		
		/*
			bitcoin_get_address_label()
			Purpose: query Bitcoin and return the label assigned to the associated address
			
			Parameter(s) Explaination
			$bitcoin_address: this is to query bitcoin for the associated label
			
			Output(s) Explaination
			$output["address_label"] Upon success will be the label associated with the inputed Bitcoin address
		*/
		function bitcoin_get_address_label($bitcoin_address=''){
			global $bfwdk_integrity_check, $bfwdk_settings;
			
			//Define local/private variables
			$output["return_status"] = -1;
			$output["address_label"] = '';
			$output["checksum_match"] = -1; // -1=Unknown; 0=False; 1= Success, Checksum good
			$output["bid_in_satoshi"] = 0; //Amount due (according to the label and checksum verification)
			$output["creation_of_reciept_timestamp"] = 0; //Timestamp upon when the customer created the reciept (according to label and checksum verification)
			
			/* Return status codes
				-1 = Failure to generate address
				1 = Success (Address label successfully retreived)
				
				100 = Failure to connect to Bitcoin client
				101 = Bitcoin was successfully connected to, but for unknown reasons we were unable to query the label..
				102 = Checksum is not a match, don't trust any information
			*/
			//Open Bitcoin connection
				$new_btcclient_connection = bitcoin_open_connection();
				
			//Bitcoin connection open?
				if($new_btcclient_connection["return_status"] == 1){
					//Bitcoin connection successfull, Get address label now...
					$tmp_label_success = 0;
					$tmp_address_label = '';
					
					try{
						$tmp_address_label = $new_btcclient_connection["connection"]->getaccount($bitcoin_address);
						$tmp_label_success = 1;
						
						//Return status success
						$output["return_status"] = 1;
					}catch(Exception $e){
						$tmp_address_label = '';
						
						//Label Retireval was a fail..................
						$output["return_status"] = 101;
					}
					
					//If label retrieval wasn't a success then output the data, otherwise output error status
					if($tmp_label_success == 1){
					
						//Label retrieval was a success.... return data
						$output["address_label"] = $tmp_address_label;
						
						//Return status success
						$output["return_status"] = 1;
						
						/*
							We have successfully retrieved the label, lets check its checksum and report the values
						*/
							$tmp_address_label_split = explode("|", $tmp_address_label);
						
							$output["bid_in_satoshi"] = intval($tmp_address_label_split[1]);
							$output["creation_of_reciept_timestamp"] = intval($tmp_address_label_split[0]);
							
							/*
								Check checksum
								
							*/
							$tmp_checksum =  hash($bfwdk_settings["hash_type"], $tmp_address_label_split[0]."|".$tmp_address_label_split[1]."|".$bfwdk_integrity_check);

							if($tmp_checksum == $tmp_address_label_split[2]){
								//Checksum looks good
								$output["checksum_match"] = 1;
								
							}else{
								//Checksum dosen't look good, MAKE FAIL STATUS!!
								$output["checksum_match"] = 0;
								
								//Checksum didn't checkout, this function is considered false.
								$output["return_status"] = 102;
							}
					}else{
						//Label retrieval wasen't a success....
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
	function bitcoin_generate_receipt($amount_due_in_satoshi=0){
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
				$amount_due_in_satoshi = intval($amount_due_in_satoshi);
				
				if($amount_due_in_satoshi <= 0){
					$amount_due_in_satoshi = 0;
				}
				
			/* 
				Attempt to generate an address 
				and label that address with the required reciept information
			*/
				
			//Generate a new address with the label being the reciept details. Label format as follows: (timestamp of creation | satoshi due | timestamp when balance confirmed | checksum )
				$new_address_timestamp_of_creation = time();
				$new_address_label_checksum = hash($bfwdk_settings["hash_type"], $new_address_timestamp_of_creation."|".$amount_due_in_satoshi."|".$bfwdk_integrity_check);
				$new_address_label = $new_address_timestamp_of_creation."|".$amount_due_in_satoshi."|".$new_address_label_checksum;
				
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
	
	
	
	/*
			bitcoin_get_reciept_information()
			Purpose: Queries a Bitcoin address, collects as much data as it can, then outputs what it knows
			
			Parameter(s) Explaination
			No parmeter(s) just yet
	*/
	function bitcoin_get_reciept_information($bitcoin_address){
		global $bfwdk_integrity_check, $bfwdk_settings;
		
		//Define local/private variables
			$output["return_status"] = -1;
			
		/* Return status codes
			-1 = Failure to collect information on the reciept
			1 = Success (Information was successfully retrieved)
			
			100 = Bitcoin address was not set, with out the address we can't retrieve any Bitcoin information
		*/
		
		if(strlen($bitcoin_address) == 0 || $bitcoin_address == ''){
			//A Bitcoin address has been set, lets attempt to query all reciept information
			$bitcoin_label_information = bitcoin_get_address_label($bitcoin_address);
			
			if($bitcoin_label_information["return_status"] == 1 && $bitcoin_label_information["checksum_match"] == 1){
				//Label & checksum information was successfully outputted, lets query Bitcoin for some reciept information
								
			}
		}else{
			//No bitcoin address was set
			$output["return_status"] = 100;
		}
			
			
		return $output;
	}

/*********************************************************************
	Clear Checksum memory to prevent any scripts out side this one. This is to prevent any attempts to inject checksum data into the labels and hacking confirmed reciepts status or anything of that nature.
*********************************************************************/
	$bfwdk_integrity_check = '00000000000000000000000000000000000000000000000000000000';
//********************* END CLEAR CHECKSUM MEMORY *************/


/*
	Below is some example codes you can uncomment, run, test and view their output.
*/
//var_dump(bitcoin_generate_receipt(100000000));
var_dump(bitcoin_get_address_label('1rA3QqvQXVCFa3tfPK5puxrNSf1SUvxgF'));
?>