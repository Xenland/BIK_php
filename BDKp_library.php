<?php
/*
	Programmer: Shane B. (Xenland)
	Date: Nov, 9th 2012
	Purpose: To provide a drop-in library for php programmers that are not educated in the art of financial security and programming methods.
	Last Updated in Version: 0.0.x
	Donation Bitcoin Address: 13ow3MfnbksrSxdcmZZvkhtv4mudsnQeLh
	Website: http://bitcoindevkit.com
	
	License (AGPL)
		"Bitcoin Development Kit" (also referred to as "BDK", "BDKp", "BDKP", "BDK PHP", or "BDK for PHP") is free software: 
		you can redistribute it and/or modify it under the terms of the Affero General Public License 
		as published by the Free Software Foundation, either version 3 of the License, or
		(at your option) any later version.

		BDK is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		Affero General Public License for more details.

		You should have received a copy of the Affero General Public License
		along with BDKp.  If not, see http://www.gnu.org/licenses/agpl-3.0.html
*/
	/*
	=================================================
	Begin loading necessary dependencies to run this script (Probubly don't need to touch this, just make sure you know what your doing)
	*/
		//Include configuration file required to connect to Bitcoind
		include("config.php");
		
		//Include the JSON-RPC PHP script (Used for querying Bitcoind)
		include("dependencies/jsonRPCClient.php");
	
	
	
	
	/*
	=================================================
	Begin Defining Runtime variables. (Don't configure these variables unless you absolutly are sure you know what you are doing!!!)
	*/
		//Set error_reporting for this page
		//error_reporting(0);
		error_reporting(E_ERROR | E_WARNING | E_PARSE | E_COMPILE_ERROR); //Used for temporary use for developers to turn on/off (Remember to comment this before commiting or it won't be approved if you change this value!)
		
	
	
	
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
						$output["connection"] = new jsonRPCClient($btcclient["https"].'://'.$btcclient["user"].':'.$btcclient["pass"].'@'.$btcclient["host"].':'.$btcclient["port"]);
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
			bitcoin_set_tx_fee()
			Purpose: to set the transaction fees
		*/
		function bitcoin_set_tx_fee($amount_in_satoshi=00000000){
			
			//Define local/private variables
			$output["return_status"] = -1;
		
			
			/* Return status codes
				-1 = Failure to set run set tx fee function
				1 = Success (Tx fee set)
				
				100 = Failure to connect to Bitcoin
				101 = Seting TX fee failed.
			*/
			
			//Filter incomming input
			$amount_in_satoshi = floor($amount_in_satoshi);
			
			$amount_in_bitcoins = 0.00000000;
			$amount_in_bitcoins = $amount_in_satoshi * 100000000;
			$amount_in_bitcoins = round($amount_in_bitcoins, 8);
			
			//Open Bitcoin connection
				$new_btcclient_connection = bitcoin_open_connection();
				
			//Bitcoin connection open?
				if($new_btcclient_connection["return_status"] == 1){
					//Yes BTC client has been successfully opened
						//Attmpt to settxfee
						$set_tx_fee_return_status = '';
						try{
							$set_tx_fee_return_status = $new_btcclient_connection["connection"]->settxfee($amount_in_satoshi);
						}catch(Exception $e){
							$set_tx_fee_return_status = '';
						}
						
						if($set_tx_fee_return_status == "true"){
							//Successfully set
							$output["return_status"] = 1;
						}else{
							//Setting tx fee failed for some reason.
							$output["return_status"] = 101;
						}
						
				}else{
					//Failure to connect to Bitcoin
					$output["return_status"] = 100;
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
									$output["pubkey"] = $tmp_valid_bitcoin_address["pubkey"];

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
			global $bdk_integrity_check, $bdk_settings;
			
			//Define local/private variables
			$output["return_status"]			= -1;
			$output["address_label"]			= '';
			$output["checksum"]			= '';
			$output["checksum_match"]		= -1; // -1=Unknown; 0=False; 1= Success, Checksum good
			$output["amount_due_in_satoshi"]	= 0; //Amount due (according to the label and checksum verification)
			$output["timestamp_generated"]	= 0; //Timestamp upon when the customer created the receipt (according to label and checksum verification)
			$output["products_in_receipt"]	= Array();
			
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

						//Decode json into PHP array
						$unverified_receipt_information = Array();
						$unverified_receipt_information = json_decode($tmp_address_label, true);

						//Save checksum, then remove the checksum from the json information and verify
						$tmp_store_checksum = $unverified_receipt_information["checksum"];
					
						//Temporarily clear the checksum value to verify checksum
						$unverified_receipt_information["checksum"] = '';
						
						//Make runtime hash/checksum
						$receipt_data_checksum = hash($bdk_settings["hash_type"], json_encode($unverified_receipt_information));
						
						//Compare runtime hash /checksum with the loaded hash/checksum
						if($tmp_store_checksum == $receipt_data_checksum){
							//Looks like the checksum is valid, extract values from json label (Don't forget to put back the checksum in the array)
							$unverified_receipt_information["checksum"] = $tmp_store_checksum;
							
							$output["checksum"]			= $tmp_store_checksum;
							$output["checksum_match"]		= 1;
							$output["timestamp_generated"]	= (int) $unverified_receipt_information["timestamp_generated"];
							$output["amount_due_in_satoshi"]	= (int) intval($unverified_receipt_information["amount_due_in_satoshi"]);
							$output["products_in_receipt"]	= $unverified_receipt_information["products_in_receipt"];
							
							//Return status success
							$output["return_status"] = 1;
						}else{
							//Return status fail
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
			bitcoin_verify_message()
			Purpose: query Bitcoin and verify the message associated with this bitcoin address and signatures.
			
			Usage(s):
					Contract Usage Example:
						Lets say user A writes out a contract, the developer should take the direct input and base64_encode()
						The dev should then output the base64_encoded() string, perferrably in a textarea or text input box
						The dev should ask the user to sign the base64_encoded() string and submit the signature to another different text input box
						The dev should take the arguments and plug them into bitcoin_verify_message and if it is valid then accept the contract as signed
						
					Login Authentication System
						The visiting user wants to anonymously login with the identity of a Bitcoin address
						The website generates a long random string (perferably 512 characters or more but 4096 should be the most before its over kill)
						The website outputs this long random string in a base64_encoded() format and displays it to the user
						The visitor then signs the base64_encoded() message and then sends the signature to the website
						The website then verifys the signature is matched if matched then create a session for this visitor.
						
		*/
		function bitcoin_verify_message($bitcoin_address='', $signature='', $message='', $enable_consistency_filter=1){
			global $bdk_integrity_check, $bdk_settings;
			
			//Define local/private variables
			$output["return_status"] = -1;
			$output["message_valid"] = -1; // -1 not changed; 0 message not vaild; 1 message valid;
			$output["original_message"] = '';
			$output["filtered_message"] = '';
			
			/* Return status codes
				-1 = Failure to verify address
				1 = Success
				
				100 = Bitcoin connection was unable to be established
				101 = Query failed
			*/
			
			//Define local only variables
				$output["original_message"] = $message;
				
				//Apply a filter which will help promtoe consistancy across all browsers
				if($enable_consistency_filter == 1){
					//Filter, goaled at making it act consistant across Browsers, OSystem, etc
					$filtered_message = bdk_encode_message($message);
				}else{
					//Don't filter
					$filtered_message = $message;
				}
			
			//Open Bitcoin connection
				$new_btcclient_connection = bitcoin_open_connection();
			
			//Bitcoin connection open?
				if($new_btcclient_connection["return_status"] == 1){
					$tmp_verifymessage_status = '';
					$tmp_command_success = 0;
					
					try{
						$tmp_verifymessage_status = $new_btcclient_connection["connection"]->verifymessage($bitcoin_address, $signature, $filtered_message);
						$tmp_command_success = 1;
					}catch(Exception $e){
						$tmp_verifymessage_status = '';
						$tmp_command_success = 0;
					}
					
					if($tmp_command_success == 1){
						if($tmp_verifymessage_status == 1){
							$output["message_valid"] = 1;
						}else{
							$output["message_valid"] = 0;
						}
						
						$output["filtered_message"] = $filtered_message;
						
						//Success
						$output["return_status"] = 1;
					}else{
						//Command failed
						$output["return_status"] = 101;
					}
					
					
				}else{
					//Connection to Bitcoin failed
					$output["return_status"] = 100;
				}
				
			return $output;
		}
		
		
		
		/*
			bitcoin_list_transactions()
			Purpose: query Bitcoin and return all transactions
		*/
		function bitcoin_list_transactions($account='*', $count=9999999999999, $from=0){
			global $bdk_integrity_check, $bdk_settings;
			
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
			global $bdk_integrity_check, $bdk_settings;
			
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
		
		
		
		/*
			bitcoin_sendfrom()
			Purpose: query Bitcoin and send Bitcoins from an account/address to the specified address
		*/
		function bitcoin_sendfrom($bitcoin_address_label='', $send_to_bitcoin_address='', $amount_in_satoshi=00000000, $minimum_confirmations=1){
			global $bdk_integrity_check, $bdk_settings;
			
			//Define local/private variables
			$output["return_status"] = -1;
			$output["tx_id"] = '';
			
			$output["error_rpc_message"] = '';

			/* 
				Return status codes
				-1 = Failure to run script (This shouldn't be taken litterly but basically nothing was ran)
				1 = Success 
				
				100 =  Bitcoin connection failed
				101 = Command failed
			*/
			
			//Open Bitcoin connection
				$new_btcclient_connection = bitcoin_open_connection();
				
			//Bitcoin connection open?
				if($new_btcclient_connection["return_status"] == 1){
					//Opened a connection to Bitcoin
					
					
					$tmp_verifymessage_status = '';
					$tmp_command_success = 0;
					
					try{
						$tmp_verifymessage_status = $new_btcclient_connection["connection"]->sendfrom($bitcoin_address_label, $send_to_bitcoin_address, $amount_in_satoshi, $minimum_confirmations, '', '');
						$tmp_command_success = 1;
					}catch(Exception $e){
						
						$tmp_command_success = 0;
					}
					
					if($tmp_command_success == 1){
						//Success
						$output["return_status"] = 1;
						
						//Return tx_id
						$output["tx_id"] = $tmp_verifymessage_status;
						
					}else{
						//Failure to execute command
						$output["return_status"] = 101;
						$output["error_rpc_message"] = $tmp_verifymessage_status;
					}
				}else{
					//Connection to Bitcoin failed
					$output["return_status"] = 100;
				}
			
			return $output;
		}
		
		
		/*
			bitcoin_sendmany()
			Purpose: query Bitcoin and send Bitcoins to many Bitcoins addresses...
			
			Bitcoin API: <fromaccount> {address:amount,...} [minconf=1] [comment] 
		*/
		function bitcoin_sendmany($bitcoin_address_label='', $send_to_bitcoin_address = Array(), $minimum_confirmations=1, $comment=''){
			global $bdk_integrity_check, $bdk_settings;
			
			//Define local/private variables
			$output["return_status"] = -1;
			
			$output["error_rpc_message"] = '';

			/* 
				Return status codes
				-1 = Failure to run script (This shouldn't be taken litterly but basically nothing was ran)
				1 = Success 
				
				100 =  Bitcoin connection failed
				101 = Command failed
			*/
			
			//Open Bitcoin connection
				$new_btcclient_connection = bitcoin_open_connection();
				
			//Bitcoin connection open?
				if($new_btcclient_connection["return_status"] == 1){
					//Opened a connection to Bitcoin
					$tmp_verifymessage_status = '';
					$tmp_command_success = 0;
					
					try{
						$tmp_verifymessage_status = $new_btcclient_connection["connection"]->sendmany($bitcoin_address_label, $send_to_bitcoin_address, $minimum_confirmations, $comment);
						$tmp_command_success = 1;
					}catch(Exception $e){
						$tmp_command_success = 0;
					}
					
					
					if($tmp_command_success == 1){
						//Success
						$output["return_status"] = 1;
					}else{
						//Failure to execute command
						$output["return_status"] = 101;
						$output["error_rpc_message"] = $tmp_verifymessage_status;
					}
					
				}else{
					$output["return_status"] = 100;
				}
				
			return $output;
		}
		
		
		
		/*
			bitcoin_get_transaction()
			Purpose: query Bitcoin and get information about the requested transaction.
			
			Bitcoin API: gettransaction <txid>
				"amount" : total amount of the transaction
				"confirmations" : number of confirmations of the transaction
				"txid" : the transaction ID
				"time" : time the transaction occurred
				"details" - An array of objects containing:
					"account"
					"address"
					"category"
					"amount"
					"fee" 
		*/
		function bitcoin_get_transaction($tx_id=''){
			global $bdk_integrity_check, $bdk_settings;
		
			//Define local/private variables
			$output["return_status"] = -1;
			
			$output["tx_info"]["amount"] = (double) 0.00000000;
			$output["tx_info"]["fee"] = (double) 0.00000000;
			$output["tx_info"]["confirmations"] = (int) 0;
			$output["tx_info"]["blockhash"] = (string) '';
			$output["tx_info"]["blockindex"] = (int) 0;
			$output["tx_info"]["blocktime"] = (int) 0;
			$output["tx_info"]["txid"] = (string) '';
			$output["tx_info"]["time"] = (int) 0;
			$output["tx_info"]["timereceived"] = (int) 0;
			
			$output["tx_info"]["details"]["account"] = (string) '';
			$output["tx_info"]["details"]["address"] = (string) '';
			$output["tx_info"]["details"]["category"] = (string) '';
			$output["tx_info"]["details"]["amount"] = (double) 0.00000000;
			$output["tx_info"]["details"]["fee"] = (double) 0.00000000;

			/* 
				Return status codes
				-1 = Failure to run script (This shouldn't be taken litterly but basically nothing was ran)
				1 = Success 
				
				100 =  Bitcoin connection failed
				101 = 
			*/
			
			//Open Bitcoin connection
				$new_btcclient_connection = bitcoin_open_connection();
				
			//Bitcoin Connection Open
			if($new_btcclient_connection["return_status"] == 1){
				//Opened a connection to Bitcoin
					$tmp_tx_info = '';
					$tmp_command_success = 0;
					
					try{
						$tmp_tx_info = $new_btcclient_connection["connection"]->gettransaction($tx_id);
						$tmp_command_success = 1;
					}catch(Exception $e){
						$tmp_command_success = 0;
					}
					
					if($tmp_command_success == 1){
						//Success
						$output["return_status"] = 1;
						
						//Set variables
						$output["tx_info"]["amount"] = (double) $tmp_tx_info["amount"];
						$output["tx_info"]["fee"] = (double) $tmp_tx_info["fee"];
						$output["tx_info"]["confirmations"] = (int) $tmp_tx_info["confirmations"];
						$output["tx_info"]["blockhash"] = (string) $tmp_tx_info["blockhash"];
						$output["tx_info"]["blockindex"] = (int) $tmp_tx_info["blockindex"];
						$output["tx_info"]["blocktime"] = (int) $tmp_tx_info["blocktime"];
						$output["tx_info"]["txid"] = (string) $tmp_tx_info["txid"];
						$output["tx_info"]["time"] = (int) $tmp_tx_info["time"];
						$output["tx_info"]["timereceived"] = (int) $tmp_tx_info["timereceived"];
						

						$output["tx_info"]["details"]["account"] = (string) $tmp_tx_info["details"][0]["account"];
						$output["tx_info"]["details"]["address"] = (string) $tmp_tx_info["details"][0]["address"];
						$output["tx_info"]["details"]["category"] = (string) $tmp_tx_info["details"][0]["category"];
						$output["tx_info"]["details"]["amount"] = (double) $tmp_tx_info["details"][0]["amount"];
						$output["tx_info"]["details"]["fee"] = (double) $tmp_tx_info["details"][0]["fee"];
						
					}else{
						//Failure to execute command
						$output["return_status"] = 101;
					}
			}else{
				$output["return_status"] = 100;
			}
			
			return $output;
		}
		
		
		
		
					
		
		/*
		^^^^^^^^^^^ ULTRA-LOW-LEVEL-FUNCTIONS ^^^^^^^^^^^
		*/
		
		
			/*
				bdk_encode_message()
				Purpose: Dummy function to promote consistancy with code. For example if this library is realeased and it is found that base64_encode() dosen't do the job right and we need to change it, a dev can just upload an update with out any issues(other than the expected signature verification incompatiblities)
			*/
			function bdk_encode_message($plain_text_string){
				return base64_encode($plain_text_string);
			}
			
			/*
				bdk_decode_message()
				Purpose: Dummy function to promote consistancy with code. For example if this library is realeased and it is found that base64_encode() dosen't do the job right and we need to change it, a dev can just upload an update with out any issues(other than the expected signature verification incompatiblities)
			*/
			function bdk_decode_message($plain_text_string){
				return base64_decode($plain_text_string);
			}
			
			/*
				bdk_verify_checksum()
				Purpose: a simple function to call for verifying a checksum with its inputted contents
			*/
			function bdk_verify_checksum($original_string='', $checksum_string='', $checksum_algo=''){
				global $bdk_settings;
				
				//Define local variables
				$output = 0;
				
				//Clean incomming variable(s)
				if($checksum_algo == ''){
					//Set checksum algo to the default (Set in config.php)
					$checksum_algo = $bdk_settings["hash_type"];
				}
				
				$original_hash = hash($checksum_algo, $original_string);
				
				if($checksum_string == $original_hash){
					$output = 1;
				}else{
					$output = 0;
				}
				
				return $output;
			}
			
			/*
				bdk_generate_random_string()
				Purpose: Generates a length of random text
			*/
			function bdk_generate_random_string($length=4096){
				global $bdk_settings;
				
				
				//Define local variables
				$output["return_status"] = -1;
				$output["random_string"] = '';
				$output["infinite_loop_fault_detected"] = 0; //This is helpfull for debugging or extra "checK" if the generation function did its job
				
				/* 
					Return status codes
					-1 = Failure to run script (This shouldn't be taken litterly but basically nothing was ran)
					1 = Success 
					
					100 =  Failed to generate the target length
				*/
				
				$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
				
				//Begin random generation
				$continue_generating = 1;
					
				/* Prevent Infinite Loops by making sure the value is changing and in the correct direction */
				$last_length = 0;
				$last_length_iteration = 0;
					
				while($continue_generating == 1){
					$string = '';
					for ($i = 0; $i < 1024; $i++) {
						$string .= $characters[rand(0, strlen($characters) - 1)];
					}
					
					//Hash new random strings until we have more then enough characters
					$output["random_string"] .= hash($bdk_settings["hash_type"], $string);
					
					
					/* Prevent Infinite loops from ever happening by comparing previous values */
					if(strlen($output["random_string"]) >= $length){
						//Tell generator to stop scince we have the target length
						$continue_generating = 0;
					}
					
					//Check if the length has changed (Only if we should continue generating though)
					if($continue_generating == 1 && $last_length_iteration >= 2){
						if($last_length >= strlen($output["random_string"])){
							//We have detected a possible infinite loop, break the while() statement
							$continue_generating = 0;
							
							$output["infinite_loop_fault_detected"] = 1;
						}
					}
					
					
					//Get length and set it as the last length
					$last_length = strlen($output["random_string"]);
					
					//Do iterations....
					$last_length_iteration++;
				}
				
				//Strip all characters past the target amount...
				$output["random_string"] = substr($output["random_string"], 0, $length);
				
				//Check if this is the target length before returning as success
				if(strlen($output["random_string"]) == $length){
					$output["return_status"] = 1;
				}else{
					$output["return_status"] = 100;
				}
				
				return $output;
			}

//--------------------------------------------------------------------------------------------------------------
//			HIGH LEVEL FUNCTIONS
//--------------------------------------------------------------------------------------------------------------
	
	
	/*
			bdk_generate_receipt()
			Purpose: Queries Bitcoin for a new address and labels that address with various receipt data to keep track of it.
			
			Parameter(s) Explaination
			$amount_due: This should be expressed in satoshi. For one Bitcoin 100000000 should be entered in.
			$product_id_array: This has to be an array regardless of key/pair data/value count
	*/
	function bdk_generate_receipt($amount_due_in_satoshi = 0, $product_id_array = Array()){
		global $bdk_integrity_check, $bdk_settings;
		
		//Define local/private variables
			$output["return_status"] = -1;
			$output["new_address"] = null;
			$output["checksum"] = null;
			
			/* Return status codes
				-1 = Failure to generate receipt
				1 = Success (Address generated)
				
				100 = Failure to connect to Bitcoin client
				101 = Address is too long or too short
				102 = Failure to generate address
			*/
			
			//Define receipt data array
			$receipt_data_checksum					= '';
			$receipt_data_json_encoded				= '';
			$receipt_data							= Array();
			$receipt_data["checksum"]				= ''; //This is blank before checksum is created, that way we can verify it securly later on.
			$receipt_data["timestamp_generated"]		= time();
			$receipt_data["amount_due_in_satoshi"]	= (int) 0;
			$receipt_data["products_in_receipt"]		= Array();
			
			
			//Sanatize variables
				$amount_due_in_satoshi = (int) floor(intval($amount_due_in_satoshi));
				
				//Apply limits to $amount_due_in_satoshi to 0 and 21 mill
					if($amount_due_in_satoshi <= 0){
						$amount_due_in_satoshi = 0;
					}
				
				//Loop through all product ids in $product_id_address and convert them to integers
					$num_product_ids_in_array = count($product_id_array);
					
					//while($a = 0; $a < $num_product_ids_in_array; $a++){
					//	$product_id_array[$i] = floor($product_id_array[$i]);
					//}

				//Assign product ids to the $receipt_data array
				$receipt_data["products_in_receipt"] = $product_id_array;
				
				//JSON Encode
				$receipt_data_json_encoded = json_encode($receipt_data);

				//Generate a checksum (Then amend the encoded json data with the checksum)
				$receipt_data_checksum = hash($bdk_settings["hash_type"], $receipt_data_json_encoded);
				
				//Amend the receipt array to contain the checksum (Then update the json encoded variable)
				$receipt_data["checksum"] = $receipt_data_checksum;
				
				//Update the JSON Encoded variables
				$receipt_data_json_encoded = json_encode($receipt_data);

				//Attempt to generate a new address with the receipt details as the label
				$new_address_status = bitcoin_generate_new_address($receipt_data_json_encoded);
				
				if($new_address_status["return_status"] == 1){
					//Success!
						$output["new_address"] = $new_address_status["new_address"];
						$output["checksum"] = $receipt_data_checksum;
						
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
			bdk_get_receipt_information()
			Purpose: Queries a Bitcoin address, collects as much data as it can, then outputs what it knows
			
			Parameter(s) Explaination
	*/
	function bdk_get_receipt_information($bitcoin_address=''){
		global $bdk_integrity_check, $bdk_settings;
		
		//Define local/private variables
			$output["return_status"]			= -1;
			$output["checksum"]			= '';
			$output["timestamp_generated"]	= (int) 0;
			$output["amount_due_in_satoshi"]	= (int) 0;
			$output["products_in_receipt"]	= Array();
			
		/* Return status codes
			-1 = Failure to collect information on the receipt
			1 = Success (Information was successfully retrieved)
			
			100 = Bitcoin address was not set, with out the address we can't retrieve any Bitcoin information
			101 = Checksum didn't match don't trust any information associated with this receipt (Besides the obvious security of the block chain, like the current balance and the address is okay to trust)
			103 = Failure to connect to bitcoin clinet
			104 = get address label function failed;
		*/		
		if($bitcoin_address != ''){
			//A Bitcoin address has been set, lets attempt to query all receipt information
			$bitcoin_label_information = bitcoin_get_address_label($bitcoin_address);

			if($bitcoin_label_information["return_status"] == 1 && $bitcoin_label_information["checksum_match"] == 1){
				//Extract receipt information from label function
				
				$output["checksum"]			= $bitcoin_label_information["checksum"];
				$output["products_in_receipt"]	= $bitcoin_label_information["products_in_receipt"];
				$output["timestamp_generated"]	= $bitcoin_label_information["timestamp_generated"];
				$output["amount_due_in_satoshi"]	= $bitcoin_label_information["amount_due_in_satoshi"];
				
				//Return status success
				$output["return_status"]	= 1;
				
			}else{
				//Checksum didn't match output error
				
				if($bitcoin_label_information["return_status"] == -1){
					$output["return_status"] = -1;
				}else if($bitcoin_label_information["return_status"] == 100){
					$output["return_status"] = 103;
				}else if($bitcoin_label_information["return_status"] == 101){
					$output["return_status"] = 104;
				}else if($bitcoin_label_information["return_status"] == 102){
					$output["return_status"] = 101;
				}
			}	
		}else{
			//No bitcoin address was set
			$output["return_status"] = 100;
		}
			
			
		return $output;
	}
	
	
	/*
			bdk_login_with_coin_address()
			Purpose: 
			
			Notes: Please note that this function is only secure depending on how many bits of entropy your $bdk_integrity_check variable including string length should be atleast 4096.
			
			Parameter(s) Explaination
			$bitcoin_address: This is the address the visitor/user is requesting to verify their identity with.
			$step: This is the step the user is one, step 1 will generate a random string to "sign" with the Bitcoin.org Client (Satoshi Client)
			$step_2_signature: This is the signature the visitor/user provides
			$step_2_original_data: This is data the server provided (required to do a comparison with out a database)
	*/
	function bdk_login_with_coin_address($bitcoin_address='', $step=1, $step_2_signature='', $step_2_original_data=''){
		global $bdk_integrity_check, $bdk_settings;
		
		//Define local/private variables
		$output["return_status"]	= -1;
		$output["bitcoin_address_authenticated"] = 0;
		$output["string_to_sign"] = '';
		
		/* Return status codes
			-1 = Failure to collect information on the receipt
			1 = Success
			
			100 = Connection to Bitcoin failed
			101 = Creation of random string failed.
			102 = Inputted server checksum dosen't match the local server check sum. Tell user to try again we can't trust this information if the server checksum dosen't match the data.
			103 = The signature may be valid but this token is expired, tell the user to try again, and get a new token to sign.
			104 = Bitcoin address was not set, with out the address we can't retrieve any Bitcoin information
			105 = (Same as 104 only different for debugging purposes)  Bitcoin address was not set, with out the address we can't retrieve any Bitcoin information
			106 = message did not validate, signature should not be trusted
		*/
		if($bitcoin_address != ''){
			//Check if this Bitcoin address is valid before expending the resources to generate a random string/checking, etc
			$bitcoin_validation_status = bitcoin_validate_address($bitcoin_address);
	
			if($bitcoin_validation_status["return_status"] == 1 && $bitcoin_validation_status["isvalid"] == 1){
				//This Bitcoin address is valid, what did we want to do now that we know this?
				if($step == 1){
					//Generate a random string for the non-authenticated user to sign and send back to us
					$random_string_request = bdk_generate_random_string(256);

					if($random_string_request["return_status"] == 1){
						//Random string created!
						$random_string = $random_string_request["random_string"];
						
						//Sync time
						$current_time_sync = time(); //We set in a variable so all time references are the same during code-execution.
						
						//Server Checksum
						$server_checksum = hash($bdk_settings["hash_type"], hash($bdk_settings["hash_type"], hash($bdk_settings["hash_type"], $current_time_sync.$random_string.$bdk_integrity_check.$bitcoin_address)));
						
						//String to sign
						$string_to_sign = bdk_encode_message("The following is to prove ownership of the address of '".$bitcoin_address."' and in no way, shape or form is it a legal binding contract. |".$current_time_sync."|".$server_checksum."|".$random_string."|".$bitcoin_address);
						
						//Return string to sign
						$output["string_to_sign"] = $string_to_sign;
						
						$output["return_status"] = 1;
						
					}else{
						//Creation of random string failed.
						$output["return_status"] = 101;
					}
					
				}else if($step == 2){
					//Validate information
					
					//First decode the incomming $step_2_original_data
					$step_2_decoded_data = bdk_decode_message($step_2_original_data);
					
					//Second split decoded data so we can do some integrity checks
					$step_2_decoded_data_split = explode("|", $step_2_decoded_data);
					
					/*
					step_2_decoded_data_split Table
					[0] = original message
					[1] = Time stamp
					[2] = Server Checksum
					[3] = Random String
					[4] = Bitcoin Address attempting to authenticate
					*/
					
					//Create a serverside checksum based on the provided information
					$server_checksum = hash($bdk_settings["hash_type"], hash($bdk_settings["hash_type"], hash($bdk_settings["hash_type"], $step_2_decoded_data_split[1].$step_2_decoded_data_split[3].$bdk_integrity_check.$step_2_decoded_data_split[4])));

					//See if the server checksum matches with the
					if($server_checksum == $step_2_decoded_data_split[2]){
						//So far soo good the data is intact, now we must verify that the Bitcoin signature is valid with the data
						
						$valid_message_status = bitcoin_verify_message($bitcoin_address, $step_2_signature, $step_2_decoded_data);
						
						if($valid_message_status["return_status"] == 1){
							//A valid message! But is this token expired?
							if((time() - $step_2_decoded_data_split[1]) <= $bdk_settings["coin_authentication_timeout"]){
								//Consider the user authenticated!
								$output["return_status"] = 1;
								$output["bitcoin_address_authenticated"] = 1;
							}else{
								//Token has expired, the user must generate another one.
								$output["return_status"] = 103;
							}
							
						}else{
							//Not a valid message
							$output["return_status"] = 106;
						}
						
					}else{
						//The provided server checksum dosen't match the servers checksum
						$output["return_status"] = 102;
					}
				}
				
			}else{
				//This Bitcoin address isn't valid
				$output["return_status"] = 104;
			}
			
		}else{
			$output["return_status"] = 105;
		}
		
		return $output;
	}
	
	
	
	
	
	/** ** **
		Cart Functions
	** ** **/
	
	/*
		bdk_cart_generate_checksum()
		
	*/
	function bdk_cart_generate_checksum($token){
		global $bdk_integrity_check, $bdk_settings;
		
		$output["return_status"]	= - 1;
		$output["checksum"] = '';
		
		/* Return status codes
			-1 = Failure to collect information on the receipt
			1 = Success
			
			100 = no token provided (no string provided)
			101 = Raw url decode failed
			102 = base64 decode failed
			103 = json decode failed
		*/
		
		if($token != ''){
			//Rawurldecode
			$rawurl_encoded_token = $token;
			
			//Raw url decode (output is base64)
			if($base64_encoded_token = rawurldecode($rawurl_encoded_token)){
			
				//Base64 decode (output is plaintext json)
				if($plaintext_json_token = base64_decode($base64_encoded_token)){
				
					//String to PHP Array
					if($token_array = json_decode($plaintext_json_token, true)){
					
						//Set checksum to blank (just in case)
						$token_array["checksum"] = '';
						
						//Create a new token
						$new_token				= Array();
						$new_token["random_token"]	= '';
						$new_token["product_id_list"] = Array();
						$new_token["checksum"]	= '';
						
						//Set new tokens' information
						$new_token["random_token"] = $token_array["random_token"];
						$new_token["product_id_list"] = $token_array["product_id_list"];
						$new_token["checksum"] = '';
						
						//Encode token for the checksum
						$checksum_of_new_token_tmp = json_encode($new_token);
						$checksum_of_new_token_tmp = base64_encode($checksum_of_new_token_tmp);
						$checksum_of_new_token_tmp = rawurlencode($checksum_of_new_token_tmp);
						
						//Define checksum
						$output["checksum"]= hash($bdk_settings["hash_type"], $checksum_of_new_token_tmp);

						//Successfully generated a checksum
						$output["return_status"] = 1;
					}else{
						//Json decode failed
						$output["return_status"] = 103;
					}
				}else{
					//Base 64 decode faile
					$output["return_status"] = 102;
				}
			}else{
				//Raw url decode failed.
				$output["return_status"] = 101;
			}
		}else{
			//Json token now set
			$output["return_status"] = 100;
		}
		
		return $output;
	}
	
	
	/*
		bdk_validate_cart()
		
	*/
	function bdk_validate_cart($token){
		global $bdk_integrity_check, $bdk_settings;
		
		$output["return_status"]	= - 1;
		$output["isvalid"] = 0;
		
		/* Return status codes
			-1 = Failure to collect information on the receipt
			1 = Success
			
			100 = no token provided (no string provided)
		*/
		
		if($token != null && $token != ''){
		
			//Generate a serverside checksum
			$serverside_checksum = bdk_cart_generate_checksum($token);
			
			
			//Extract the clientside checksum to compare against...
			$tmp_token = $token;
			if($tmp_token = rawurldecode($tmp_token)){
				if($tmp_token = base64_decode($tmp_token)){
					if($tmp_token = json_decode($tmp_token, true)){
							
							//Define clientside checksum
							$clientside_checksum = $tmp_token["checksum"];
							
						//Do compare
						if($clientside_checksum == $serverside_checksum["checksum"]){
							//Is valid
							$output["isvalid"] = 1;
							
							//Successfull function run.
							$output["return_status"] = 1;
						}else{
							//NOT valid
							$output["isvalid"] = 0;
							
							//Successfull function run
							$output["return_status"] = 1;
						}
					}else{
						//Token failed to json_decode (Consider Invalid)
						$output["isvalid"] = 0;
						
						//Successfull run
						$output["return_status"] = 1;
					}
				}else{
					//Token failed to base64_decode (Consider invalid)
					$output["isvalid"] = 0;
					
					//Successful run
					$output["return_status"] = 1;
				}
			}else{
				//Token failed to rawurldecode (Consider invalid)
				$output["isvalid"] = 0;
				
				$output["return_status"] = 1;
			}
		}else{
			//Token was not set
			$output["isvalid"] = 0;
			
			//Successfull function run
			$output["return_status"] = 1;
		}
		
		
		return $output;
	}
	
	
	/*
			bdk_start_cart()
			Purpose: 
			
			Parameter(s) Explaination
	*/
	function bdk_start_cart(){
		global $bdk_integrity_check, $bdk_settings;
		
		$output["return_status"]	= - 1;
		$output["token"]	= '';
		
		/* Return status codes
			-1 = Failure to collect information on the receipt
			1 = Success
			
			100 = Random string failed to generate
		*/

		//Generate a random token id
		$generate_random_token_id = bdk_generate_random_string(512);
		
		if($generate_random_token_id["return_status"] == 1){
			//Generate receipt json token.
			$receipt = Array();
			$receipt["random_token"]	= '';
			$receipt["product_id_list"]	= Array();
			$receipt["checksum"]		= '';
			
			//Random token successfully generated attach it to the new receipt
			$receipt["random_token"] = $generate_random_token_id["random_string"];
			
			//Make a temporary json string for generating a checksum
			$tmp_json_string = json_encode($receipt);
			$tmp_json_string = base64_encode($tmp_json_string);
			$tmp_json_string = rawurlencode($tmp_json_string);
			
				//Generate checksum
				$tmp_checksum = hash($bdk_settings["hash_type"], $tmp_json_string);
				
				//Add checksum to receipt
				$receipt["checksum"] = $tmp_checksum;
				
			//output the json token
			$output["token"] = json_encode($receipt);
			$output["token"] = base64_encode($output["token"]);
			$output["token"] = rawurlencode($output["token"]);

			//Generation of everything looks good
			$output["return_status"] = 1;
		}else{
			//Generation of random token id failed
			$output["return_status"] = 100;
		}
		
		return $output;
	}
	
	
	/*
		bdk_get_cart_info()
	
	*/
	function bdk_get_cart_info($token, $auto_fix=0){
		global $bdk_integrity_check, $bdk_settings;
		
		$output["return_status"]	= - 1;
		$output["product_id_list"] = Array();
		$output["token"] = '';
		$output["plaintext_token"] = '';
		
		/* Return status codes
			-1 = Failure to collect information on the receipt
			1 = Success
			
			100 = Token isn't valid.
			101 = Failed to generate a new cart
		*/
		
		//Validate cart first
		$validate_cart = bdk_validate_cart($token);
		if($validate_cart["return_status"] == 1 && $validate_cart["isvalid"] == 1){
			//Token looks valid, lets retireve the information
			
			//Relay token..
			$output["token"] = $token;
			
			//Convert string into PHP array
			$tmp_token = $token;
			$tmp_token = rawurldecode($tmp_token);
			$tmp_token = base64_decode($tmp_token);
			$output["plaintext_token"] = $tmp_token; //Define plaintext token.
			$tmp_token = json_decode($tmp_token, true);
			
			//Define product id list
			$output["product_id_list"] = $tmp_token["product_id_list"];
			
			//Success
			$output["return_status"] = 1;

		}else{
			//Cart token isn't valid
			if($auto_fix == 0){
				//Token is invalid
				$output["return_status"] = 100;
			}else{
				//Generate a new cart
				$new_cart = bdk_start_cart();
				
				if($new_cart["return_status"] == 1){
					$output["token"] = $new_cart["token"];
					$output["product_id_list"] = Array();
					$output["plaintext_token"] = base64_decode(rawurldecode($new_cart["token"]));
					
					//Success
					$output["return_status"] = 1;
				}else{
					$output["return_status"] = 101;
				}
			}
		}
		
		return $output;
	}
	
	
	/*
		bdk_add_to_cart()
	*/
	function bdk_add_to_cart($token, $product_id, $auto_fix=0){
		global $bdk_integrity_check, $bdk_settings;
		
		$output["return_status"] = -1;
		$output["token"] = '';
		
		/* Return status codes
			-1 = Failure to collect information on the receipt
			1 = Success
			
			100 = Retrieving of cart information failed.
			101 = generationg of checksum failed.
			102 = Failed to encode into json string.
			103 = failed to base64 encdoe.
			104 = failed to raw url encode.
		*/
		
		//Get decoded token
		
		$cart_info = bdk_get_cart_info($token, $auto_fix);
		if($cart_info["return_status"] == 1){
			//Retrieving of cart information succeeded, now attempt to add to the receipt
			
			$token_as_array = json_decode($cart_info["plaintext_token"], true);

			//Add to product id list
			array_push($token_as_array["product_id_list"], (int)$product_id);
			
			//Generate new receipt
			$receipt = Array();
			$receipt["random_token"] = '';
			$receipt["product_id_list"] = Array();
			$receipt["checksum"] = '';
			
			$receipt["random_token"] = $token_as_array["random_token"];
			$receipt["product_id_list"] = $token_as_array["product_id_list"];
			
			//Attempt to generate checksum
			$tmp_json_string = rawurlencode(base64_encode(json_encode($receipt)));
			$tmp_checksum = bdk_cart_generate_checksum($tmp_json_string);

			if($tmp_checksum["return_status"] == 1){
				$receipt["checksum"] = $tmp_checksum["checksum"];
				
				//Json encode the receipt
				if($output["token"] = json_encode($receipt)){
					//Base64 the receipt
					if($output["token"] = base64_encode($output["token"])){
						//Raw url encode
						if($output["token"] = rawurlencode($output["token"])){
							$output["return_status"] = 1;
						}else{
							$output["return_status"] = 104;
						}
					}else{
						$output["return_status"] = 103;
					}
				}else{
					//Failed to encode into json string
					$output["return_status"] = 102;
				}
			}else{
				$output["return_status"] = 101;
			}
		}else{
			//Retrieving of cart information failed
			$output["return_status"] = 100;
		}
		
		return $output;
	}
	
	
/*********************************************************************
	Clear Checksum memory to prevent any scripts out side this one from tampering with checksums. (this memory clearing dosen't prevent scripts from opening up the config in a text file and reading the checksum)
*********************************************************************/
	$bdk_integrity_check = '000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000';
/********************* END CLEAR CHECKSUM MEMORY *************/

?>