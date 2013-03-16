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
		along with BDK.  If not, see http://www.gnu.org/licenses/agpl-3.0.html
*/


	/*	NOTES ABOUT ALTERING CONFIG PAGE FILE TYPE
		Please don't ever save your file as .txt or in a format where any joe-shmoe can type in http://yourwebsite.com/path/to/bdk_library/config.txt and take a look at your passwords, keep this file in a non-renderable format by it self like php
		
		Moving right along......
	*/
	
	
	/*
		Select the hashing function you would like to use to experience data integrity with your transactions
	*/
	$bdk_settings["hash_type"] = "sha256"; //What should the hash() function use?
	$bdk_settings["coin_authentication_timeout"] = 1200; //How much time (in seconds) how long a user has to authenticate their Bitcoin identity before a signature is considered expired
	
	//Define some Bitcoin client configuration settings
	$btcclient["https"]	= "http"; //HTTPS is recommended....
	$btcclient["host"]	= "127.0.0.1"; //Just the domainname don't put Http:// or https:// that is already taken care of.
	$btcclient["user"]	= "username";
	$btcclient["pass"]	= "password";
	$btcclient["port"]	= "4367";
	
	//Define Integrity checks (checksum details)
	$bdk_integrity_check = 'TypeALongRandomStringHere'; //Generate a random string that is atleast 4096 characters long, Random number here:  http://textmechanic.com/Random-String-Generator.html

?>
	