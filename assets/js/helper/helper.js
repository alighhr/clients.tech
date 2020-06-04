var Mobiweb = Mobiweb || {};



Mobiweb.helpers = {

	isObjectEmpty : function(obj){

		for(var key in obj) {

			if(obj.hasOwnProperty(key))

				return false;

		}

		return true;

	},

	playerIcon : {"BATSMAN":'flaticon1-game','BOWLER':'flaticon1-tennis-ball','ALLROUNDER':'flaticon1-ball','WICKETKEEPER':'flaticon1-pair-of-gloves'},



	daysAbbreviation : ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],

	days : ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],

	month: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],

	monthFull: ['January','February','March','April','May','June','July','August','September','October','November','December'],

	getDayOfDate : function(dateString){

		var _that = this;

		let d = new Date(dateString);

		return _that.days[d.getDay()];

	},
	getLocalDateTime : function(date,time){
		var _that = this;
		var serverDateArray = date.split("-");
		var receivedDateTime = serverDateArray[2]+" "+_that.monthFull[parseInt(serverDateArray[1])-1]+" "+serverDateArray[0]+" "+time+" GMT";

		var d = new Date(receivedDateTime).toLocaleDateString("en-gb").split("/");
		var t = new Date(receivedDateTime).toLocaleTimeString("en-gb");
	  
		var localeDate = d[2]+"-"+d[1]+"-"+d[0];
		var localeDateTime = new Date(localeDate+" "+t);
		return localeDateTime;
	},
	
	getAbbreviationDayOfDate : function(dateString){

		var _that = this;

		let d = new Date(dateString);

		return _that.daysAbbreviation[d.getDay()];

	},

	getDesiredFormOfDate : function(dateString){

		var _that = this;

		let d = new Date(dateString);

		return _that.month[d.getMonth()]+" "+d.getDate();	

	},
	getDesiredFormOfDate2 : function(dateString){
		console.log(dateString)
		var _that = this;
		let d = new Date(dateString);
		console.log(d.getDate()+"-"+_that.month[d.getMonth()],'111')
		return d.getDate()+"-"+_that.month[d.getMonth()];	
	},

	getTwelveHoursTime : function(time){

		// Check correct time format and split into components

		time = time.toString ().match (/^([01]\d|2[0-3])(:)([0-5]\d)(:[0-5]\d)?$/) || [time];

		if (time.length > 1) { // If time format correct

			time = time.slice (1);  // Remove full string match value

			time[5] = +time[0] < 12 ? 'AM' : 'PM'; // Set AM/PM

			time[0] = +time[0] % 12 || 12; // Adjust hours

		}

		return time.join (''); // return adjusted time or original string



	},

	getSalary : function($amount){

		$salary = 1000;

		if($amount==0)

		{

			return $salary	

		}

		else

		{

			return $amount*$salary;

		}

		

	},

	getTimeGap : function(contest_time){

		var now = new Date().getTime();



		var countDownDate = new Date(contest_time).getTime();

		if(countDownDate<now)

		{

			return -1;

		}

		var distance = countDownDate - now;

		var days = Math.floor(distance / (1000*60*60*24));

		var hours = Math.floor((distance % (1000*60*60*24)) / (1000*60*60))+(days*24);

		var minutes = Math.floor((distance % (1000*60*60)) / (1000 * 60));

		var seconds = Math.floor((distance % (1000 * 60)) / 1000);

		if(parseInt(hours)>99)
		{
			return 0;
		}

		var $str = '';



		// calculate (and subtract) whole days

		$str += ('0'+hours.toString()).slice(-2)+':';

		$str += ('0'+minutes.toString()).slice(-2)+':';

		$str += ('0'+seconds.toString()).slice(-2);



		return $str;



	},

	getTimeGapFormat2 : function(contest_time){

		var now = new Date().getTime();

		

		var countDownDate = new Date(contest_time).getTime();

		if(countDownDate<now)

		{

			return false;

		}

		var distance = countDownDate - now;

		var days = Math.floor(distance / (1000*60*60*24));

		var hours = Math.floor((distance % (1000*60*60*24)) / (1000*60*60))+(days*24);

		var minutes = Math.floor((distance % (1000*60*60)) / (1000 * 60));

		var seconds = Math.floor((distance % (1000 * 60)) / 1000);

		

		var $str = '';

		// calculate (and subtract) whole days

		$str += '<span>'+('0'+days.toString()).slice(-2)+' <strong>DAY</strong></span> ';
		$str += '<span>'+('0'+hours.toString()).slice(-2)+' <strong>HRS</strong></span> ';
		$str += '<span>'+('0'+minutes.toString()).slice(-2)+' <strong>MIN</strong></span> ';
		$str += '<span>'+('0'+seconds.toString()).slice(-2)+' <strong>SEC</strong></span> ';
		
		return $str;

	}

	
};

