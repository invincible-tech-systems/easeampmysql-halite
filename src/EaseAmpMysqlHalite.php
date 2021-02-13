<?php

declare(strict_types=1);

namespace InvincibleTechSystems\EaseAmpMysqlHalite;

use \Amp\Mysql;
use InvincibleTechSystems\EaseAmpMysql\EaseAmpMysql;

use \EaseAppPHP\EAHalite\EAHalite;
use \EaseAppPHP\EAHalite\Exceptions\EAHaliteException;

use \InvincibleTechSystems\EaseAmpMysqlHalite\Exceptions\EaseAmpMysqlHaliteException;

/*
* Name: EaseAmpMysqlHalite
*
* Author: Raghuveer Dendukuri
*
* Company: Invincible Tech Systems
*
* Version: 1.0.0
*
* Description: A very simple and safe PHP library to execute SQL Queries as Prepared Statements on MySQL Database, in an asynchronous & non-blocking all basing 
* upon amphp/mysql package. Additional checks are supported in terms of facilitating creation and verification of row level digital signature for different 
* database tables along with creation of blind indexes of data in encrypted db columns, using easeappphp/ea-halite package.
*
* License: MIT
*
* @copyright 2021 Invincible Tech Systems
*/
class EaseAmpMysqlHalite {
	
	private $easeAmyMysql;
	private $eaHalite;

	public function __construct(EaseAmpMysql $easeAmyMysql, EAHalite $eaHalite) {
		
		$this->easeAmyMysql = $easeAmyMysql;
		$this->eaHalite = $eaHalite;
		
    }
	
	//Select Query with Single Row Response
	public function selectSingle($sql, array $values_array)
	{
		
		try {
		
			$signatureCheckResult = false;
			$responseArray = [];
			
			if (is_null(trim($sql))) {
				
				$responseArray["row_content_received"] = false;
				$responseArray["row_content_excluding_ds"] = [];
				$responseArray["row_rel_doc_crypto_hash"] = "";
				return $responseArray;
				
			} elseif ((empty($values_array)) || (!is_array($values_array)) || (count($values_array) == 0)) {
				
				$responseArray["row_content_received"] = false;
				$responseArray["row_content_excluding_ds"] = [];
				$responseArray["row_rel_doc_crypto_hash"] = "";
				return $responseArray;
				
			} else {
								
				$selectQueryResult = $this->easeAmyMysql->executeQuery($sql, $values_array, "selectSingle");
				
				if (count($selectQueryResult) > 0) {
					echo "\n row content received \n";
					$rowDataAndDoccryptohash = $this->getRowDataAndDoccryptohash($selectQueryResult);
					
					//Extract Digital Signature from Received Row
					$docCryptoHashFromDb = $rowDataAndDoccryptohash["docCryptoHash"];
					$rowrelOtherColumnDataArray = $rowDataAndDoccryptohash["rowrelOtherColumnData"];
					
					if ((!is_null($docCryptoHashFromDb)) && ($docCryptoHashFromDb != "")) {
						
						//Verify digital signature
						$signatureCheckResult = $this->eaHalite->validateDigitalSignature($rowrelOtherColumnDataArray, $docCryptoHashFromDb);
						var_dump($signatureCheckResult);
						
						if ($signatureCheckResult === true) {
							echo "\n Signature is Valid and true\n";
							
							$responseArray["row_content_received"] = true;
							$responseArray["row_content_excluding_ds"] = $rowrelOtherColumnDataArray;
							$responseArray["row_rel_doc_crypto_hash"] = $docCryptoHashFromDb;
							return $responseArray;
							
						} else {
							echo "\n Signature is Invalid and false\n";
							
							$responseArray["row_content_received"] = true;
							$responseArray["row_content_excluding_ds"] = [];
							$responseArray["row_rel_doc_crypto_hash"] = "";
							return $responseArray;
							
						}
						
					} else {
						
						echo "\n Signature is NULL or Empty String\n";
							
						$responseArray["row_content_received"] = true;
						$responseArray["row_content_excluding_ds"] = [];
						$responseArray["row_rel_doc_crypto_hash"] = "";
						return $responseArray;
						
					}
					
					
				} else {
					echo "\n row content not received\n";
					$responseArray["row_content_received"] = false;
					$responseArray["row_content_excluding_ds"] = [];
					$responseArray["row_rel_doc_crypto_hash"] = "";
					return $responseArray;
					
				}
				
			}
			
		} catch (EaseAmpMysqlHaliteException $e) {
			
			echo "\n EaseAmpMysqlHaliteException - ", $e->getMessage(), (int)$e->getCode();
			
		}
			
	}
	
