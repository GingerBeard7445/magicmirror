// Call functions
setDate();
setTime();
setGreeting();

// Recall conditions after page load
var getDate = setInterval(function(){ setDate() }, 1000);
var getTime = setInterval(function(){ setTime() }, 1000);
var getGreeting = setInterval(function(){ setGreeting() }, 1000);

function setDate() {
	"use strict";

	// Variables
	var d = new Date();
	var weekday = null, month = null, day = null, monthDay = null;
	var today = null, tomorrow = null, tomorrowTwo = null, tomorrowThree = null;
	var tomorrowFour = null, tomorrowFive = null, tomorrowSix = null;

	const monthNames = ["January", "February", "March", "April", "May", "June", "July", 
						"August", "September", "October", "November", "December"];
	const weekdayNamesLong = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
	const weekdayNamesShort = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

	// Sets current month, weekday, and date
	weekday = weekdayNamesLong[d.getDay()];
	month = monthNames[d.getMonth()];
	day = d.getDate();
	day = humanize(day);

	monthDay = month + " " + day;

	today = weekday + ", " + monthDay;

	document.getElementById('date').innerHTML = today;

	// Sets day weekday names for 3 day forecast
	document.getElementById('weekdayOne').innerHTML = "Today";

	tomorrow = d.getDay() + 1;
	tomorrow = weekdayNumber(tomorrow);
	tomorrow = weekdayNamesShort[tomorrow] + '.';
	document.getElementById('weekdayTwo').innerHTML = tomorrow;

	tomorrowTwo = d.getDay() + 2;
	tomorrowTwo = weekdayNumber(tomorrowTwo);
	tomorrowTwo = weekdayNamesShort[tomorrowTwo] + '.';
	document.getElementById('weekdayThree').innerHTML = tomorrowTwo;

	tomorrowThree = d.getDay() + 3;
	tomorrowThree = weekdayNumber(tomorrowThree);
	tomorrowThree = weekdayNamesShort[tomorrowThree] + '.';
	document.getElementById('weekdayFour').innerHTML = tomorrowThree;

	tomorrowFour = d.getDay() + 4;
	tomorrowFour = weekdayNumber(tomorrowFour);
	tomorrowFour = weekdayNamesShort[tomorrowFour] + '.';
	document.getElementById('weekdayFive').innerHTML = tomorrowFour;

	tomorrowFive = d.getDay() + 5;
	tomorrowFive = weekdayNumber(tomorrowFive);
	tomorrowFive = weekdayNamesShort[tomorrowFive] + '.';
	document.getElementById('weekdaySix').innerHTML = tomorrowFive;

	tomorrowSix = d.getDay() + 6;
	tomorrowSix = weekdayNumber(tomorrowSix);
	tomorrowSix = weekdayNamesShort[tomorrowSix] + '.';
	document.getElementById('weekdaySeven').innerHTML = tomorrowSix;
}

// Sets time
function setTime() {
	"use strict";

	// Variables
	var d = new Date();
	var time = null;
	var hours = d.getHours();
	var minutes = d.getMinutes();
	var meridiem = null;

	// Makes minutes less than zero double digits for looks
	if (minutes < 10) {
		minutes = "0" + minutes;
	}

	// Sets meridiam (am/pm)
	if (hours < 12) {
		meridiem = "am";
	} else {
		meridiem = "pm";
	}

	// Converts hours to 12 hour clock
	if (hours >= 12) {
		hours = hours - 12;
	}
	if (hours == 0) {
	    hours = 12;
	}

	time = hours + ":" + minutes + meridiem;

	// Sets time in html
	document.getElementById('time').innerHTML = time;
}

// Sets greeting
function setGreeting() {
	"use strict";
	
	// Variables
	var d = new Date();
	var hours = d.getHours();
	var greeting = d.getHours();

	if (hours < 12) {
		greeting = "Good Morning";
	} else if (hours < 17) {
		greeting = "Good Afternoon";
	} else {
		greeting = "Good Evening";
	}

	// Sets greeting in html
	document.getElementById('greeting').innerHTML = greeting;
}

// Adds "th", "nd", "st" to number
function humanize(number) {
    if(number % 100 >= 11 && number % 100 <= 13) {
        return number + "th";
    }
    
    switch(number % 10) {
        case 1: return number + "st";
        case 2: return number + "nd";
        case 3: return number + "rd";
    }
    
    return number + "th";
}

// Makes sure the week doesn't exceed 7 days
function weekdayNumber(n) {
	var number;

	if (n >= 7) {
		number = n - 7;
	} else {
		number = n
	};

	return number;
}
