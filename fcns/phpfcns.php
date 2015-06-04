<?php

// RC metadata stored in format 1, Op1 | 2, Opt2 | 3, Opt3
// fcn breaks string into array for processing
function mapStringToAssocArray($string, $first_delim, $second_delim){
	
	if($string == null)
		return null;
	
	$a = explode($first_delim, $string);	
	$final = array();
	
	if($a[0]=="" || !strpos($string,","))
		return null;
	
	foreach ($a as $key => $value) {
		$t_key = trim(substr($value, 0, strpos($value,",")));
		$t_val = trim(substr($value,  strpos($value,",")+1));
		$final[$t_key] = $t_val;
	}

	unset($a);
	unset($t_key);
	unset($t_val);
	
	return $final;
}

// will only work with metadata arrays assoc with this kind of dashboard
// builds array of project fields with associated metadata codes and labels
// utility fcn 
function getFieldInfo($c){
		
	$fields = REDCap::getFieldNames($c['instr']);
	for ($i=0; $i < count($fields); $i++) { 
		$cfld = $fields[$i];
		$fmeta = ($c[$i]['field_type']=='yesno' || $c[$i]['field_type']=='truefalse') 
			? array('No','Yes') 
			: mapStringToAssocArray($c[$i]['select_choices_or_calculations'], '|', ',');
		$nfields[$cfld] = array('field_name' => $c[$i]['field_name'],
								'field_label' => $c[$i]['field_label'],
								'field_type' => $c[$i]['field_type'],
								'field_meta' => $fmeta
							);
	}

	$cfld = $c['instr'].'_complete';
	$nfields[$cfld] = 	array(
								'field_name' => $c['instr'].'_complete',
								'field_label' => 'Complete?',
								'field_type' => 'radio',
								'field_meta' => array('Inc','Unverified', 'Comp')
							);
	return $nfields;
}

//generic data conversion fcn
function cDate_mdy($date){

	if($date != null && $date != ''){
		$t = strtotime($date);
		return date('m/d/Y',$t);
	}
	else{
		return null;
	}
}

//generic data conversion fcn
function cDate_ymd($date){

	if($date != null && $date != ''){
		$t = strtotime($date);
		return date('Y-m-d',$t);
	}
	else{
		return null;
	}
}

//need to make generic
//placed here for time being
function getMetadata(){
	

	$fields = REDCap::getFieldNames('native_import'); 
	$data = array
			(
			'fields'=> $fields,
			'content' => 'metadata', 
			'type' => 'flat', 
			'format' => 'json', 
			'token' => '4325947E3EFB722B390C5CB7D3C861BF'
			); 
	$request = new RestCallRequest('http://redcap-dev/redcap_ddp/api/', 'POST', $data);
	$request->execute();
	//TO DO: err handling for API
	$data_arr = json_decode($request->getResponseBody(), true);
	return $data_arr;
}


?>