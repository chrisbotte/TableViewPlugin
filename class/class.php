<?php

require_once 'fcns/phpfcns.php';
require_once 'RestCallRequest.php';
define(SRCH_ALL, "-99999");

// sample of tv_config object:
// [config:protected] => Array
//         (
//             [pid] => 78
//             [event] => 169
//             [top_title] => PIM3 Dashboard
//             [edit_form_name] => edit
//             [import_form_name] => native_import
//             [form_submit_location] => submit.php
//             [pkey] => encntr_pk
//             [f_radio] => Array
//                 (
//                     [room_type] => Array
//                         (
//                             [display] => Unit
//                             [values] => Array
//                                 (
//                                     [1] => MSICU
//                                     [2] => 11S
//                                     [3] => 8S
//                                     [-99999] => ALL
//                                 )

//                         )

//                     [confirmed] => Array
//                         (
//                             [display] => Review Status
//                             [values] => Array
//                                 (
//                                     [0] => No
//                                     [1] => Yes
//                                     [-99999] => ALL
//                                 )

//                         )

//                     [f_date_fields] => Array
//                         (
//                             [display] => Date Field to Filter
//                             [show_date_filter] => 1
//                             [values] => Array
//                                 (
//                                     [dob] => Date of Birth
//                                     [dod] => Date of Death
//                                     [icu_discharge_date] => ICU Discharge Date
//                                     [icu_admit_date] => ICU Admit Date
//                                     [-99999] => No Date Filter
//                                 )

//                         )

//                 )

//             [hdrs] => Array
//                 (
//                     [q1] => QCC 1
//                     [q2] => QCC 2
//                     [q3] => QCC 3
//                     [q4] => QCC 4
//                     [q5] => QCC 5
//                     [q6_final] => QCC 6
//                     [q7_final] => QCC 7
//                     [q8_final] => QCC 8
//                 )

//             [qcc_yes_codes] => Array
//                 (
//                     [0] => 1386
//                     [1] => 1388
//                     [2] => 1390
//                     [3] => 1391
//                     [4] => 1392
//                     [5] => 1394
//                     [6] => 1396
//                     [7] => 1
//                 )

//             [gc1] => PIM3
//             [gc2] => POD%
//         )


class tv_config{
	protected $config;
	protected $meta;
	protected $f_radio;
	protected $post;
	protected $pid;

	public function __construct($p, $pid){
	
		$this->sanitizePost($p);
		$c = getMetadata();
		$this->meta = getFieldInfo($c);
		$this->pid = $pid;

		$json_data = file_get_contents('config_'.$this->pid.'.json');
		
		//err handling...
		if($json_data !== FALSE)
			$this->config = json_decode($json_data, true);
		else
			throw new exception('ERR: config_XX.json file can not be read.'); 
		
		if($this->config == null)
			throw new exception('ERR: config_XX.json could not be decoded.'); 
		
		//checks if config file read is mapped to correct pid
		if($this->config['pid'] != $this->pid){
			throw new exception('ERR: Incorrect config_XX.json file loaded'); 
		}
		
		$this->f_radio = $this->config['f_radio'];
	}

	//accessor functions
	public function getConfig(){
		return $this->config;
	}
	
	public function getMeta(){
	return $this->meta;
	}

	public function getDateFieldFilter(){
		return $this->f_radio;
	}

	// utility fcn to sanitize POST array
	private function sanitizePost($p){
		array_map("strip_tags", $p);
		array_map("htmlentities", $p);
		$this->post = $p;
	}
}

class tv_data extends tv_config{
	protected $logic;
	protected $start;
	protected $end;
	protected $f_date_fields;
	protected $data;
	protected $edit_data;
	protected $rows;


	public function __construct($p, $pid){
		parent::__construct($p, $pid);
		$this->setPostVars();
		$this->extractData();
	}

	protected function genericRowCalc1(){
		// should be overloaded by specific class built for specific project
		return null;
	}

	protected function genericRowCalc2(){
		// should be overloaded by specific class built for specific project
		return null;
	}

	private function extractData(){
	
		$this->data = REDcap::getData ( $this->pid, 'array', null, REDCap::getFieldNames($this->config['import_form_name']), null, null,false,false,false, $this->logic);
		$this->edit_data = REDcap::getData ( $this->pid, 'array', null, REDCap::getFieldNames($this->config['edit_form_name']), null, null,false,false,false, $this->logic);
		$this->rows = count($this->data);
	}

