<?php
	const INVALIDINPUT = 'INP1';
	global $isDebugMode;
	$isDebugMode = true;
	
	
	function generateResponseData($status, $data = NULL, $error_code = NULL ,$error_msg = NULL){
		global $isDebugMode;
		
		$response = array();
		$response["status"] = $status;
		
		if (!is_null($data)){
			$response["data"] = $data;
		}
		
		if (!is_null($error_code)){
			$response["error_code"] = $error_code;	
		}
		
		if ($isDebugMode == true){
			if (!is_null($error_msg)){
				$response["error_msg"] = $error_msg;	
			}
		}
		
		return json_encode($response);
	}	
	//
	
	function getTimeStamp(){
		$timezone = new DateTimeZone("Asia/Kolkata");
		$date = new DateTime();
		$date->setTimezone($timezone );
		$output_data = $date->format( 'Y-m-d H:i:s' );
		return $output_data;
	}
	
	function getDateNow($fmt = null){
		$timezone = new DateTimeZone("Asia/Kolkata");
		$date = new DateTime();
		$date->setTimezone($timezone );
		if (is_null($fmt)){
			$output_data = $date->format( 'Y-m-d' );
		}
		else{
			$output_data = $date->format($fmt);
		}
		
		return $output_data;
	}
	
	
?>