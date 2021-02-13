<?php 

require '../vendor/autoload.php';

use InvincibleTechSystems\EaseAmpMysqlHalite\EaseAmpMysqlHalite;
use \InvincibleTechSystems\EaseAmpMysqlHalite\CustomAmphpDnsConfigLoader;
use \InvincibleTechSystems\EaseAmpMysqlHalite\Exceptions\EaseAmpMysqlHaliteException;

use InvincibleTechSystems\EaseAmpMysql\EaseAmpMysql;

use \EaseAppPHP\EAHalite\EAHalite;

use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto as Symmetric;
use ParagonIE\HiddenString\HiddenString;

//Crypto Key Storage Path
$crypto_key_storage_path = '/home/username/public_html/generated-enc-auth-keys/';

//Check, if Libsodium is setup correctly
if (ParagonIE\Halite\Halite::isLibsodiumSetupCorrectly() === true) {
	
	//Retrieve the previously saved Symmetric Encryption key from the file
	$symmetric_encryption_key = \ParagonIE\Halite\KeyFactory::loadEncryptionKey($crypto_key_storage_path . 'xsalsa20_symmetric_encryption.key');
	
	//Retrieve the previously saved Symmetric Authentication key from the file
	$symmetric_authentication_key = \ParagonIE\Halite\KeyFactory::loadAuthenticationKey($crypto_key_storage_path . 'hmac_sha512_or_sha256_symmetric_authentication.key');
	
	//Retrieve the previously saved File related Symmetric Encryption key from the file
	$file_rel_symmetric_encryption_key = \ParagonIE\Halite\KeyFactory::loadEncryptionKey($crypto_key_storage_path . 'xsalsa20_file_rel_symmetric_encryption.key');
	
	//Retrieve the previously saved File related Symmetric Authentication key from the file
	$file_rel_symmetric_authentication_key = \ParagonIE\Halite\KeyFactory::loadAuthenticationKey($crypto_key_storage_path . 'hmac_sha512_or_sha256_file_rel_symmetric_authentication.key');

	//Retrieve the previously saved Asymmetric Anonymous Encryption key from the file
	$asymmetric_anonymous_encryption_keypair = \ParagonIE\Halite\KeyFactory::loadEncryptionKeyPair($crypto_key_storage_path . 'curve25519_asymmetric_anonymous_encryption_keypair.key');

	$asymmetric_anonymous_encryption_secret_key = $asymmetric_anonymous_encryption_keypair->getSecretKey();
	$asymmetric_anonymous_encryption_public_key = $asymmetric_anonymous_encryption_keypair->getPublicKey();
	
	//Retrieve the previously saved Asymmetric Authentication key from the file
	$asymmetric_authentication_keypair = \ParagonIE\Halite\KeyFactory::loadSignatureKeyPair($crypto_key_storage_path . 'ed25519_asymmetric_authentication_keypair.key');

	$asymmetric_authentication_secret_key = $asymmetric_authentication_keypair->getSecretKey();
	$asymmetric_authentication_public_key = $asymmetric_authentication_keypair->getPublicKey();
	
	//Retrieve the previously saved File related Asymmetric Anonymous Encryption key from the file
	$file_rel_asymmetric_anonymous_encryption_keypair = \ParagonIE\Halite\KeyFactory::loadEncryptionKeyPair($crypto_key_storage_path . 'curve25519_file_rel_asymmetric_anonymous_encryption_keypair.key');

	$file_rel_asymmetric_anonymous_encryption_secret_key = $file_rel_asymmetric_anonymous_encryption_keypair->getSecretKey();
	$file_rel_asymmetric_anonymous_encryption_public_key = $file_rel_asymmetric_anonymous_encryption_keypair->getPublicKey();
	
	//Retrieve the previously saved File Related Asymmetric Authentication key from the file
	$file_rel_asymmetric_authentication_keypair = \ParagonIE\Halite\KeyFactory::loadSignatureKeyPair($crypto_key_storage_path . 'ed25519_file_rel_asymmetric_authentication_keypair.key');

	$file_rel_asymmetric_authentication_secret_key = $file_rel_asymmetric_authentication_keypair->getSecretKey();
	$file_rel_asymmetric_authentication_public_key = $file_rel_asymmetric_authentication_keypair->getPublicKey();
	
} else {
	
	throw new EAHaliteException("Error with Libsodium Setup, tha is required by Halite! \n");
	
}