	//Select Query with Multiple Row Response
	public function selectMultiple($sql, array $valuesArray)
	{
		$signature_check_result = false;
		$responseArray = [];
		
		if (is_null(trim($sql))) {
			
			$responseArray["valid_data"] = [];	
			$responseArray["tampered_data"] = [];
			$responseArray["no_signature_data"] = [];
			return $responseArray;
			
		} elseif (!is_array($valuesArray)) {
			
			$responseArray["valid_data"] = [];	
			$responseArray["tampered_data"] = [];
			$responseArray["no_signature_data"] = [];
			return $responseArray;
			
		} else {
			
			$selectQueryResult = $this->easeAmyMysql->executeQuery($sql, $valuesArray, "selectMultiple");
			
			if (count($selectQueryResult) > 0) {
					
					
				foreach ($selectQueryResult as $selectQueryResultRow) {
					
					$temp = array();
					
					$rowDataAndDoccryptohash = $this->getRowDataAndDoccryptohash($selectQueryResultRow);
					
					//Extract Digital Signature from Received Row
					$docCryptoHashFromDb = $rowDataAndDoccryptohash["docCryptoHash"];
					$rowrelOtherColumnDataArray = $rowDataAndDoccryptohash["rowrelOtherColumnData"];
					
					if ((!is_null($docCryptoHashFromDb)) && ($docCryptoHashFromDb !="")) {
						
						//Verify digital signature
						$signature_check_result = $this->eaHalite->validateDigitalSignature($rowrelOtherColumnDataArray, $docCryptoHashFromDb);
						
						if ($signature_check_result === true) {
							//echo "Signature is Valid and true\n";
							
							$temp["row_content_received"] = true;
							$temp["row_content_excluding_ds"] = $rowrelOtherColumnDataArray;
							$temp["row_rel_doc_crypto_hash"] = $docCryptoHashFromDb;
							
							$responseArray["valid_data"][] = $temp;
							
							
							
						} else {
							//echo "Signature is Invalid and false\n";
							
							$temp["row_content_received"] = true;
							$temp["row_content_excluding_ds"] = $rowrelOtherColumnDataArray;
							$temp["row_rel_doc_crypto_hash"] = $docCryptoHashFromDb;
							
							$responseArray["tampered_data"][] = $temp;
							
						}
						
					} else {
						
						//echo "Signature Does not Exist, i.e. Value is NULL\n";
							
						$temp["row_content_received"] = true;
						$temp["row_content_excluding_ds"] = $rowrelOtherColumnDataArray;
						$temp["row_rel_doc_crypto_hash"] = $docCryptoHashFromDb;
						
						$responseArray["no_signature_data"][] = $temp;
						
						
					}
					
					
				}
				
				return $responseArray;
				
			} else {
				
				$responseArray["valid_data"] = [];	
				$responseArray["tampered_data"] = [];
				$responseArray["no_signature_data"] = [];
				return $responseArray;
				
			}
			
			
		}
				
	}
	
