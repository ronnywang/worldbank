<?php

// map_unit.json is from  http://www.naturalearthdata.com
// http://www.naturalearthdata.com/http//www.naturalearthdata.com/download/10m/cultural/ne_10m_admin_0_map_units.zip
$json = json_decode(file_get_contents('map_unit.json'));
$fp = fopen(__DIR__ . '/health/sp.dyn.le00.in_Indicator_en_csv_v2.csv', 'r');
$columns = fgetcsv($fp);
$bank_codes = array();
while ($row = fgetcsv($fp)) {
    list($country_name, $code) = $row;
    $bank_codes[$code] = $country_name;
}

$showed = array();

$merge_feature = function($a, $b){
    $ret = new StdClass;
    $ret->type = 'MultiPolygon';
    $ret->coordinates = array();

    foreach (array($a, $b) as $feature) {
        if ($feature->type == 'Polygon') {
            $ret->coordinates[] = $feature->coordinates;
        } else {
            $ret->coordinates = array_merge($ret->coordinates, $feature->coordinates);
        }
    }
    return $ret;
};

foreach ($json->features as $serial => $feature) {
    $id = $feature->properties->WB_A3;
    if ($id == 'ROM') {
        $id = 'ROU'; // Romania
    }
    if ($id == 'ZAR') {
        $id = 'COD'; // Democratic Republic of the Congo
    }
    if ($id == 'ADO') {
        $id = 'AND'; // Andorra
    }
    if ($id == 'IMY') {
        $id = 'IMN'; // Isle of Man
    }
    if ($id == 'TMP') {
        $id = 'TLS'; // Timor-Leste
    }
    if ($id == -99) {
        $id = $feature->properties->ADM0_A3;
    }

    if ($id == 'PSX') {
        $id = 'PSE'; // West Bank and Gaza
    }

    if ($showed[$id]) {
        if (array_key_exists($showed[$id], $json->features)) {
            $json->features[$showed[$id]]->geometry = $merge_feature($json->features[$showed[$id]]->geometry, $feature->geometry);
        }
        error_log("{$id} is showed");
        unset($json->features[$serial]);
        continue;
    }
    $showed[$id] = $serial;
    if ($id == 'TWN' or array_key_exists($id, $bank_codes)) {
        $json->features[$serial]->properties = array(
            'id' => $id,
            'name' => $bank_codes[$id],
        );
        unset($bank_codes[$id]);
        continue;
    }

    error_log("Map yes, Bank no: ({$id}) {$json->features[$serial]->properties->NAME_LONG}");
    $json->features[$serial]->properties = array(
        'id' => $id,
        'name' => $json->features[$serial]->properties->NAME_LONG,
    );
}
$json->features = array_values($json->features);

foreach ($bank_codes as $id => $code) {
    error_log("Bank yes, Map no: ({$id}){$code}");
}

file_put_contents('worldbank.json', str_replace('{"type":"Feature",', "\n" . '{"type":"Feature",', json_encode($json)));