$eaHalite = new EAHalite($asymmetric_anonymous_encryption_secret_key, $asymmetric_anonymous_encryption_public_key, $asymmetric_authentication_secret_key, $asymmetric_authentication_public_key, $file_rel_asymmetric_anonymous_encryption_secret_key, $file_rel_asymmetric_anonymous_encryption_public_key, $file_rel_asymmetric_authentication_secret_key, $file_rel_asymmetric_authentication_public_key, $symmetric_encryption_key, $symmetric_authentication_key, $file_rel_symmetric_encryption_key, $file_rel_symmetric_authentication_key);


$dbHost = "localhost";
$dbUsername = "db_username";
$dbPassword = "db_password";
$dbName = "db_name"; 

$customAmphpDnsConfigValues = ["208.67.222.222:53", "208.67.220.220:53","8.8.8.8:53","[2001:4860:4860::8888]:53"];

$CustomAmphpDnsConfigLoader = new CustomAmphpDnsConfigLoader($customAmphpDnsConfigValues, 5000, 3);

\Amp\Dns\resolver(new \Amp\Dns\Rfc1035StubResolver(null, $CustomAmphpDnsConfigLoader));

$easeAmyMysql = new EaseAmpMysql($dbHost, $dbUsername, $dbPassword, $dbName);

$dbConn = new EaseAmpMysqlHalite($easeAmyMysql, $eaHalite);

//Insert Query (insertWithIntegerAsPrimaryKey)
$query = "INSERT INTO `table_name`(`id`, `name`) VALUES (:id,:name)";

$values_array = array();
$values_array = array(':id' => 10,':name' => 'Raghu');


echo "===============================================================================================================================================";

//Insert Query (insertWithUUIDAsPrimaryKey)
$query = "INSERT INTO `site_members`(`sm_memb_id`, `sm_firstname`) VALUES (:sm_memb_id,:sm_firstname)";

$values_array = array();
$values_array = array(':sm_memb_id' => 'abcd',':sm_firstname' => 'Raghu');

//$queryResult = $dbConn->insertSingle($query, $values_array, "site_members", "sm_memb_id", $values_array[":sm_memb_id"]);

echo "===============================================================================================================================================";

//Update Query
$query = "UPDATE `site_members` SET `sm_firstname`=:sm_firstname, `sm_lastname`=:sm_lastname WHERE `sm_memb_id`= :sm_memb_id";

$values_array = array();
$values_array = array(':sm_firstname' => 'Raghuveer',':sm_lastname' => 'D',':sm_memb_id' => "10d46b1f-9ddc-4bbe-866b-82523a96a037");

$queryResult = $dbConn->updateSingle($query, $values_array, "site_members", "sm_memb_id", $values_array[":sm_memb_id"]);


//h44ijc0yVG7P5an8heg_jg23Gzggu8g6fXMF5Rep0huBAwoR8l1sBPy9zszC21OwjZ91pZb2rtGk8G7mMTd0DQ==
echo "===============================================================================================================================================";

//Select Query
$query = "SELECT * FROM `site_members` WHERE `sm_memb_id`=:sm_memb_id";

$values_array = array();
$values_array = array(':sm_memb_id' => "02d3ca71-a0a2-4f4c-a857-f69dad626a06");

//$queryResult = $dbConn->selectSingle($query, $values_array);

/*if ((count($queryResult) == "3") && (isset($queryResult["row_content_received"])) && ($queryResult["row_content_received"] === true) && (is_array($queryResult["row_content_excluding_ds"])) && (count($queryResult["row_content_excluding_ds"]) > 0) && (isset($queryResult["row_rel_doc_crypto_hash"])) && ($queryResult["row_rel_doc_crypto_hash"] != "")) {
	
	echo "select query response received\n";
	
}*/

echo "===============================================================================================================================================";

//Select All Query
$query = "SELECT * FROM `site_members`";

$values_array = array();

//$queryResult = $dbConn->selectMultiple($query, $values_array);

echo "===============================================================================================================================================";

//Delete Query

//$queryResult = $dbConn->deleteSingle("site_members", "sm_memb_id", "abcd");

echo "===============================================================================================================================================";

//Update DOCCryptoHash for a specific DB row, based on db table specific primary key column name and primary key column value
//$queryResult = $dbConn->updateDocCryptoHashSingleRowDBTable("site_members", "sm_memb_id", "10d46b1f-9ddc-4bbe-866b-82523a96a037");

//h44ijc0yVG7P5an8heg_jg23Gzggu8g6fXMF5Rep0huBAwoR8l1sBPy9zszC21OwjZ91pZb2rtGk8G7mMTd0DQ==
//h44ijc0yVG7P5an8heg_jg23Gzggu8g6fXMF5Rep0huBAwoR8l1sBPy9zszC21OwjZ91pZb2rtGk8G7mMTd0DQ==
echo "===============================================================================================================================================";

echo "<pre>";
print_r($queryResult);


?>