	// initialize data from post into class config array
	// two possibilities: 
	// Page was called without a POST array set or user elected to filter data
	private function setPostVars(){
		
		//user elects to filter data and refresh page...
		if(!empty($this->post)){
			
			$this->f_date_fields = null;
			$this->f_date_fields = $this->post['f_date_fields'];
			$this->start = $this->post['start'];
			$this->end = $this->post['end'];
			$this->logic = '';

			if($this->f_date_fields != SRCH_ALL && isset($this->post['f_date_fields']) ){
				$this->logic = ' datediff("'.cDate_ymd($this->start).'",['.$this->f_date_fields.'],"d","ymd",true) >= 0 and datediff("'.cDate_ymd($this->end).'",['.$this->f_date_fields.'],"d","ymd",true) <= 0 ';
			}
			
			unset($this->post['f_date_fields']);
			unset($this->post['start']);
			unset($this->post['end']);
			unset($this->post['submit']);
			
			// step through remaining POST vars and build logic
			// each $key : $val pair represents the field to filter and the value to filter on 
			foreach($this->post as $key=>$val){
				if($val == SRCH_ALL) 
			 		$this->logic .= ""; 
				else
					$this->logic .= ($this->logic == '') ? "[".$key."]=".$val : " and [".$key."]=".$val ;
			}
		}
		// user has opened page without filtering...
		else{
			
			$this->logic = '';
			$this->start = null;
			$this->end = null;
		}
	}
}



class tv_ui extends tv_data{

	// represent sections of dashboard page
	private $table_html;
	private $search_html;
	protected $summary_html;
	protected $gc_total;
	protected $gc2_total;


	public function __construct($p, $pid){
		parent::__construct($p, $pid);
		$this->buildTV_Table();
		$this->buildTV_Search();
		$this->buildSummarySection();
	}

	//accessor fcns
	public function getTableHTML(){
		return $this->table_html;
	}

	public function getSearchHTML(){
		return $this->search_html;
	}

	public function getUIHdr(){
		return $this->config['top_title'];
	}

	public function getSummaryHTML(){
		return $this->summary_html;
	}

	private function buildSummarySection(){
		// should be overloaded by specific class built for specific project
		return null;
	}

	private function buildTV_Search(){

		$html='<form id="srch" action="index.php?pid='.$this->pid.'" method="POST">';

			// using data in config array (f_radio), build drop down menus and date selection filter fields to place inside form
			foreach ($this->f_radio as $key => $val){
				$id = $key;
				$disp = $val['display'];
				$vals = $val['values'];
				$input = '';
			
				// build start and and date filter input boxes
				if(array_key_exists ('show_date_filter' , $val )){
					$input = '
						<div class="sgrp">
							<div class="sgrp rlabel">Between</div> 
							<input class=" dfilter"  type="text" id="start" value = "'.$this->start.'" name="start" required placeholder="Enter a Start Date">
							<div class="sgrp rlabel">AND</div>
							<input class=" dfilter"  type="text" id="end" value="'.$this->end.'" name="end" required placeholder="Enter a End Date">
						</div>';
				}

				$html .= '<div class="rgroup"><div class="rlabel">' . $disp . "</div>";
				$html .= "<select class='radio_filter' id='".$id."' name ='".$id."'>";
				
				// check to see if post array provided default values.  if so, find val and pre-pop the coresponding radio fields...
				foreach($vals as $code=>$label) { 
					
					$default = '';

					if(empty($_POST))
						$curr_key_code = SRCH_ALL;
					else	
						$curr_key_code = $_POST[$id];

					if($curr_key_code == $code)
					 	$default = " selected='selected' ";

					$html .= "<option value='".$code."'".$default.">".$label."</option>";
				}

				$html .= '</select>'.$input.'</div><hr>';
			}
		// append submit button to form and close the form...
		$html .= '<input type="submit" id="butt" value="Filter" />';
		$html .= "</form>";
		$this->search_html = $html;
	}

