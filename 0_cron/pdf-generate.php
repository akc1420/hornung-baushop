#!/usr/bin/php
<?php

/*
 * Dieses Script sucht nach Rechnungen, Stornos und Gutschriften
 * für die noch kein PDF Dokument erzeugt wurde und generiert dieses.
 * Der Aufruf des Scripts erfolgt über die crontab 1 x mal am Tag.
 * */

//
// Config
//
$baseUrl = 'https://hornung-baushop.de';
$username = 'api';
$password = 'P1u9%6e7sBit';

$host = 'localhost';
$dbname = 'shopware';
$usernameDB = 'shopware';
$passwordDB = '94fn3pwfn8394pfn293gfn29pfnsod';

// $documentID = '2F9E8DCD93F04D7F8B655771ED935105';

// get Access Token
$accessToken = getAccessToken($baseUrl, $username, $password);
// echo "Token: ".$accessToken;

$mysqli = new mysqli($host, $usernameDB, $passwordDB, $dbname);
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}

//
// Rechnungen, Gutschriften, Stornos
//
if(isset($documentID) && $documentID != "") {
    $sql = "SELECT HEX(id) AS id, deep_link_code FROM document WHERE id = UNHEX('".$documentID."') AND 
    (document_type_id = UNHEX('932DF20C306A46B7943642305CA8A2FE') 
    OR 
    document_type_id = UNHEX('DCA7264B4F104B798A4EAB65CE161A01')
    OR 
    document_type_id = UNHEX('6F242E6813904A4EBFA10111B3E894E6')) 
    AND document_media_file_id IS NULL LIMIT 1";
}
else {
    $sql = "SELECT HEX(id) AS id, deep_link_code FROM document WHERE 
    (document_type_id = UNHEX('932DF20C306A46B7943642305CA8A2FE') 
    OR 
    document_type_id = UNHEX('DCA7264B4F104B798A4EAB65CE161A01')
    OR 
    document_type_id = UNHEX('6F242E6813904A4EBFA10111B3E894E6'))
    AND document_media_file_id IS NULL LIMIT 100";
}
// die($sql);
$result = $mysqli->query($sql);
$count = $result->num_rows;

if ($count > 0) {
    while ($row = $result->fetch_assoc()) {

        $url = "https://hornung-baushop.de/api/_action/document/".strtolower($row['id'])."/".$row['deep_link_code']."";
        // die($url);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer '.$accessToken.''
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        // Handle the API response here
        // ...
        // print_r($response);

        // die('Test URL: '.$url);
    }
    echo $count." Einträge geändert";
} else {
    echo "No results found.";
}

$mysqli->close();


//
// get Bearer Token
//
function getAccessToken($baseUrl, $username, $password)
{
    $url = $baseUrl . '/api/oauth/token';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'password',
        'scopes'    => 'read',
        'client_id' => 'administration',
        'username' => $username,
        'password' => $password,
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (isset($data['access_token'])) {
        return $data['access_token'];
    } else {
        throw new Exception('Failed to get access token: ' . $response);
    }
}
