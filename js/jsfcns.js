function submitValidate(startsubm, endsubm, fdate){

	if(!isDate(startsubm) || !isDate(endsubm)){
		alert("Invalid Date(s) provided as search criteria. Please correct.");
		return false;
	}

	if(endsubm < startsubm){
		alert("End Date provided is before Start Date. Please correct.");
		return false;
	}

	if(fdate != -99999 && (startsubm === null || startsubm === '' || endsubm === null || endsubm === '')){
		alert('Error: Please verify date search criteria. \n Must Enter a Start and End Date if filtering on a date field.');
		return false;
	}

	if(fdate == -99999 && (startsubm != null || endsubm != null ) ){
		alert('Error: Please verify date search criteria. \n Must Select a Date Field to Filter on.');
		return false;
	}


	return true;

}

function isDate(txtDate)  //credit: http://www.jquerybyexample.net/2011/12/validate-date-using-jquery.html
{
    var currVal = txtDate;
    if(currVal == '')
        return false;
    
    var rxDatePattern = /^(\d{1,2})(\/|-)(\d{1,2})(\/|-)(\d{4})$/; //Declare Regex
    var dtArray = currVal.match(rxDatePattern); // is format OK?
    
    if (dtArray == null) 
        return false;
    
    //Checks for mm/dd/yyyy format.
    dtMonth = dtArray[1];
    dtDay= dtArray[3];
    dtYear = dtArray[5];        
    
    if (dtMonth < 1 || dtMonth > 12) 
        return false;
    else if (dtDay < 1 || dtDay> 31) 
        return false;
    else if ((dtMonth==4 || dtMonth==6 || dtMonth==9 || dtMonth==11) && dtDay ==31) 
        return false;
    else if (dtMonth == 2) 
    {
        var isleap = (dtYear % 4 == 0 && (dtYear % 100 != 0 || dtYear % 400 == 0));
        if (dtDay> 29 || (dtDay ==29 && !isleap)) 
                return false;
    }
    return true;
}

