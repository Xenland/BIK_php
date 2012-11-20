<?php
/*
	Programmer: Shane B. (Xenland)
	Date: Nov, 9th 2012
	Purpose: To provide a drop-in library for php programmers that are not educated in the art of financial security and programming methods.
	Last Updated in Version: 0.0.x
	Bitcoin Address: 13ow3MfnbksrSxdcmZZvkhtv4mudsnQeLh
	Website: http://bitcoindevkit.com
	
	License (AGPL)
		"Bitcoin Financial Web Development Kit" (also referred to as "BFWDK") is free software: 
		you can redistribute it and/or modify it under the terms of the Affero General Public License 
		as published by the Free Software Foundation, either version 3 of the License, or
		(at your option) any later version.

		BFWDK is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		Affero General Public License for more details.

		You should have received a copy of the Affero General Public License
		along with BFWDK.  If not, see http://www.gnu.org/licenses/agpl-3.0.html
*/
	/*
	=================================================
	Begin loading necessary dependencies to run this script (Probubly don't need to touch this, just make sure you know what your doing)
	*/
	
		//Include configuration file required to connect to Bitcoind
		include("./config.php");
		
		//Include the JSON-RPC PHP script (Used for querying Bitcoind)
		include("./dependencies/jsonRPCClient.php");
	
	
	
	
	/*
	=================================================
	Begin Defining Runtime variables. (Don't configure these variables unless you absolutly are sure you know what you are doing!!!)
	*/
		//Get default error reporting (This will help prevent altering the developers intended error_reporting level, but also provide the error_reporting nessecary at this level of runtime)
		$current_error_reporting = error_reporting();	
		
		//Set error_reporting for this page
		error_reporting(0);
		//error_reporting(E_ERROR | E_WARNING | E_PARSE); //Used for temporary use for developers to turn on/off (Remember to comment this before commiting or it won't be approved if you change this value!)
	
	
	
	
	/*
	=================================================
	Begin Defining Function(s)
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
				101 = Address is invalid
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
						
						
						$generated_address_is_valid = bitcoin_validate_address($tmp_new_address);
						if($generated_address_is_valid["isvalid"] == 1){
							//Strlen looks okay
							$output["return_status"] = 1;
							
							$output["new_address"] = $tmp_new_address;
							
						}else{
							//This address is invalid
							$output["return_status"] = 101;
						}
				}else{
					//Report that we had connection issues
					$output["return_status"] = 100;
				}
				
			return $output;
		}
		
		
		
		/*
			bitcoin_validate_address()
			Purpose: query Bitcoin and detect if string is a valid Bitcoin address, returns -1 if no valid address was detected
		*/
		function bitcoin_validate_address($bitcoin_address=''){
		
			
			
			//Define local/private variables
			$output["return_status"] = -1;
			$output["isvalid"] = 0; //(Binary)
			$output["ismine"] = 0; //(Binary)
			$output["isscript"] = 0; //(Binary)
			$output["pubkey"] = ''; //(String)
			$output["iscompressed"] = 0; //(Binary)
			$output["label"] = ''; //(String)
			
			/* Return status codes
				-1 = Failure to validate address
				1 = Success (Command was successfully executed, This Does not mean this is a valid address!, It just means you can trust the returned information is true)
				
				100 = Failure to connect to Bitcoin
			*/
			
			//Before attempting to open Bitcoin for querying check if the Bitcoin address was set
			if($bitcoin_address != ''){
				
				//Open Bitcoin connection
					$new_btcclient_connection = bitcoin_open_connection();
					
				//Bitcoin connection open?
					if($new_btcclient_connection["return_status"] == 1){
						//Yes BTC client has been successfully opened
							//Attempt to query Bitcoin if this is a valid Bitcoin address
							$tmp_command_executed = 0; // (Binary)
							try{
								$tmp_valid_bitcoin_address = $new_btcclient_connection["connection"]->validateaddress($bitcoin_address);
								$tmp_command_executed = 1;
								
							}catch(Exception $e){
								$tmp_command_executed = 0;
							}
							
							
							if($tmp_command_executed == 1){
								$output["return_status"] = 1;
								
								if($tmp_valid_bitcoin_address["isvalid"] == 1){
									//This is a valid address report it as such
									$output["return_status"] = 1;
									
									//set isvalid
									$output["isvalid"] = 1;
									
									//Set ismine
									if($tmp_valid_bitcoin_address["ismine"] == 1){
										$output["ismine"] = 1;
									}else{
										$output["ismine"] = 0;
									}
									
									//Set isscript
									if($tmp_valid_bitcoin_address["isscript"] == 1){
										$output["isscript"] = 1;
									}else{
										$output["isscript"] = 0;
									}
									
									
									//Set pubkey
									$output["pubkey"] = strip_tags($tmp_valid_bitcoin_address["pubkey"]);
									
									//Set iscompressed
									if($tmp_valid_bitcoin_address["iscompressed"] == 1){
										$output["iscompressed"] = 1;
									}else{
										$output["iscompressed"] = 0;
									}
									
									//Set label (A developer should use Bitcoin_Get_Address_Label to aquire the Unmodifed version of the label)
 									$output["label"] = strip_tags($tmp_valid_bitcoin_address["account"]);
									
								}else{
									//This is not a valid address report it as such
									$output["return_status"] = 1;
									$output["isvalid"] = 0;
								}
								
							}else if($tmp_command_executed == 0){
								$output["return_status"] = -1;
							}

					}else{
						//Report that we had connection issues
						$output["return_status"] = 100;
					}
					
			}else{
				//Bitcoin address not set | Invalid return error
				$output["return_status"] = 1;
				
				$output["isvalid"] = 0;
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
			$output["amount_due_in_satoshi"] = 0; //Amount due (according to the label and checksum verification)
			$output["creation_of_receipt_timestamp"] = 0; //Timestamp upon when the customer created the reciept (according to label and checksum verification)
			
			/* Return status codes
				-1 = Failure to generate address
				1 = Success (Address label successfully retrieved)
				
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
						
							$output["amount_due_in_satoshi"] = intval($tmp_address_label_split[1]);
							$output["creation_of_receipt_timestamp"] = intval($tmp_address_label_split[0]);
							
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
		
		
		
		/*
			========================= This function isn't ready for use yet =========================
			bitcoin_list_transactions()
			Purpose: query Bitcoin and return all transactions
		*/
		function bitcoin_list_transactions($account='', $count=9999999999999, $from=0){
			global $bfwdk_integrity_check, $bfwdk_settings;
			
			//Define local/private variables
			$output["return_status"] = -1;
			$output["transaction_list"] = null;
			
			/* Return status codes
				-1 = Failure to generate address
				1 = Success 
				
				100 = Failure to connect to Bitcoin client
			*/
			
			//Open Bitcoin connection
				$new_btcclient_connection = bitcoin_open_connection();
				
			//Bitcoin connection open?
				if($new_btcclient_connection["return_status"] == 1){
					$output["transaction_list"] = $new_btcclient_connection["connection"]->listtransactions($account, $count, $from);
				}else{
					//Connection to Bitcoin failed
					$output["return_status"] = 100;
				}
			
			return $output;
		}
		
		
		
		/*
			bitcoin_get_received_by_address()
			Purpose: query Bitcoin and return the total overall acumulated Bitcoins for this account
			Notes:
				With out a Bitcoin address this function fails with error at the Bitcoind level so we produce a software error
		*/
		function bitcoin_get_received_by_address($bitcoin_address='', $minimum_confirmations=1){
			global $bfwdk_integrity_check, $bfwdk_settings;
			
			//Define local/private variables
			$output["return_status"] = -1;
			$output["total_received_in_satoshi"] = (int) 0; //Integers only
			$output["total_received_in_bitcoin"] = (double) 0.00000000; //Decimal/Float/Double (THIS IS FOR ONLY DISPLAYING THE TOTAL RECEIVED BALANCE IN BITCOIN , NOT FOR DOING MATH AGAINST!!! Do math in satoshi only)
			
			/* Return status codes
				-1 = Failure to run script (This shouldn't be taken litterly but basically nothing was ran)
				1 = Success 
				
				100 = Failure to connect to Bitcoin client
				101 = Failure to retrieve balance
				102 = Invalid Bitcoin address was set
			*/
			
			//Sanatize incomming parameters
				$bitcoin_address = strip_tags($bitcoin_address); //I went with strip_tags for now, all I can think of is perhaps someone enables error reporting somehow and made the Bitcoin address into a XSS/XSRF attack made up of a string of javavscript)
				$minimum_confirmations = (int) floor($minimum_confirmations); //Make integer(if for some reason it came in as a decimal)
			
			//Create a floor limit of zero
				if($minimum_confirmations <= 0){
					$minimum_cofirmations = 0;
				}
				
				
				
			//Before, connecting to bitcoin make sure that "Bitcoin address" is indeed set, scince we know for sure Bitcoin will error upon invalid Bitcoin address
			if($bitcoin_address != ''){
				
				//Open Bitcoin connection
					$new_btcclient_connection = bitcoin_open_connection();
					
				//Bitcoin connection open?
					if($new_btcclient_connection["return_status"] == 1){
						//Verify this is a valid Bitcoin address
						$tmp_is_valid_address = bitcoin_validate_address($bitcoin_address);
						
						if($tmp_is_valid_address["isvalid"] == 1){
							//Define command executed
							$tmp_command_executed = 0; // (Binary)
							try{
								$tmp_total_received_in_bitcoin = $new_btcclient_connection["connection"]->getreceivedbyaddress($bitcoin_address, $minimum_confirmations);
								$tmp_command_executed = 1;
							}catch(Exception $e){
								$tmp_command_executed = 0;
							}
							
						
							//Looks like the command was successfully queried, lets continue with execution of the rest of this function
							if($tmp_command_executed == 1){
								if($tmp_total_received_in_bitcoin >= 0){
									//Looks like a success!
									$output["return_status"] = 1;
									
									//Return the values....
									$output["total_received_in_bitcoin"] = (double) $tmp_total_received_in_bitcoin; 
									$output["total_received_in_satoshi"] = (int) floor($tmp_total_received_in_bitcoin * 100000000); //Convert Bitcoins to satoshi so we can do math with integers.
									
								}else{
									//Failure
									$output["return_status"] = 101;
								}
							}else{
								//Failure to query command from Bitcoind, return failure status
								$output["return_status"] = 101;
							}
						}else{
							//Bitcoin address is invalid
							$output["return_status"] = 102;
						}
						
					}else{
						//Connection to Bitcoin failed
						$output["return_status"] = 100;
					}
			}else{
				//Bitcoin address not set, return software error
				$output["return_status"] = 102;
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
	function bitcoin_generate_receipt($amount_due_in_satoshi = 0, $product_id_array = Array()){
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
				$amount_due_in_satoshi = (int) intval($amount_due_in_satoshi);
				
				//Apply limits to $amount_due_in_satoshi to 0 and 21 mill
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
	Clear Checksum memory to prevent any scripts out side this one from tampering with checksums, and to verify label information is true.
*********************************************************************/
	$bfwdk_integrity_check = '00000000000000000000000000000000000000000000000000000000';
/********************* END CLEAR CHECKSUM MEMORY *************/

/*********************************************************************
	Revert error_reporting back to default before this script executed (To prevent any intended settings to error_reporting to be changed)
**********************************************************************/
	error_reporting($current_error_reporting);
/*********************END REVERTING ERROR REPORTING************/
var_dump(bitcoin_validate_address('1Dge2nbsnsHPmU1qdgBawNijED6n9WsHsZ'));
var_dump(bitcoin_get_received_by_address('1Dge2nbsnsHPmU1qdgBawNijED6n9WsHsZ', 0));
?>