	public function updateDocCryptoHashSingleRowDBTable($tableName, $primaryKeyColumnName, $primaryKeyColumnValue)
	{
		$responseArray = [];
		
		$colonAddedPrimaryKeyColumnName = ":" . $primaryKeyColumnName;
		//echo "colonAddedPrimaryKeyColumnName: " . $colonAddedPrimaryKeyColumnName . "\n";
		
		$selectQuerySql = "SELECT * FROM " . $tableName . " WHERE " . $primaryKeyColumnName . " LIKE " . $colonAddedPrimaryKeyColumnName;
		$selectQueryValuesArray = array("$colonAddedPrimaryKeyColumnName" => $primaryKeyColumnValue);
		
		$selectQueryResult = $this->easeAmyMysql->executeQuery($selectQuerySql, $selectQueryValuesArray, "selectSingle");
		
		if (count($selectQueryResult) > 0) {
			
			$rowDataAndDoccryptohash = $this->getRowDataAndDoccryptohash($selectQueryResult);
					
			//Extract Digital Signature from Received Row
			$docCryptoHashFromDb = $rowDataAndDoccryptohash["docCryptoHash"];
			$rowrelOtherColumnDataArray = $rowDataAndDoccryptohash["rowrelOtherColumnData"];
					
			//Assign
			$rowArrayNew = $rowrelOtherColumnDataArray;
			
			//Create Digital Signature
			$createdRowRelDigitalSignature = $this->eaHalite->createDigitalSignature($rowArrayNew);
			
			
			//Update the Row, with Digital Signature
			$updateQuerySql = "UPDATE " . $tableName . " SET `doc_crypto_hash`=:doc_crypto_hash WHERE " . $primaryKeyColumnName . " LIKE " . $colonAddedPrimaryKeyColumnName;
			
			$rowUpdationRelChangingColumnsArray = array();	
			$rowUpdationRelChangingColumnsArray[":doc_crypto_hash"] = $createdRowRelDigitalSignature;
			$rowUpdationRelChangingColumnsArray["$colonAddedPrimaryKeyColumnName"] = $primaryKeyColumnValue;
			
			$updateQueryResult = $this->easeAmyMysql->executeQuery($updateQuerySql, $rowUpdationRelChangingColumnsArray, "update");
				
			if($updateQueryResult === true) {
			   
				$updatedRowVerificationResult = $this->selectSingle($selectQuerySql, $selectQueryValuesArray);
				/* echo "updatedRowVerificationResult: \n";
				var_dump($updatedRowVerificationResult);
				echo "\n-----------------\n";
				 */
				if ((count($updatedRowVerificationResult) > 0) && ($updatedRowVerificationResult["row_content_received"] === true)) {
					
					if ((count($updatedRowVerificationResult["row_content_excluding_ds"]) > 0) && ($updatedRowVerificationResult["row_rel_doc_crypto_hash"] != "") && (!is_null($updatedRowVerificationResult["row_rel_doc_crypto_hash"]))) {
					
						$responseArray["primary_key"] = $primaryKeyColumnValue;
						$responseArray["update_query_status"] = true;
						$responseArray["row_content_received"] = true;
						$responseArray["update_query_verification_status"] = true;
						
					} else {
						
						$responseArray["primary_key"] = $primaryKeyColumnValue;
						$responseArray["update_query_status"] = true;
						$responseArray["row_content_received"] = true;
						$responseArray["update_query_verification_status"] = false;
						
					}
					
				} else {
					
					$responseArray["primary_key"] = $primaryKeyColumnValue;
					$responseArray["update_query_status"] = true;
					$responseArray["row_content_received"] = false;	
					$responseArray["update_query_verification_status"] = false;
					
				}
				
				
				return $responseArray;
			   
			} else {
				
				$responseArray["primary_key"] = $primaryKeyColumnValue;
				$responseArray["update_query_status"] = false;
				$responseArray["row_content_received"] = true;	
				$responseArray["update_query_verification_status"] = false;
				
				return $responseArray;
				
			}
			
			
		} else {
			
			$responseArray["primary_key"] = $primaryKeyColumnValue;
			$responseArray["update_query_status"] = false;
			$responseArray["row_content_received"] = false;	
			$responseArray["update_query_verification_status"] = false;
			
			return $responseArray;
			
		}
	}
					