	private function buildTV_Table(){
		// initialize vals
		$csncnt = 0;
		$podsum = 0;
		$this->gc_total = 0;
		$this->gc2_total = 0;
		$thbuilt = false;
		$cntr = 0;
	
		// begin building table
		$tbl = '<table id="example" class="display" cellspacing="0">';
		
		// $this->data array format
		// Array
		// (
		 //    [999999 04/07/15 03:29:00 PM] => Array
		 //        (
		 //            [169] => Array
		 //                (
		 //                    [encntr_pk] => 999999 04/07/15 03:29:00 PM
		 //                    [patient_key] => 888888
		 //                    [encounter_key] => 777777
		 //                    [contact_serial_number] => 666666
		 //                    [encntr_id] => 
		 //                    [patient_name] => Smith, John
		 //                    [gender] => M
		 //                    [dod] => 
		 //                    [dob] => 2014-08-19
		 //                    [mrn] => 333333
		 //                    [admit_date] => 2014-08-22 14:55
		 //                    [pre_icu_location] => 10E INFANT PRE-SCHOOL
		 //                    ...
		 //                    ...

		// break into first level...
		foreach ($this->data as $pk => $ev) {
			
			// traverse second level
			foreach ($ev as $evid => $arr) {
		
				$cntr += 1;
		
				// build header row for table
				// calc fields appended to right most cols of table
				// labels inside RC forms are used unless alternative was provided in config.json (via config array)
				if(!$thbuilt){
					$tbl .= "<thead><tr>";
		
					// begin processing innermost part of data array....
					foreach ($arr as $key => $value) {
					
						$newhdr = (array_key_exists($key, $this->config['hdrs'])) ? $this->config['hdrs'][$key]  : $this->meta[$key]['field_label'] ;
						$tbl.= "<th>" . $newhdr ."</th>";
					}
		
					$tbl .= (array_key_exists("gc1", $this->config)) ? "<th>".$this->config['gc1']."</th>" : "";
					$tbl .= (array_key_exists("gc2", $this->config)) ? "<th>".$this->config['gc2']."</th>" : "";
					
					$tbl .= "</tr></thead><tbody>";
					$thbuilt = true;
				}

				// begin displaying actual data in rows
				// skips any edit fields
				// will look for presence of meta data labels for yes/no, radio, drop down fields. if present will use text label, otherwise will use raw value
				foreach ($arr as $key => $value) {

					// pkey field must be a link to direct user to actual data entry form
					if($key == $this->config['pkey'])
						$tbl .= "<td><a target='_blank' href='http://redcap-dev/redcap_ddp/redcap_v5.12.0/DataEntry/index.php?pid=".$this->pid."&page=edit&id=".$value."'>" . $value . "</a></td>";
					else{
						// if value is an array (signifying that field to be displayed is a checkbox field), step through array and build 1 cell with all text vals
						if(is_array($value)){
							$tval='';
							foreach ($value as $check_code => $check_val) {
								$tval .=  ($check_val == 1) ? $this->meta[$key]['field_meta'][$check_code] . "<br>" : "";
							}
						}
						else{ // otherwise value id from a single val field...
							$tval = (is_array($this->meta[$key]['field_meta'])) ? $this->meta[$key]['field_meta'][$value] : $value ; 					
						}
						// check if there is any manual data entered for this field in edit form
						$tbl .= "<td>" . $tval . '<br>'. $this->lookupManualData($pk, $this->config['event'], $key) ."</td>";
					}
				}
			
			// done with data in array, now build calc field vals and insert into table (if desired via config array)
			if(array_key_exists("gc1", $this->config)){
				$gc = $this->genericRowCalc1($this->data[$pk][$evid],$this->edit_data[$pk][$evid],$cntr); 
				$this->gc_total += $gc;
				$tbl .= "<td><a id='".$cntr."' class='detail_link' href='#'>".$gc."</a></td>";
			}

			if(array_key_exists("gc2", $this->config)){
				$gc2 = $this->genericRowCalc2($gc); 
				$this->gc2_total += $gc2;
				$tbl .= "<td>".$gc2."</td>";
			}

			$tbl .= "</tr>";
			}				
		}

		$tbl .= "</tbody></table>";
		$this->table_html = $tbl;
	}

	private function lookupManualData($pk, $ev, $key){

		// fcn checks to see if there is a corresponding value that was mapped to the edited field..
		
		$value = $this->edit_data[$pk][$ev][$key . '_edit'];

		$tval='';

		// if a checkbox field that allows multiple selections...
		if(is_array($value)){ 
			foreach ($value as $check_code => $check_val) {
				$tval .=  ($check_val == 1) ? $this->meta[$key]['field_meta'][$check_code] . "<br>" : "";
			}
		}
		// if a single value field...
		else{
			$tval .= (is_array($this->meta[$key]['field_meta'])) ? $this->meta[$key]['field_meta'][$value] : $value ; 					
		}
	
		// if a val exists, wrap in a span tag and return string
		return ($tval == '') ? "" : "  <span class='manual'>(" . $tval .")</span>   ";
	}
}


