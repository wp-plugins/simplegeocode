var geocoder;
geocoder = new google.maps.Geocoder();
var map;
var myOptions;
var div;
var map_id;

function geoBuildMap(response, div) {
	
	if (div) { map_id = div; }
	else { map_id = "simpleGeoMap"; }
	
	if ( null == response.geomarker || "null" == response.geomarker ) {
		var myOptions = {
			zoom: 2,
			center: new google.maps.LatLng(0, 0),
			mapTypeId: google.maps.MapTypeId.ROADMAP
		}
		map = new google.maps.Map(document.getElementById(map_id), myOptions);
	}
	
	else {

		var geoMarker = response.geomarker;
		
		var myOptions = {
			zoom: 16,
			center: new google.maps.LatLng(geoMarker[0].latitude, geoMarker[0].longitude),
			mapTypeId: google.maps.MapTypeId.ROADMAP
		}
		
		map = new google.maps.Map(document.getElementById(map_id), myOptions);

		var marker, i; 
		
		var bounds = new google.maps.LatLngBounds();
		
		for ( i = 0; i < geoMarker.length; i++) {
			var myLatlng = new google.maps.LatLng(geoMarker[i].latitude, geoMarker[i].longitude);
			var marker = new google.maps.Marker({							
				position: myLatlng, 
				map: map,
				title: geoMarker[i].title
			});
		
			var infowindow = new google.maps.InfoWindow();
			
			google.maps.event.addListener(
				marker, 'click', (
					function( marker, i ) {
						return function() {
							infowindow.open(map, marker);
							infowindow.setContent(
								'<h3><strong><a href="'+geoMarker[i].permalink+'">'+geoMarker[i].title+'</a></strong></h3>'+
								'<p>'+geoMarker[i].excerpt+'</p>'+
								'<button onclick="calcRoute('+geoMarker[i].latitude+', '+geoMarker[i].longitude+')">Get Directions</button>' 
								);				 
						}
					})					  
				(marker, i)
			);
			
			if ( geoMarker.length > 1 ) {
				bounds.extend(myLatlng);
				map.fitBounds(bounds);
			}
		}
	}
}

function geoResetMap() {
	document.getElementById("geo_post_delete").value= "true";
	document.getElementById("geo_address_field").value= "Enter Address";
	geoBuildMap( null );
}


function geoCodeAddress() {
	var address = document.getElementById("geo_address_field").value;
	geocoder.geocode( { 'address': address}, function(results, status) {
		if (status == google.maps.GeocoderStatus.OK) {
		  
		var latlng = results[0].geometry.location;
		
		map.setCenter(results[0].geometry.location);
		map.setZoom(18);
		
		var marker = new google.maps.Marker({
			map: map, 
			position: results[0].geometry.location,
			title: "Found your location"
		});
		
		document.getElementById("geo_post_coordinates").value= latlng;
		document.getElementById("geo_coordinates").innerHTML= '<p class="howto">Coordinates: '+latlng;
		
		} else {
			alert("Geocode was not successful for the following reason: " + status);
		} 
	});
}
  
function calcRoute(dir_lat, dir_lon) {
	navigator.geolocation.getCurrentPosition(
		function(pos) {								   
			var pos = new google.maps.LatLng(pos.coords.latitude,pos.coords.longitude);
			var start =  pos;
			var end = new google.maps.LatLng(dir_lat, dir_lon);
			
			var request = {
				origin: start,
				destination: end,
				travelMode: google.maps.DirectionsTravelMode.DRIVING
			};
		
			directionsService.route(request, function(response, status) {
				if (status == google.maps.DirectionsStatus.OK) {
					directionsDisplay.setDirections(response);
				}
			});
		},
		
		function (error)
		{
			switch(error.code) 
			{
				case error.TIMEOUT:
					alert ('Timeout');
					geoBuildMap(response);
					document.getElementById('directions_container').style.display = 'none';
					break;
				case error.POSITION_UNAVAILABLE:
					alert ("I'm sorry, your position is unavailable.");
					geoBuildMap(response);
					document.getElementById('directions_container').style.display = 'none';
					break;
				case error.PERMISSION_DENIED:
					alert ('Permission denied');
					geoBuildMap(response);
					document.getElementById('directions_container').style.display = 'none';
					break;
				case error.UNKNOWN_ERROR:
					alert ('Unknown error');
					geoBuildMap(response);
					document.getElementById('directions_container').style.display = 'none';
					break;
			}
		}
	);
	
	directionsDisplay = new google.maps.DirectionsRenderer();
	
	var myOptions = {
		zoom: 10,
		mapTypeId: google.maps.MapTypeId.ROADMAP,
	};
	
	var map = new google.maps.Map(document.getElementById(map_id), myOptions);
	
	directionsDisplay.setMap(map);
	directionsDisplay.setPanel(document.getElementById('directions_panel'));
	document.getElementById('directions_container').style.display = 'block';
}