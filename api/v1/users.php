<?php
	// required headers
	header("Access-Control-Allow-Origin: *");
	header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
	header('Access-Control-Allow-Headers: X-Requested-With');
	header("Content-Type: application/json; charset=UTF-8");
	
	// include database and object files
	require_once ('config/data_helper.php');
	include_once 'config/database.php';
	// instantiate database and product object
	Global $dbCon, $paramList;
	$database = new Database();
	$dbCon = $database->getConnection();
	
	
	$method = $_SERVER['REQUEST_METHOD'];
	$inputStr = file_get_contents("php://input");	
	$paramList = json_decode($inputStr, true);	
	
	switch ($method) {
		case 'GET':
			//READ
			$id = ( isset( $_GET['id'] ) && is_numeric( $_GET['id'] ) ) ? intval( $_GET['id'] ) : 0;
			$email = ( isset( $_GET['email'] ) && is_string( $_GET['email'] ) ) ?  $_GET['email']  : "";
						
			if ( $id != 0 )	{
				fetch_single($id);			
			}else if (!empty($email)){
				fetch_user_by_email($email);		
			} else {
				fetch_all();							
			}
			
			break;
		case 'POST':
			//CREATE
			create();
			break;
		case 'PUT':
			//UPDATE
			update();
			break;
		
		case 'DELETE':
			// DELETE			
			deleteRecord();
			break;
		default:
			// Invalid Request Method
			header("HTTP/1.0 405 Method Not Allowed");
			break;
	}
	//
	function fetch_all()
	{
		global $dbCon;	
		$data = array();		
			
		try {	
			$query = "SELECT * FROM users ORDER BY id";
			// prepare query statement
			$stmt = $dbCon->prepare($query);
			$stmt->setFetchMode(PDO::FETCH_ASSOC);	
			// execute query
			$stmt->execute();
			$num = $stmt->rowCount();			
			if($num>0){
				$data = $stmt->fetchAll();
				$response["records"] = $data;
			}
			
			$response = generateResponseData(1, $data);
			} catch (Exception $exception) {		
			//$exception->getMessage()
			$response = generateResponseData(0, NULL, $exception->getCode(), $exception->getMessage());
		}
		header('Content-Type: application/json');
		// show products data in json format	 
		echo ($response);	 
	}
	//
	function fetch_single($id){
		global $dbCon;	
		$data = array();	
		
		try {
			$query = "SELECT * FROM users WHERE id = :id";
			// prepare query statement
			$stmt = $dbCon->prepare($query);
			$stmt->setFetchMode(PDO::FETCH_ASSOC);		 
			// execute query
			$stmt->execute(array('id' => $id));
			$num = $stmt->rowCount();			
			if($num>0){
				$data = $stmt->fetchAll();
				$response["records"] = $data;
			}
			
			$response = generateResponseData(1, $data);
		} catch (Exception $exception) {		
			//$exception->getMessage()
			$response = generateResponseData(0, NULL, $exception->getCode(), $exception->getMessage());
		}
		header('Content-Type: application/json');
		// show products data in json format	 
		echo ($response);	 
		
	}
	
	function fetch_user_by_email($email){
		global $dbCon;	
		$data = array();	
		
		try {
			$query = "SELECT * FROM users WHERE email = :email";
			// prepare query statement
			$stmt = $dbCon->prepare($query);
			$stmt->setFetchMode(PDO::FETCH_ASSOC);		 
			// execute query
			$stmt->execute(array('email' => $email));
			$num = $stmt->rowCount();			
			if($num>0){
				$data = $stmt->fetchAll();
				$response["records"] = $data;
			}
			
			$response = generateResponseData(1, $data);
		} catch (Exception $exception) {		
			//$exception->getMessage()
			$response = generateResponseData(0, NULL, $exception->getCode(), $exception->getMessage());
		}
		header('Content-Type: application/json');
		// show products data in json format	 
		echo ($response);	 
		
	}	
	//
	function create()
	{
		global $dbCon, $paramList;			
		$inputValid = validateInsert($paramList);
				
		if ($inputValid == false){
			$response = generateResponseData(0, NULL, INVALIDINPUT, 'Invalid input');
			echo ($response);
			return false;
		}
		
		try {
			$dbCon->beginTransaction();
			$query = "INSERT INTO users(email, display_name, linked_id, social_id, social_type, active, created_at) VALUES (:email, :display_name, :linked_id, :social_id, :social_type, :active, :created_at)";
		// prepare query statement
			$stmt = $dbCon->prepare($query);
			$stmt->execute(array(
			'email' => $paramList['email'],
			'display_name' => $paramList['display_name'],			
			'linked_id' => $paramList['linked_id'],
			'social_id' => $paramList['social_id'],
			'social_type' => $paramList['social_type'],
			'active' => 1,
			'created_at' => getTimeStamp(),
			));
			
			$data['id'] =  $dbCon->lastInsertId();	
			$data['ref_code'] = getDateNow('Ymd');
			$data['active'] = "1";
			
			
			$query = "UPDATE users SET ref_code = :ref_code WHERE id = :id";
			$stmt = $dbCon->prepare($query);
			$stmt->execute(array(
			'id' => $data['id'],
			'ref_code' => $data['ref_code'],
			));
			
			$dbCon->commit();
			
			$response = generateResponseData(1, $data);
		} catch (Exception $exception) {
			$dbCon->rollBack(); 
			//$exception->getMessage()
			$response = generateResponseData(0, NULL, $exception->getCode(), $exception->getMessage());
		}
		header('Content-Type: application/json');
		// show products data in json format	 
		echo ($response);	 
	}
	//
	function update()
	{
		global $dbCon, $paramList;			
		$inputValid = validateUpdate($paramList);
				
		if ($inputValid == false){
			$response = generateResponseData(0, NULL, INVALIDINPUT, 'Invalid input');
			echo ($response);
			return false;
		}
		
		$allowed = ["name","mobile","pincode","date_of_birth","gender_id"];
		$updateValues = [];
		// initialize a string with `fieldname` = :placeholder pairs
		$setStr = "";
		// loop over source data array
		foreach ($allowed as $key)
		{
			if (isset($paramList[$key]) && $key != "id")
			{
				$setStr .= "`$key` = :$key,";
				$updateValues[$key] = $paramList[$key];
			}
		}
		$setStr = rtrim($setStr, ",");
		$updateValues['id'] = $paramList['id'];	
		$updateValues['updated_at'] = getTimeStamp();	
		
		try {
			$query = "UPDATE users SET $setStr , updated_at = :updated_at WHERE id = :id";
			$stmt = $dbCon->prepare($query);
			$stmt->execute($updateValues);
			
			//$response = generateResponseData(1, []);
			fetch_single($paramList['id']);
			
		} catch (Exception $exception) {		
			//$exception->getMessage()
			$response = generateResponseData(0, NULL, $exception->getCode(), $exception->getMessage());
			header('Content-Type: application/json');
			// show products data in json format		
			echo ($response);
		}		
			 
	}
	//
	function deleteRecord()
	{
		global $dbCon, $paramList;			
		
		/* if (! isset($paramList['id'])){
			$response = generateResponseData(0, NULL, INVALIDINPUT, 'Invalid input');
			echo ($response);
			return false;
		} */
		
		if(empty($_GET["id"])){
			$response = generateResponseData(0, NULL, INVALIDINPUT, 'Invalid input');
			echo ($response);
			return false;
		}
		$id = intval($_GET['id']);
		
		try {
			$dbCon->beginTransaction();
			//Add Other linked tables Query here
			$query = "DELETE FROM users WHERE id = :id";
			$stmt = $dbCon->prepare($query);
			$stmt->execute(array('id' => $id));
			
			$dbCon->commit();
			
			$response = generateResponseData(1, []);			
		} catch (Exception $exception) {		
			$dbCon->rollBack(); 			
			$response = generateResponseData(0, NULL, $exception->getCode(), $exception->getMessage());
		}
		
		header('Content-Type: application/json');
		// show products data in json format	 
		echo ($response);	
	}
	//
	function validateInsert($inputdata){
		if (! isset($inputdata['email'])){
			return false;
		}
		if (! isset($inputdata['display_name'])){
			return false;
		}		
		if (! isset($inputdata['linked_id'])){
			return false;
		}
		if (! isset($inputdata['social_id'])){
			return false;
		}
		if (! isset($inputdata['social_type'])){
			return false;
		}
		return true;		
	}
	//
	function validateUpdate($inputdata){
		if (! isset($inputdata['id'])){
			return false;
		}
		return true;		
	}
	
?>