class tv_iu_pim3 extends tv_ui{

	// functions defined here wil override fcns used in parent class
	// this will allow full use of parent class with customizability for summary and calcs to display
	// tv_ui parent class will be extended for each project so summary section and calcs can be customized

	protected function genericRowCalc2($val){
		return round( 100*(exp($val) / (1 + exp($val))), 2);
	}

	protected function genericRowCalc1($vals, $edits, $i){

		$yes_codes = $this->config['qcc_yes_codes'];
		$final = $vals;

		$div = "<div style='display:none;' id='detail_".$i."'><table class='detail'><tr>
					<td class='calc_hdr'>Term</td>
					<td class='calc_hdr'>Calc Details</td>
					<td class='calc_hdr'>Result</td></tr>";


		if($edits['mr_manual']==1){
			$final['hemoglobin'] = $edits['hemoglobin_edit'];
			$final['bicarb_arterial'] = $edits['bicarb_arterial_edit'];
			$final['ph_arterial'] = $edits['ph_arterial_edit'];
			$final['systolic_bp'] = $edits['systolic_bp_edit'];
			$final['fio2'] = $edits['fio2_edit'];
			$final['po2'] = $edits['po2_edit'];
		}



		if($edits['qcc_manual']==1){
			$final['q1'] = $edits['q1_edit'];
			$final['q3'] = $edits['q3_edit'];
			$final['q4'] = $edits['q4_edit'];
			$final['q5'] = $edits['q5_edit'];
			$final['q6_final'] = $edits['q6_final_edit'];
			$final['q7_final'] = $edits['q7_final_edit'];	
			$final['q8_final'] = $edits['q8_final_edit'];
		}


		$final['q1'] = (in_array($final['q1'], $yes_codes)) ? -0.5378 : 0;
		$final['q2'] = (in_array($final['q2'], $yes_codes)) ? 1 : 0;
		$final['q4'] = (in_array($final['q4'], $yes_codes)) ? 0.9763 : 0;
		$final['q5'] = (in_array($final['q5'], $yes_codes)) ? 3.8233 : 0;
		
		


		if(in_array($final['q8_final'], $yes_codes)){
			$final['q8_final'] = 1.6225;
			$final['q7_final'] = 0;
		}
		else{
			$final['q8_final'] = 0;
			$final['q7_final'] = (in_array($final['q7_final'], $yes_codes)) ? 1.0725 : 0;
		}

		if($final['q7_final']==0 && $final['q8_final']==0){
			$final['q6_final'] = (in_array($final['q6_final'], $yes_codes)) ? -2.1766 : 0;
		}
		else{
			$final['q6_final'] = 0;
		}

		// $final['q7_final'] = (in_array($final['q7_final'], $yes_codes)) ? 1.0725 : 0;
		// $final['q8_final'] = (in_array($final['q8_final'], $yes_codes)) ? 1.6225 : 0;

		if(in_array($final['q3'], $yes_codes)){

			switch ($final['q3']) {

				case 1390:
					$final['q3']= -1.2246;
					break;
				
				case 1391:
					$final['q3']= -0.8762;
					break;
				
				case 1392:
					$final['q3']= -1.5164;
					break;
			}
		}
		else{
			$final['q3'] = 0;
		}


		if ($final['hemoglobin'] != null && $final['bicarb_arterial'] != null && $final['ph_arterial'] != null ){
			$base_excess = (1-0.0143*$final['hemoglobin'])*($final['bicarb_arterial']-(9.5+1.63*$final['hemoglobin'])*(7.4-$final['ph_arterial'])-24);
			$div .= "<tr class='calc_detail'><td>Base Excess Sub-Calculation</td><td>(1-0.0143*<u>".$final['hemoglobin']."</u>)*(<u>".$final['bicarb_arterial']."</u>-(9.5+1.63*<u>".$final['hemoglobin']."</u>)*(7.4-<u>".$final['ph_arterial']."</u>)-24)</td><td>".round($base_excess,4)."</td></tr>";
			$base_term = (.0671 * abs($base_excess));
			$div .= "<tr class='calc_detail'><td>Base Excess Final</td><td>(.0671 * abs(<u>" . $base_excess . "</u>))</td><td>" . round($base_term,4) . "</td></tr>" ;
		}
		else{
			$base_excess = 0;
			$base_term = 0;
			$div .= "<tr class='calc_detail'><td>Base Excess Final</td><td>Base Excess Terms Are Empty (Hemoglobin, PH and/or Bicarb Arterial)</td><td>0</td></tr>";
		}


		$bp_term = ($final['systolic_bp'] * -.0431) + (0.1716 * (($final['systolic_bp'] * $final['systolic_bp'])/1000));
		$div .= "<tr class='calc_detail'><td>BP Term</td><td>(".$final['systolic_bp']." * -.0431) + (0.1716 * ((".$final['systolic_bp']." * " . $final['systolic_bp']. ")/1000)</td><td>". round($bp_term,4) ."</td></tr>";


		if ($final['fio2'] != null && $final['po2'] != null){
			$pofio_term = (.4214*(100*( $final['fio2'] / $final['po2'])));

			$div .="<tr class='calc_detail'><td>PO2/FIO2</td><td>(.4214*(100*( <u>".$final['fio2']."</u>/<u>". $final['po2']."</u>)))</td><td>".round($pofio_term,4)."</td></tr>";
		}else{
			$div .="<tr class='calc_detail'><td>PO2/FIO2</td><td>Terms are empty (FIO2 and/or PO2)</td><td>0</td></tr>";
		}


		$qcc_term = $final['q1'] + $final['q3'] + $final['q4'] + $final['q5'] + $final['q6_final'] + $final['q7_final'] + $final['q8_final'];
		$div .= "<tr class='calc_detail'><td>QCC - 1 (ELECTIVE)</td><td>".$final['q1']."</td><td>".$final['q1']."</td></tr>";
		$div .= "<tr class='calc_detail'><td>QCC - 3 (RECOV FROM SURG)</td><td>".$final['q3']."</td><td>".$final['q3']."</td></tr>";
		$div .= "<tr class='calc_detail'><td>QCC - 4 (MECH VENT)</td><td>".$final['q4']."</td><td>".$final['q4']."</td></tr>";
		$div .= "<tr class='calc_detail'><td>QCC - 5 (PUPILS)</td><td>".$final['q5']."</td><td>".$final['q5']."</td></tr>";
		$div .= "<tr class='calc_detail'><td>QCC - 6 (LOW-RISK)</td><td>".$final['q6_final']."</td><td>".$final['q6_final']."</td></tr>";
		$div .= "<tr class='calc_detail'><td>QCC - 7 (HIGH-RISK)</td><td>".$final['q7_final']."</td><td>".$final['q7_final']."</td></tr>";
		$div .= "<tr class='calc_detail'><td>QCC - 8 (VERY HIGH-RISK)</td><td>".$final['q8_final']."</td><td>".$final['q8_final']."</td></tr>";	
		$div .= "<tr class='calc_detail'><td>CONSTANT</td><td>-1.7928</td><td>-1.7928</td></tr>";	
		$div .= "<tr class='calc_total'><td>Total</td><td></td><td>".round($qcc_term + $base_term + $pofio_term  + $bp_term - 1.7928, 4)."</td></tr></table></div>";
		
		echo $div;
		
		return round($qcc_term + $base_term + $pofio_term  + $bp_term - 1.7928, 4);
	}

