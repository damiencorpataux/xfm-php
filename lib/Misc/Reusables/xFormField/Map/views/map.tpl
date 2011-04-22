<div id="<?php echo $d['dom_id'] ? $d['dom_id'] : md5(mktime().rand()) ?>" style="width:<?php echo $d['width'] ?>px;height:<?php echo $d['height']?>px">
</div>
<script>
Ext.onReady(function() {
    map_init();
});

function map_init(){
    olmap = new OpenLayers.Map('map');
    // Base layers
    var google_hybrid = new OpenLayers.Layer.Google("Satellite", {
        type: G_HYBRID_MAP,
        numZoomLevels: 22
    });
    var google_street = new OpenLayers.Layer.Google("Streets", {
        type: G_NORMAL_MAP,
        numZoomLevels: 22
    });
    olmap.addLayers([google_street, google_hybrid]);
    // Itinerary layer
    itinerary_layer = new OpenLayers.Layer.Vector("Itinerary", {
        displayInLayerSwitcher: false
    });
    olmap.addLayer(itinerary_layer);
    // Maker layer
    markers_layer = new OpenLayers.Layer.Markers("Markers", {
        displayInLayerSwitcher: false
    });
    olmap.addLayer(markers_layer);
    // Map controls
    olmap.addControl(new OpenLayers.Control.LayerSwitcher());
    olmap.addControl(new OpenLayers.Control.MousePosition());
    // Map center
    olmap.setCenter(new OpenLayers.LonLat(8.305664, 46.71726), 7);
}
</script>