	//Insert Query with Row Level Doc Crypto Hash Verification. This is useful with Single Column based Primary Key Scenario
	function insertSingle($insertQuerySql, $insertQueryValuesArray, $tableName, $primaryKeyColumnName, $primaryKeyColumnValue)
	{
		$responseArray = [];
		
		$rowArrayNew = [];
		
		
		$queryResult = $this->easeAmyMysql->executeQuery($insertQuerySql, $insertQueryValuesArray, "insertWithUUIDAsPrimaryKey");

		if($queryResult === true) {
			
			$colonAddedPrimaryKeyColumnName = ":" . $primaryKeyColumnName;
			//echo "colonAddedPrimaryKeyColumnName: " . $colonAddedPrimaryKeyColumnName . "\n";
			
			$selectQuerySql = "SELECT * FROM " . $tableName . " WHERE " . $primaryKeyColumnName . " LIKE " . $colonAddedPrimaryKeyColumnName;
			$selectQueryValuesArray = array("$colonAddedPrimaryKeyColumnName" => $primaryKeyColumnValue);
			
			$selectQueryResult = $this->easeAmyMysql->executeQuery($selectQuerySql, $selectQueryValuesArray, "selectSingle");
			
			
			if (count($selectQueryResult) > 0) {
			
			$rowDataAndDoccryptohash = $this->getRowDataAndDoccryptohash($selectQueryResult);
					
			//Extract Digital Signature from Received Row
			$docCryptoHashFromDb = $rowDataAndDoccryptohash["docCryptoHash"];
			$rowrelOtherColumnDataArray = $rowDataAndDoccryptohash["rowrelOtherColumnData"];
					
			//Assign
			$rowArrayNew = $rowrelOtherColumnDataArray;
			
			//Create Digital Signature
			$createdRowRelDigitalSignature = $this->eaHalite->createDigitalSignature($rowArrayNew);
			
			
			//Update the Row, with Digital Signature
			$updateQuerySql = "UPDATE " . $tableName . " SET `doc_crypto_hash`=:doc_crypto_hash WHERE " . $primaryKeyColumnName . " LIKE " . $colonAddedPrimaryKeyColumnName;
			
			$rowUpdationRelChangingColumnsArray = array();	
			$rowUpdationRelChangingColumnsArray[":doc_crypto_hash"] = $createdRowRelDigitalSignature;
			$rowUpdationRelChangingColumnsArray["$colonAddedPrimaryKeyColumnName"] = $primaryKeyColumnValue;
			
			$updateQueryResult = $this->easeAmyMysql->executeQuery($updateQuerySql, $rowUpdationRelChangingColumnsArray, "update");
				
				if($updateQueryResult === true) {
					   
					$insertedRowVerificationResult = $this->selectSingle($selectQuerySql, $selectQueryValuesArray);
					/* echo "insertedRowVerificationResult: \n";
					var_dump($insertedRowVerificationResult);
					echo "\n-----------------\n";
					 */
					if ((count($insertedRowVerificationResult) > 0) && ($insertedRowVerificationResult["row_content_received"] === true)) {
						
						if ((count($insertedRowVerificationResult["row_content_excluding_ds"]) > 0) && ($insertedRowVerificationResult["row_rel_doc_crypto_hash"] != "") && (!is_null($insertedRowVerificationResult["row_rel_doc_crypto_hash"]))) {
						
							$responseArray["primary_key"] = $primaryKeyColumnValue;
							$responseArray["insert_query_status"] = true;
							$responseArray["row_content_received"] = true;
							$responseArray["insert_query_verification_status"] = true;
							
						} else {
							
							$responseArray["primary_key"] = $primaryKeyColumnValue;
							$responseArray["insert_query_status"] = true;
							$responseArray["row_content_received"] = true;
							$responseArray["insert_query_verification_status"] = false;
							
						}
						
					} else {
						
						$responseArray["primary_key"] = $primaryKeyColumnValue;
						$responseArray["insert_query_status"] = true;
						$responseArray["row_content_received"] = false;	
						$responseArray["insert_query_verification_status"] = false;
						
					}
					
					
					return $responseArray;
				   
				} else {
					
					$responseArray["primary_key"] = $primaryKeyColumnValue;
					$responseArray["insert_query_status"] = true;
					$responseArray["row_content_received"] = true;	
					$responseArray["insert_query_verification_status"] = false;
					
					return $responseArray;
					
				}
				
				
			} else {
				
				$responseArray["primary_key"] = $primaryKeyColumnValue;
				$responseArray["insert_query_status"] = true;
				$responseArray["row_content_received"] = false;	
				$responseArray["insert_query_verification_status"] = false;
				
				return $responseArray;
				
			}
			
			
		} else {
			//echo "error with insert query \n";
			
			$responseArray["primary_key"] = $primaryKeyColumnValue;
			$responseArray["insert_query_status"] = false;
			$responseArray["row_content_received"] = false;	
			$responseArray["insert_query_verification_status"] = false;
			
			return $responseArray;
		}
			
	}

