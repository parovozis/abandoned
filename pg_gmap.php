    <script src="https://maps.google.com/maps?file=api&amp;v=2&amp;key=<? echo GMAPS_KEY ?>"
      type="text/javascript"></script>
    <script type="text/javascript">

    //<![CDATA[

    function load() {
       if (!GBrowserIsCompatible()) {
	 alert ("Sorry, your browser cannot handle the true power of Google Maps");
	 return;
       }
       var opts = {mapTypes : [G_SATELLITE_MAP,G_NORMAL_MAP]};
       var map = new GMap2(document.getElementById("map"), opts);
       map.addControl (new GLargeMapControl ());
       map.addControl (new GMapTypeControl ());
       map.addControl (new GOverviewMapControl (new GSize(100,100)));
       var point = new GLatLng(<? echo $lat ?>, <? echo $long ?>);
       map.setCenter(point, <? echo GOOGLE_SPAN ?>);
       options = {draggable : false, bouncy : false};
       <? if ($dominus) {
       echo 'options["draggable"] = true;';
       } ?>
       var marker = new GMarker(point, options);
       <? if ($dominus) {
       echo 'map.openInfoWindowHtml (point, "<hr><b>Этот маркер можно двигать</b><br>с целью редактирования координат фотографии.<hr>");';
       } ?>
       map.addOverlay(marker);
       GEvent.addListener (marker, "dragend", function() {
	   GDownloadUrl("move.php?ID=<? echo $ID ?>&LAT="+marker.getPoint().lat()+"&LNG="+marker.getPoint().lng(), function () {});
	 });	
      
       var greenIcon = new GIcon (G_DEFAULT_ICON);
       greenIcon.image = "http://www.google.com/intl/en_us/mapfiles/ms/micons/green-dot.png";
       
       var x = [<? echo $x; ?>];
       var y = [<? echo $y; ?>];
       var ids = [<? echo $ids; ?>];

       function createMarker (location, url) {
	 var marker = new GMarker (location, {draggable : false, bouncy : false, icon : greenIcon});
	 GEvent.addListener (marker, "click", function () {
	     window.location = url;
	   });
	 return marker;
       }

       for (index = 0; index < x.length; index++)
	 map.addOverlay (createMarker (new GLatLng (x[index], y[index]), 
				       "<? echo GHOST ?>?ID=" + ids[index]));	 
     }

    //]]>
    </script>

  <body onload="load()" onunload="GUnload()">
    <div id="map" style="width: 800px; height: 600px"></div>
</body>