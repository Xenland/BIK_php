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
			
			Parameter(s) Explanation
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
							
	
							
							if($tmp_command_executed == 1 && $tmp_valid_bitcoin_address != null){
								$output["return_status"] = 1;
								
								if($tmp_valid_bitcoin_address["isvalid"] == true){
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
			
			Parameter(s) Explanation
			$bitcoin_address: this is to query bitcoin for the associated label
			
			Output(s) Explanation
			$output["address_label"] Upon success will be the label associated with the inputed Bitcoin address
			
				**The following are only set if the bdk_generate_receipt was used to generate the label**
				$output["checksum"] (The checksum of the whole json label -- Not generally useful outside of this function)
				$output["checksum_match"] (Checksum validility status | -1=Unknown; 0=False; 1= Success, Checksum good)
				$output["amount_due_in_satoshi"] (If the checksum is valid (1) then the amount due in satoshi will be set according to the receipt lable information)
				$output["timestamp_generated"] (If the checksum is valid (1) then timestamp generated will be set according to the server timestamp when the receipt was generated the very firstime)
				$output["products_in_receipt"] (If the checksum is valid (1) this will contain what is intended for "product id" integers or even customer specific cart product ids could be useful here -- Remember to do your own cross validation of the Bitcoin receipts and server databases.)
		*/
		function bitcoin_get_address_label($bitcoin_address=''){
			global $bdk_integrity_check, $bdk_settings;
			
			//Define local/private variables
			$output["return_status"]		= -1;
			$output["address_label"]		= '';
			$output["checksum"]			= '';
			$output["checksum_match"]		= -1; // -1=Unknown; 0=False; 1= Success, Checksum good
			$output["amount_due_in_satoshi"]	= 0; //Amount due (according to the label and checksum verification)
			$output["timestamp_generated"]		= 0; //Timestamp upon when the customer created the receipt (according to label and checksum verification)
			$output["products_in_receipt"]		= Array();
			
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



		
		/**
			bitcoin_verify_message()
			
			
			Purpose
				Query Bitcoin and verify the message associated with this bitcoin address and signatures.
			
			
			Parameter Explanation
				$bitcoin_address | (required) | The address to verify the signature & message against 
				$signature | (required) | The signature to verify the address & message against
				$message | (required) | The message to verify the signature & bitcoin address against
			
			
			Output(s) Explanation
				Return Status
					-1 = Failure to verify address
					 1 = Success

					100 = Bitcoin connection was unable to be established
					101 = Query failed
				
					
				Message Valid
					-1 = Epic Failure
						Notes: This function magically did not run, 
						Reailty as we know it probubly ended if this happened.
						
					 0 = Message was not valid.
					 
					 1 = Message was valid.

		**/
		function bitcoin_verify_message($bitcoin_address='', $signature='', $message=''){
			global $bdk_integrity_check, $bdk_settings;
			
			//Define local/private variables
			$output["return_status"] = -1;
			$output["message_valid"] = -1; // -1 not changed; 0 message not vaild; 1 message valid;
			
			/* Return status codes
				
			*/
			//Open Bitcoin connection
				$new_btcclient_connection = bitcoin_open_connection();
			
			//Bitcoin connection open?
				if($new_btcclient_connection["return_status"] == 1){
					$tmp_verifymessage_status = '';
					$tmp_command_success = 0;
					
					try{
						$tmp_verifymessage_status = $new_btcclient_connection["connection"]->verifymessage($bitcoin_address, $signature, $message);
						$tmp_command_success = 1;
					}catch(Exception $e){
						$tmp_verifymessage_status = '';
						$tmp_command_success = 0;
					}

					if($tmp_verifymessage_status == false || $tmp_verifymessage_status == true){
						//Query to successfully executed
						
						if($tmp_verifymessage_status == true){
							$output["message_valid"] = 1;
						}else if($tmp_verifymessage_status == false){
							$output["message_valid"] = 0;
						}
						
						$output["return_status"] = 1;
					}else{
						//Failed to query for a verifymessage
						$output["return_status"] = 101;
					}
				}else{
					//Connection to Bitcoin failed
					$output["return_status"] = 100;
				}
				
			return $output;
		}
		
		
		
		/**
			bitcoin_list_transactions()
			
			Purpose
				Query Bitcoin and return all transactions
				
			Parameter Explanation
				account | (optional) | If set to star "*" then all addresses fit criteria, other wise set to the bitcoin address label
				  count | (optional) | How many transactions to limit the list to
				   from | (optional) | How many transactions to skip "from" the beginning.
				   
			Output Explanation
				Return Status
					-1 = Epic Failure
						Notes: This function magically did not run, 
						Reailty as we know it probubly ended if this happened.
						
					 1 = Success 
						Notes: If success, then this function ran with out errors and
						all output data is considered "useable".
					
					100 = Failure to connect to Bitcoin client
					101 = Failed to execute command
					
				Transaction List
					
		**/
		function bitcoin_list_transactions($account='*', $count=9999999999999, $from=0){
			global $bdk_integrity_check, $bdk_settings;
			
			//Define local/private variables
			$output["return_status"] = -1;
			$output["transaction_list"] = null;

			
			//Cast/Limit variables
				//Remove non-UTF8 binary data from text string.
				$account = iconv("UTF-8", "UTF-8//IGNORE", $account);
				
				//Cast to int
				$count = (int)$count;
				
				//Limit Floor to $count
				if($count <= 0){
					$count = 1;
				}
				
				//Cast to int
				$from = (int)$from;
			
				//Limit From to $count
				if($from < 0){
					$from = 0;
				}
			
			
			
			//Open Bitcoin connection
				$new_btcclient_connection = bitcoin_open_connection();
				
			//Bitcoin connection open?
				if($new_btcclient_connection["return_status"] == 1){
					$output["transaction_list"] = $new_btcclient_connection["connection"]->listtransactions($account, $count, $from);
					if(is_array($output["transaction_list"])){
						$output["return_status"] = 1;
					}else{
						$output["return_status"] = 101;
					}
				}else{
					//Connection to Bitcoin failed
					$output["return_status"] = 100;
				}
			
			return $output;
		}
		
		
		
		/**
			bitcoin_get_received_by_address()
			
			Purpose
				Query Bitcoin and return the total overall acumulated Bitcoins for this account
			
			Parameter Explanation
					bitcoin_address | (required) | The address you own and want to check the "total bitcoins ever recieved" to that address.
				  minimum_confirmations | (optional) | min confirmations required to match the criteria.
				  
			Output Explanation
				Return Status
					-1 = Epic Failure
						Notes: This function magically did not run, 
						Reailty as we know it probubly ended if this happened.
						
					 1 = Success 
						Notes: If success, then this function ran with out errors and
						all output data is considered "useable".
					
					100 = Failure to connect to Bitcoin client
					101 = Failure to retrieve balance
					102 = Invalid Bitcoin address was set
					
				total received in satoshi
					The amount of Bitcoins in total received to this address
					in the integer (1 = 1 satoshi = 0.00000001 Bitcoins)
					
				total received in bitcoin
					(DO NOT USE THIS WITH FORMULAS)
					The amount of Bitcoins in total received to this address
					in the decimal/float/double format.
				
		**/
		function bitcoin_get_received_by_address($bitcoin_address='', $minimum_confirmations=1){
			global $bdk_integrity_check, $bdk_settings;
			
			//Define local/private variables
			$output["return_status"] = -1;
			$output["total_received_in_satoshi"] = (int) 0; //Integers only
			$output["total_received_in_bitcoin"] = (double) 0.00000000; //Decimal/Float/Double (THIS IS FOR ONLY DISPLAYING THE TOTAL RECEIVED BALANCE IN BITCOIN , NOT FOR DOING MATH AGAINST!!! Do math in satoshi only)

			
			//Sanatize incomming parameters
				$bitcoin_address	= strip_tags($bitcoin_address); //I went with strip_tags for now, all I can think of is perhaps someone enables error reporting somehow and made the Bitcoin address into a XSS/XSRF attack made up of a string of javavscript)
				$minimum_confirmations	= (int) floor($minimum_confirmations); //Make integer(if for some reason it came in as a decimal)
			
			//Create a floor limit of zero
				if($minimum_confirmations <= 0){
					$minimum_confirmations = 0;
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
		
		
		
		/**
			bitcoin_sendfrom()
			
			Purpose 
				Query Bitcoin and send Bitcoins from an account/address to the specified address
				
			Parameter Explanation
				  bitcoin_address_label [string] | (required) | Which "bitcoin addresses" should the Bitcoins be spent from, addresses are identfied by matching "label".
				send_to_bitcoin_address [string] | (required) | Which address to send/spend the Bitcoins to.
				     amount_in_satoshi [integer] | (required) | The amount of Bitcoins (in satoshi)
				  minimum_cofirmations [integer] | (optional) | Only send bitcoins with this many confirmations (or greater).
			
			Output Explanation
				
				Return Status
					-1 = Epic Failure
						Notes: This function magically did not run, 
						Reailty as we know it probubly ended if this happened.
						
					 1 = Success 
						Notes: If success, then this function ran with out errors and
						all output data is considered "useable".
					
					100 =  Bitcoin connection failed
					101 = Command failed
					
					
				tx_id
					If the return status is (1) then this output will be set with the corresponding tx id.
						
				error_rpc_message
					If an error happened at the Bitcoin level then this will contain that error message (Usually set when return status is not 1)
		**/
		function bitcoin_sendfrom($bitcoin_address_label='', $send_to_bitcoin_address='', $amount_in_satoshi=00000000, $minimum_confirmations=1){
			global $bdk_integrity_check, $bdk_settings;
			
			//Define local/private variables
			$output["return_status"] = -1;
			$output["tx_id"] = '';
			
			$output["error_rpc_message"] = '';

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
		
		
		
		/**
			bitcoin_sendmany()
			
			Purpose
				Query Bitcoin and send Bitcoins to many Bitcoins addresses...
			
			Parameter Explanation
				bitcoin_address_label [string] | (required) | Which "bitcoin addresses" should the Bitcoins be spent from, addresses are identfied by matching "label".
				send_to_bitcoin_address [array] | (required) | Array("Address" => (double)0.0005, Address => (double)0.0005)
				minimum_cofirmations [integer] | (optional) | Only send bitcoins with this many confirmations (or greater).
				
			Output Exlanation
				Return Status
					-1 = Epic Failure
						Notes: This function magically did not run, 
						Reailty as we know it probubly ended if this happened.
						
					 1 = Success 
						Notes: If success, then this function ran with out errors and
						all output data is considered "useable".

					100 =  Bitcoin connection failed
					101 = Command failed
					
				Error Rpc Message
					Notes: if the Bitcoin query errors it will be placed into this variable for debuging
						This is not needed other than debugging/error.
				
		**/
		function bitcoin_sendmany($bitcoin_address_label='', $send_to_bitcoin_address='', $minimum_confirmations=1, $comment=''){
			global $bdk_integrity_check, $bdk_settings;
			
			//Define local/private variables
			$output["return_status"] = -1;
			
			$output["error_rpc_message"] = '';

			
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
		
		
		
		/**
			bitcoin_get_transaction()
			
			Purpose
			Query Bitcoin and get information about the requested transaction.
			
			Parameter Explanation
			tx_id | (required) | the bitcoin transaction id
				
			Output Explanation
				Return Status
					-1 = Epic Failure
						Notes: This function magically did not run, 
						Reailty as we know it probubly ended if this happened.
						
					 1 = Success 
						Notes: If success, then this function ran with out errors and
							all output data is considered "useable".
							
					 100 =  Bitcoin connection failed
					 101 =  Bitcoin connected but failed to query command.
					 
					 
				tx_info
					Notes: The tx_info descriptions below may or may not be precise use caution
						and your own dilegence.
						
					amount | total amount of the transaction
				 confirmations | total confirmations verified for this tx
					  txid | Transaction ID
					  time | Time the transaction became aware in the network (was added to the tx pool)
				       details | An array of variables with each array slot containing
						 the following variable group:
										"account"
										"address"
										"category"
										"amount"
										"fee"
		**/
		function bitcoin_get_transaction($tx_id=''){
			global $bdk_integrity_check, $bdk_settings;
		
			//Define local/private variables
			$output["return_status"]		= -1;
			
			$output["tx_info"]["amount"]		= (double) 0.00000000;
			$output["tx_info"]["fee"]		= (double) 0.00000000;
			$output["tx_info"]["confirmations"]	= (int) 0;
			$output["tx_info"]["blockhash"]		= (string) '';
			$output["tx_info"]["blockindex"]	= (int) 0;
			$output["tx_info"]["blocktime"]		= (int) 0;
			$output["tx_info"]["txid"]		= (string) '';
			$output["tx_info"]["time"]		= (int) 0;
			$output["tx_info"]["timereceived"]	= (int) 0;
			
			$output["tx_info"]["details"]["account"]	= (string) '';
			$output["tx_info"]["details"]["address"]	= (string) '';
			$output["tx_info"]["details"]["category"]	= (string) '';
			$output["tx_info"]["details"]["amount"]		= (double) 0.00000000;
			$output["tx_info"]["details"]["fee"]		= (double) 0.00000000;

			
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
						$output["tx_info"]["amount"]		= (double) $tmp_tx_info["amount"];
						$output["tx_info"]["fee"]		= (double) $tmp_tx_info["fee"];
						$output["tx_info"]["confirmations"]	= (int) $tmp_tx_info["confirmations"];
						$output["tx_info"]["blockhash"]		= (string) $tmp_tx_info["blockhash"];
						$output["tx_info"]["blockindex"]	= (int) $tmp_tx_info["blockindex"];
						$output["tx_info"]["blocktime"]		= (int) $tmp_tx_info["blocktime"];
						$output["tx_info"]["txid"]		= (string) $tmp_tx_info["txid"];
						$output["tx_info"]["time"]		= (int) $tmp_tx_info["time"];
						$output["tx_info"]["timereceived"]	= (int) $tmp_tx_info["timereceived"];
						

						$output["tx_info"]["details"]["account"]	= (string) $tmp_tx_info["details"][0]["account"];
						$output["tx_info"]["details"]["address"]	= (string) $tmp_tx_info["details"][0]["address"];
						$output["tx_info"]["details"]["category"]	= (string) $tmp_tx_info["details"][0]["category"];
						$output["tx_info"]["details"]["amount"]		= (double) $tmp_tx_info["details"][0]["amount"];
						$output["tx_info"]["details"]["fee"]		= (double) $tmp_tx_info["details"][0]["fee"];
						
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
			
			
			/**
				bdk_generate_random_string()
				
				Purpose
					Generates a length of random text
					
				Parameter Explanation
					length | (optional) | the maximum string length the output should be like.
					
				Output Explanation
					Return Status
						-1 = Epic Failure
							Notes: This function magically did not run, 
							Reailty as we know it probubly ended if this happened.
						
						1 = Success 
							Notes: If success, then this function ran with out errors and
							all output data is considered "useable".
						
						100 =  Failed to generate the target length
					
				
			**/
			function bdk_generate_random_string($length=4096, $character_quick=0, $characters=''){
				global $bdk_settings;
				
				
				//Define local variables
				$output["return_status"] = -1;
				$output["random_string"] = '';
				$output["infinite_loop_fault_detected"] = 0; //This is helpfull for debugging or extra "checK" if the generation function did its job

				if(strlen($characters) < 1){
					$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
				}
				
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
			
			Parameter(s) Explanation
			$amount_due: This should be expressed in satoshi. For one Bitcoin 100000000 should be entered in.
			$product_id_array: This has to be an array regardless of key/pair data/value count
	*/
	function bdk_generate_receipt($amount_due_in_satoshi = 0, $product_id_array = Array(), $additional_info = Array()){
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
			$receipt_data["additional_info"]			= Array();
			
			
			//Sanatize variables
				$amount_due_in_satoshi = (int) floor(intval($amount_due_in_satoshi));
				
				//Assign amount due to the receipt data array
				$receipt_data["amount_due_in_satoshi"] = $amount_due_in_satoshi;
				
				//Apply limits to $amount_due_in_satoshi to 0 and 21 mill
					if($amount_due_in_satoshi <= 0){
						$amount_due_in_satoshi = 0;
					}
				
				//Loop through all product ids in $product_id_address and convert them to integers (Still needs to be done Apparently)
					$num_product_ids_in_array = count($product_id_array);
					
					//while($a = 0; $a < $num_product_ids_in_array; $a++){
					//	$product_id_array[$i] = floor($product_id_array[$i]);
					//}

				//Assign product ids to the $receipt_data array
				$receipt_data["products_in_receipt"] = $product_id_array;
				
				//Assign the additional info array into the receipt
				$receipt_data["additional_info"] = $additional_info;
				
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
			
			Parameter(s) Explanation
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
				$output["products_in_receipt"]		= $bitcoin_label_information["products_in_receipt"];
				$output["timestamp_generated"]		= $bitcoin_label_information["timestamp_generated"];
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
			bdk_prove_coin_ownership()
			Purpose: 
			
			PHP.INI NOTE: It seems that if you require to transport your message to sign through get urls you need to set (suhosin.get.max_value_length = 10000) or more;
						other wise use POST or a database and an unique id system to relook up the message.
			
			Notes: Please note that this function is only secure depending on how many bits of entropy your $bdk_integrity_check variable including string length should be atleast 4096.
					^^^ That only matters if you are relying on the message to be intact as it was when it once left the server and you don't have a DB to check/verify message integrity.
			
			Parameter(s) Explanation
				TO DO: ....
	*/
	function bdk_prove_coin_ownership($bitcoin_address='', $step=1, $step_2_signature='', $step_2_original_data='', $message_to_sign=''){
		global $bdk_integrity_check, $bdk_settings;
		
		//Define local/private variables
		$output["return_status"]			= -1;
		$output["bitcoin_address_authenticated"] 	= 0;
		$output["string_to_sign"]			= '';
		
		/* Return status codes
			-1 = Failure to collect information on the receipt
			1 = Success
			
			100 = Connection to Bitcoin failed
			101 = Creation of random string failed.
			102 = Inputted server checksum dosen't match the local server check sum. Tell user to try again we can't trust this information if the server checksum dosen't match the data.
			103 = null
			104 = Bitcoin address was not set, with out the address we can't retrieve any Bitcoin information
			105 = (Same as 104 only different for debugging purposes)  Bitcoin address was not set, with out the address we can't retrieve any Bitcoin information
			106 = message did not validate, signature should not be trusted
		*/
		
		/** Filter - Sanatize **/
		$step_2_signature	= trim($step_2_signature);
		$step_2_original_data	= trim($step_2_original_data);
		$message_to_sign	= trim($message_to_sign);
		
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
						$server_checksum = hash($bdk_settings["hash_type"], $current_time_sync.$random_string.$bdk_integrity_check.$bitcoin_address);
						
						//String to sign
							if($message_to_sign == ''){
								$string_to_sign = "This message is to prove ownership of the address of ".$bitcoin_address." and in no way, shape or form is it a legal binding contract. |".$current_time_sync."|".$server_checksum."|".$random_string."|".$bitcoin_address;
							}else{
								//Remove all | pipes from the string to prevent the message from breaking the ending signature thing.
								$message_to_sign = str_replace("|", "", $message_to_sign);
								$string_to_sign = $message_to_sign."|".$current_time_sync."|".$server_checksum."|".$random_string."|".$bitcoin_address;
							}
							
						//Return string to sign
						$output["string_to_sign"] = $string_to_sign;
						
						$output["return_status"] = 1;
						
					}else{
						//Creation of random string failed.
						$output["return_status"] = 101;
					}
					
				}else if($step == 2){
					//Validate information

					//Split data so we can do some integrity checks
					$step_2_decoded_data_split = explode("|", $step_2_original_data);
					
					/*
					step_2_decoded_data_split Table
					[0] = original message
					[1] = Time stamp
					[2] = (Client provided) Server Checksum
					[3] = Random String
					[4] = Bitcoin Address attempting to authenticate
					*/
					
					//Create a serverside checksum based on the provided information
					$server_checksum =hash($bdk_settings["hash_type"], $step_2_decoded_data_split[1].$step_2_decoded_data_split[3].$bdk_integrity_check.$step_2_decoded_data_split[4]);

					//See if the server checksum matches with the client provided serverchecksum
					if($server_checksum == $step_2_decoded_data_split[2]){
						//So far soo good the data is intact, now we must verify that the Bitcoin signature is valid with the data
						$tmp_hash_message = hash($bdk_settings["hash_type"], $step_2_original_data);

						$valid_message_status = bitcoin_verify_message($bitcoin_address, $step_2_signature, $tmp_hash_message);

						if($valid_message_status["return_status"] == 1 && $valid_message_status["message_valid"] == 1){
							//A valid message! But is this token expired?
							if((time() - $step_2_decoded_data_split[1]) <= $bdk_settings["coin_authentication_timeout"]){
								//Consider the user authenticated!
								$output["return_status"] = 1;
								$output["bitcoin_address_authenticated"] = 1;
							}else{
								//Token has expired, the user must generate another one.
								$output["return_status"] = 1;
								$output["bitcoin_address_authenticated"] = 0;
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
	
	
/*********************************************************************
	Clear Checksum memory to prevent any scripts out side this one from tampering with checksums. (this memory clearing dosen't prevent scripts from opening up the config in a text file and reading the checksum)
*********************************************************************/
	$bdk_integrity_check = '000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000';
/********************* END CLEAR CHECKSUM MEMORY *************/

?>