	//Update Query for specific fields, with Row Level Doc Crypto Hash Verification.
	public function updateSingle($updateQuerySql, $updateQueryValuesArray, $tableName, $whereParameterColumnName, $whereParameterColumnValue)
	{
		$responseArray = [];
		
		$singlePrimaryKeyColumnName = "";
		$updatedValuesArray = array();
	
		//Get Table specific Primary key Details
		$singlePrimaryKeyColumnName = $this->easeAmyMysql->getTableRelSinglePrimaryKeyColumnName($tableName);
		//echo "singlePrimaryKeyColumnName: " . $singlePrimaryKeyColumnName . "\n"; 
		
		if ($singlePrimaryKeyColumnName != "") {
			
			//echo "UPDATE QUERY SCENARIO: \n";
			$selectQueryWhereRelColumnNamedParameter = ":" . $whereParameterColumnName;
				
			$selectQuerySql = "SELECT * FROM " . $tableName . " WHERE " . $whereParameterColumnName . " = " . $selectQueryWhereRelColumnNamedParameter;
			/* $selectQuerySql = "SELECT * FROM `site_members` WHERE `sm_memb_id`=:sm_memb_id";
			$selectQueryValuesArray = array(':sm_memb_id' => "1016r8r86r889");
 */
			$selectQueryValuesArray = array("$selectQueryWhereRelColumnNamedParameter" => $whereParameterColumnValue);
			
			 
			$selectedRowVerificationResult = $this->selectSingle($selectQuerySql, $selectQueryValuesArray);
			echo "\n selectedRowVerificationResult: \n";
			var_dump($selectedRowVerificationResult);
			 
			if ((count($selectedRowVerificationResult) > 0) && ($selectedRowVerificationResult["row_content_received"] === true)) {
				
				if ((count($selectedRowVerificationResult["row_content_excluding_ds"]) > 0) && ($selectedRowVerificationResult["row_rel_doc_crypto_hash"] != "") && (!is_null($selectedRowVerificationResult["row_rel_doc_crypto_hash"]))) {
				
					$selectQueryContent = array();
					$namedParameterRemovedReceivedContent = array();
					$namedParameterAddedTotalContent = array();
					
					//Do Extract Verified Row Content into a Separate Array
					foreach ($selectedRowVerificationResult["row_content_excluding_ds"] as $existingRowDataKey => $existingRowDataValue) {
						
						$selectQueryContent["$existingRowDataKey"] = $existingRowDataValue;
						
					}
					
					//Do Remove Colon, from the Received Modification Content
					foreach ($updateQueryValuesArray as $updateQueryValuesArrayKey => $updateQueryValuesArrayValue) {
					
						$columnName = substr($updateQueryValuesArrayKey, 1);
						
						$namedParameterRemovedReceivedContent["$columnName"] = $updateQueryValuesArrayValue;
						
					}
					
					//var_dump($namedParameterRemovedReceivedContent);
					
					//Do Merge Recent Content on to the Existing Row Data Array
					$updatedValuesArray = array_merge($selectQueryContent, $namedParameterRemovedReceivedContent);
					
					echo "\n updatedValuesArray: \n";
					var_dump($updatedValuesArray);
					
					//Create Digital Signature, for the Updated Row Content
					$updated_row_array_ds = $this->eaHalite->createDigitalSignature($updatedValuesArray);
					echo "\n updated_row_array_ds: " . $updated_row_array_ds . "\n";
					
					//Verify Created Digital Signature
					$updatedContentSignatureCheckResult = $this->eaHalite->validateDigitalSignature($updatedValuesArray, $updated_row_array_ds);
					
					//echo "updatedContentSignatureCheckResult: " . $updatedContentSignatureCheckResult . "\n";
					
					if ($updatedContentSignatureCheckResult === true) {
						
						//echo "Primary_Key: " . $updatedValuesArray[$singlePrimaryKeyColumnName] . " - updatedContentSignatureCheckResult: " . $updatedContentSignatureCheckResult . " ( SIGNATURE IS VALID and TRUE)\n\n";
						
						//Add Digital Signature to the Row Array
						//$updatedValuesArray["doc_crypto_hash"] = $updated_row_array_ds;
						$namedParameterRemovedReceivedContent["doc_crypto_hash"] = $updated_row_array_ds;
						$namedParameterRemovedReceivedContent[$singlePrimaryKeyColumnName] = $updatedValuesArray[$singlePrimaryKeyColumnName];
						
						echo "\n namedParameterRemovedReceivedContent: \n";
						var_dump($namedParameterRemovedReceivedContent);
						
						//Do Convert Array key into named parameter, from the received row content
						foreach ($namedParameterRemovedReceivedContent as $namedParameterRemovedReceivedContentKey => $namedParameterRemovedReceivedContentValue) {
						
							$namedParameterKey = ":" . $namedParameterRemovedReceivedContentKey;
							
							$namedParameterAddedTotalContent["$namedParameterKey"] = $namedParameterRemovedReceivedContentValue;
							
						}
						echo "\n namedParameterAddedTotalContent: \n";
						var_dump($namedParameterAddedTotalContent);
						
						$updateQueryResult = $this->easeAmyMysql->executeQuery($updateQuerySql, $namedParameterAddedTotalContent, "update");
						
						/*//Do Update Query
						$update_query = $dbcon->prepare($updateQuerySql);
						
						if($update_query->execute($namedParameterAddedTotalContent)) {*/
						if($updateQueryResult === true) {
						   
							$responseArray["primary_key"] = $updatedValuesArray[$singlePrimaryKeyColumnName];
							$responseArray["row_content_received"] = true;
							$responseArray["row_tampering_status"] = false;
							$responseArray["update_query_status"] = true;
							$responseArray["updated_content_doc_crypto_hash_verification_result"] = true;
							
							return $responseArray;
						   
						} else {
							
							$responseArray["primary_key"] = $updatedValuesArray[$singlePrimaryKeyColumnName];
							$responseArray["row_content_received"] = true;
							$responseArray["row_tampering_status"] = false;
							$responseArray["update_query_status"] = false;
							$responseArray["updated_content_doc_crypto_hash_verification_result"] = true;
							
							return $responseArray;
							
						}
						
					} else {
						
						//echo "Primary_Key: " . $updatedValuesArray[$singlePrimaryKeyColumnName] . " - updatedContentSignatureCheckResult: " . $updatedContentSignatureCheckResult . " ( SIGNATURE IS INVALID and FALSE)\n";
						
						$responseArray["primary_key"] = $updatedValuesArray[$singlePrimaryKeyColumnName];
						$responseArray["row_content_received"] = true;
						$responseArray["row_tampering_status"] = false;
						$responseArray["update_query_status"] = false;
						$responseArray["updated_content_doc_crypto_hash_verification_result"] = false;
						
						return $responseArray;
						
					}
					
				} else {
					
					//$responseArray["primary_key"] = $updatedValuesArray[$singlePrimaryKeyColumnName];
					$responseArray["primary_key"] = "";
					$responseArray["row_content_received"] = true;
					$responseArray["row_tampering_status"] = true;
					$responseArray["update_query_status"] = false;
					$responseArray["updated_content_doc_crypto_hash_verification_result"] = false;
					
					return $responseArray;
					
				}
				
			} else {
				
				//$responseArray["primary_key"] = $updatedValuesArray[$singlePrimaryKeyColumnName];
				$responseArray["primary_key"] = "";
				$responseArray["row_content_received"] = false;	
				$responseArray["row_tampering_status"] = false;
				$responseArray["update_query_status"] = false;
				$responseArray["updated_content_doc_crypto_hash_verification_result"] = false;
				
				return $responseArray;
				
			}
			
		} else {
			
			//$responseArray["primary_key"] = $updatedValuesArray[$singlePrimaryKeyColumnName];
			$responseArray["primary_key"] = "";
			$responseArray["row_content_received"] = false;	
			$responseArray["row_tampering_status"] = false;
			$responseArray["update_query_status"] = false;
			$responseArray["updated_content_doc_crypto_hash_verification_result"] = false;
			
			return $responseArray;
			
		}
		
		
		
		
	}

