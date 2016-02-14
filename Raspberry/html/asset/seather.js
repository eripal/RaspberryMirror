var baseURL = "http://lab.syaningv.com/seather/";

if (!window.Seather)
	window.Seather = {};

Seather.forecast = function(param) {
	if (!param.cityID)
		return;
	$.ajax({
		type : 'GET',
		url : baseURL + 'forecast.jsp',
		data : {
			cityID : param.cityID
		},
		dataType : 'jsonp',
		jsonp : 'callback',
		jsonpCallback : 'forecastHandler',
		success : function(data) {
			showForecast(data, param);
		}
	});
};

function showForecast(data, param) {
	if (!param.color)
		param.color = 'white';
	if (!param.size)
		param.size = 'small';
	if (!param.days)
		param.days = 5;
	var $seather = $(param.position);
	var $city = $('<div class="city"></div>').text(data.city);
	$seather.append($city);
	var $forecasts = $('<ul class="forecast"></ul>');

	param.days = (param.days <= 0 || param.days > 5) ? 5 : param.days;
	for ( var i = 0; i < param.days; i++) {
		var item = data.forecasts[i];
		var $img = $('<img />').attr('src',
				baseURL + 'img/' + item.code + '.png').attr('alt', item.text);
		var $weather = $('<div class="weather"></div>').append($img);

		var $temp = $('<div class="temp"></div>').text(
				parseInt(item.low) + ' ~ ' + parseInt(item.high) + '℃');
		var $date = $('<div class="date"></div>').text(item.date);

		var $li = $('<li></li>').append($weather).append($temp).append($date);
		$forecasts.append($li);
	}
	$seather.append($forecasts);
	$seather.addClass('seather').addClass('seather-'+param.color).addClass('seather-'+param.size).addClass('seather-'+param.size+'-'+param.days);
}

$(function() {
	var $style = $('<link rel="stylesheet" type="text/css" href="' + baseURL
			+ 'asset/seather.css" />');
	$('head').append($style);
});