	protected function buildSummarySection(){
		
		$this->calc_data = REDcap::getData ( $this->pid, 'array', null, "dod_edit", null, null,false,false,false,  
				'datediff("'.cDate_ymd($this->start).'",[dod_edit],"d","ymd",true) >= 0 and datediff("'.cDate_ymd($this->end).'",[dod_edit],"d","ymd",true) <= 0');
		$keys = array_keys($this->calc_data);
		$dodcnt = 0;
		$ev = $this->config['event'];
			
		for ($i=0; $i < count($keys) ; $i++) {
			$ckey = $keys[$i]; 
			$dodcnt += ($this->calc_data[$ckey][$ev]['dod_edit'] == '') ? 0 : 1 ;
		}

		$podtot = $this->gc2_total / 100;
		$smr = round($dodcnt / $podtot,3);
		
		$smrdisplay = "
			<div>
				<span class='calc_detail'>ICU Admit Count:</span><br>
				$this->rows<br><br>
				<span class='calc_detail'>POD Total:</span><br>
				$podtot<br><br>
				<span class='calc_detail'>DOD Count:</span><br>
				$dodcnt<br><br>
				<span class='calc_detail'>Final SMR:</span><br>
				$smr
			</div>
			";

		$this->summary_html = $smrdisplay;
	}

}

?>