	//Update Query with Row Level Doc Crypto Hash Verification, to handle Updation of MULTIPLE ROWS. This is useful with Single Column based Primary Key Scenario
	public function updateMultiple($updateQuerySql, $updateQueryValuesArray, $tableName, $whereParameterColumnName, $whereParameterColumnValue)
	{
		$updateQueryResult = array();
		
		$singlePrimaryKeyColumnName = "";
		
		//Get Table specific Primary key Details
		$singlePrimaryKeyColumnName = $this->easeAmyMysql->getTableRelSinglePrimaryKeyColumnName($tableName);
		//echo "single_primary_key_column_name: " . $singlePrimaryKeyColumnName . "\n"; 
		
		
		$whereParameterColumnNameNamedParameter = ":" . $whereParameterColumnName;
			
		$selectQuerySql = "SELECT * FROM " . $tableName . " WHERE " . $whereParameterColumnName . " LIKE " . $whereParameterColumnNameNamedParameter;
		$selectQueryValuesArray = array("$whereParameterColumnNameNamedParameter" => $whereParameterColumnValue);
		
		/* echo "select_query_sql: " . $selectQuerySql . "\n";
		var_dump($selectQueryValuesArray);
		 */
		 
		$selectQueryResult = $this->easeAmyMysql->executeQuery($selectQuerySql, $selectQueryValuesArray, "selectMultiple");
		
		if(count($selectQueryResult) > 0) {			
			
			foreach ($selectQueryResult as $selectQueryResultRow) {
				
				//var_dump($selectQueryResultRow);
				
				$updateQueryResult[] = updateSingle($updateQuerySql, $updateQueryValuesArray, $tableName, $singlePrimaryKeyColumnName, $selectQueryResultRow[$singlePrimaryKeyColumnName]);
				
			}

		}
		
		return $updateQueryResult;
		
	}


