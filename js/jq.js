$(document).ready(function() {
				
	$.getScript("js/jsfcns.js", function(){});

	var start = $('#start').val();
	var end = $('#end').val();
	
	$('a.detail_link').click(function(){
		var id = $(this).attr('id');

		id = "#detail_" + id;

		var content = $(id).html();

		$('#dialog').html(content);

		$( "#dialog" ).dialog({
			height: 'auto',
			width: 'auto'
		});
	});

	// form filter submit
	$('#srch').submit(function(event){
		 var startsubm = $('#start').val();
		 var endsubm = $('#end').val();
		 var fdate = $('#f_date_fields').val();
		 var result = submitValidate(startsubm, endsubm, fdate);

		 if(!result){
		 	event.preventDefault();
		}
	});

	// change start val in filter section
	$('#start').change(function(){
		var v = $(this).val();
		$('#end').datepicker("setDate", v );
	});

	// change which date field to filter on
	$('#f_date_fields').change(function(){
		var dval =  $(this).val();
		if(dval == -99999){
			$('#start').val(null);
			$('#end').val(null);
		}
	});

	// implemeny jquery ui datepicker
	$( "#start" ).datepicker({
		showOn: "both",
		buttonImage: "calendar.gif",
		buttonImageOnly: true,
		buttonText: "Select date",
		defaultDate: start,
		changeMonth: true,
	    changeYear: true
	});

	$( "#end" ).datepicker({
		showOn: "both",
		buttonImage: "calendar.gif",
		buttonImageOnly: true,
		buttonText: "Select date",
		defaultDate: end,
		changeMonth: true,
	    changeYear: true
	});
			
	// implement datatables.net jquery plugin		
    $('#example').DataTable( {
        "dom":'C<"clear">lfrtip',
        "aoColumnDefs": [ {"bVisible": false, "aTargets": [1,2,3,4,14,24,25,26,27,28,29,37,39,41, 43] }],
        "iDisplayLength": 20,
        "lengthMenu": [ 10, 20, 40, 80, 100 ],
        "colVis": {
            "label": function ( index, title, th ) {
                return (index+1) +'. '+ title;
            },
            "align": "right",
            "showAll": "Show all",
            "showNone": "Show none",
            "restore": "Revert to original visibility" }
    });
});