	/*//Update Query for specific fields, with Row Level Doc Crypto Hash Verification. (TO BE DONE LATER)
	public function updateSingleWihoutDocCryptoHashVerification($updateQuerySql, $updateQueryValuesArray, $tableName, $whereParameterColumnName, $whereParameterColumnValue)
	{
		$responseArray = [];
		
		$singlePrimaryKeyColumnName = "";
		
		//Get Table specific Primary key Details
		$singlePrimaryKeyColumnName = ea_get_table_rel_single_primary_key_column_name($tableName);
		//echo "single_primary_key_column_name: " . $singlePrimaryKeyColumnName . "\n"; 
		
		
		//echo "UPDATE QUERY SCENARIO: \n";
		$select_query_primary_key_named_parameter = ":" . $whereParameterColumnName;
			
		$selectQuerySql = "SELECT * FROM " . $tableName . " WHERE " . $whereParameterColumnName . " LIKE " . $select_query_primary_key_named_parameter;
		$selectQueryValuesArray = array("$select_query_primary_key_named_parameter" => $whereParameterColumnValue);
		
		
		$select_query = $dbcon->prepare($selectQuerySql);
		$select_query->execute($selectQueryValuesArray);
			
		if ($select_query->rowCount() > 0) {
			
			$select_query_result = $select_query->fetch();
			
			//echo "select_query_result: inside updatewith docchash verification function \n";
			//var_dump($select_query_result);
			
			$retrieved_row_rel_doc_crypto_hash_from_db = array_pop($select_query_result);
			//echo "retrieved_row_rel_doc_crypto_hash_from_db: " . $retrieved_row_rel_doc_crypto_hash_from_db . "\n";
			
			//echo "array after removing existing row related digital signature: \n";
			//var_dump($select_query_result);
			
			
			
			$named_parameter_removed_received_content = array();
			$named_parameter_added_total_content = array();
			
			//Do Remove Colon, from the Received Modification Content
			foreach ($updateQueryValuesArray as $updateQueryValuesArrayKey => $updateQueryValuesArrayValue) {
			
				$column_name = substr($updateQueryValuesArrayKey, 1);
				
				$named_parameter_removed_received_content["$column_name"] = $updateQueryValuesArrayValue;
				
			}
			
			//echo "received content after removing the namedparameter \n";
			//var_dump($named_parameter_removed_received_content);
			
			//Do Merge Recent Content on to the Existing Row Data Array
			$updated_values_array = array_merge($select_query_result, $named_parameter_removed_received_content);
			
			//echo "updated_values_array array: \n";
			//var_dump($updated_values_array);
			
			//Create Digital Signature, for the Updated Row Content
			$updated_row_array_ds = createDigitalSignature($updated_values_array);
			//echo "updated_row_array_ds: " . $updated_row_array_ds . "\n";
			
			//Verify Created Digital Signature
			$updated_content_signature_check_result = validateDigitalSignature($updated_values_array, $updated_row_array_ds);
			
			//echo "updated_content_signature_check_result: " . $updated_content_signature_check_result . "\n";
			
			if ($updated_content_signature_check_result === true) {
				
				//echo "Primary_Key: " . $updated_values_array[$singlePrimaryKeyColumnName] . " - updated_content_signature_check_result: " . $updated_content_signature_check_result . " ( SIGNATURE IS VALID and TRUE)\n";
				
				//Add Digital Signature to the Row Array
				//$updated_values_array["doc_crypto_hash"] = $updated_row_array_ds;
				$named_parameter_removed_received_content["doc_crypto_hash"] = $updated_row_array_ds;
				$named_parameter_removed_received_content[$singlePrimaryKeyColumnName] = $updated_values_array[$singlePrimaryKeyColumnName];
				
				
				//var_dump($named_parameter_removed_received_content);
				//Do Convert Array key into named parameter, from the received row content
				foreach ($named_parameter_removed_received_content as $named_parameter_removed_received_content_key => $named_parameter_removed_received_content_value) {
				
					$named_parameter_key = ":" . $named_parameter_removed_received_content_key;
					
					$named_parameter_added_total_content["$named_parameter_key"] = $named_parameter_removed_received_content_value;
					
				}
				
				//echo "total content, after adding named parameter \n";
				//var_dump($named_parameter_added_total_content);
				//echo "update_query_sql: " . $updateQuerySql . "\n";
				
				 
				//Do Update Query
				$update_query = $dbcon->prepare($updateQuerySql);
				
				if($update_query->execute($named_parameter_added_total_content)) {
				   
				   // echo "update query successful \n";
					$responseArray["primary_key"] = $updated_values_array[$singlePrimaryKeyColumnName];
					$responseArray["row_content_received"] = true;
					$responseArray["update_query_status"] = true;
					$responseArray["updated_content_doc_crypto_hash_verification_result"] = true;
					
					return $responseArray;
				   
				} else {
					
					//echo "error with update query \n";
					$responseArray["primary_key"] = $updated_values_array[$singlePrimaryKeyColumnName];
					$responseArray["row_content_received"] = true;
					$responseArray["update_query_status"] = false;
					$responseArray["updated_content_doc_crypto_hash_verification_result"] = true;
					
					return $responseArray;
					
				}
				
			} else {
				
				//echo "Primary_Key: " . $updated_values_array[$singlePrimaryKeyColumnName] . " - updated_content_signature_check_result: " . $updated_content_signature_check_result . " ( SIGNATURE IS INVALID and FALSE)\n";
				
				$responseArray["primary_key"] = $updated_values_array[$singlePrimaryKeyColumnName];
				$responseArray["row_content_received"] = true;
				$responseArray["update_query_status"] = false;
				$responseArray["updated_content_doc_crypto_hash_verification_result"] = false;
				
				 
				return $responseArray;
				
			}
			
			
			
		} else {
			
			$responseArray["primary_key"] = "";
			$responseArray["row_content_received"] = false;	
			$responseArray["update_query_status"] = false;
			$responseArray["updated_content_doc_crypto_hash_verification_result"] = false;
			
			// echo "row does not exist \n";
			return $responseArray;
			
		}
		
		
	}*/

	//Delete Query with Row Level Doc Crypto Hash Verification. This is useful with Single Column based Primary Key Scenario
	public function deleteSingle($tableName, $primaryKeyColumnName, $primaryKeyColumnValue)
	{
		$responseArray = [];
		
		$selectQueryPrimaryKeyNamedParameter = ":" . $primaryKeyColumnName;
			
		$selectQuerySql = "SELECT * FROM " . $tableName . " WHERE " . $primaryKeyColumnName . " LIKE " . $selectQueryPrimaryKeyNamedParameter;
		$selectQueryValuesArray = array("$selectQueryPrimaryKeyNamedParameter" => $primaryKeyColumnValue);
		
		$selectedRowVerificationResult = $this->selectSingle($selectQuerySql, $selectQueryValuesArray);
		
		if ((count($selectedRowVerificationResult) > 0) && ($selectedRowVerificationResult["row_content_received"] === true)) {
			
			if ((count($selectedRowVerificationResult["row_content_excluding_ds"]) > 0) && ($selectedRowVerificationResult["row_rel_doc_crypto_hash"] != "") && (!is_null($selectedRowVerificationResult["row_rel_doc_crypto_hash"]))) {
				
				$deleteQuerySql = "DELETE FROM " . $tableName . " WHERE " . $primaryKeyColumnName . " LIKE " . $selectQueryPrimaryKeyNamedParameter;
				$deleteQueryValuesArray = array("$selectQueryPrimaryKeyNamedParameter" => $primaryKeyColumnValue);
				
				$queryResult = $this->easeAmyMysql->executeQuery($deleteQuerySql, $deleteQueryValuesArray, "delete");
	
	
				if($queryResult === true) {
					
					$responseArray["primary_key"] = $primaryKeyColumnValue;
					$responseArray["row_content_received"] = true;
					$responseArray["row_tampering_status"] = false;
					$responseArray["delete_query_status"] = true;
					
					return $responseArray;
					
				} else {
					
					$responseArray["primary_key"] = $primaryKeyColumnValue;
					$responseArray["row_content_received"] = true;
					$responseArray["row_tampering_status"] = false;
					$responseArray["delete_query_status"] = false;
					
					return $responseArray;
					
				}
				
			} else {
				
				$responseArray["primary_key"] = $primaryKeyColumnValue;
				$responseArray["row_content_received"] = true;
				$responseArray["row_tampering_status"] = true;
				$responseArray["delete_query_status"] = false;
				
				return $responseArray;
				
			}
			
		} else {
			
			$responseArray["primary_key"] = $primaryKeyColumnValue;
			$responseArray["row_content_received"] = false;
			$responseArray["row_tampering_status"] = false;
			$responseArray["delete_query_status"] = false;
			
			return $responseArray;
			
		}
		
	}
	
	public function getRowDataAndDoccryptohash(array $rowData) {
		
		$responseArray = [];
		
		$rowRelOtherColumnsArray = [];
		
		$docCryptoHash = null;
		
		foreach ($rowData as $key => $value) {
			
			if ($key != "doc_crypto_hash") {
				
				$rowRelOtherColumnsArray[$key] = $value;
				
			} else {
				
				$docCryptoHash = $value;
				
			}
			
		}
		
		$responseArray["rowrelOtherColumnData"] = $rowRelOtherColumnsArray;
		$responseArray["docCryptoHash"] = $docCryptoHash;
		
		
		return $responseArray;

	}
	
	//$this->pool->close();
}
?>