<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_WARNING);

header("Content-Type: text/plain");


echo "Current date: ".date("Y-m-d").PHP_EOL.PHP_EOL;
$nextload=strtotime(json_decode(file_get_contents("mds.json"))->nextUpdate);
echo "local json next update: ";
var_dump(date("Y-m-d",$nextload));

if(time()<$nextload) {
  echo "no refresh needed";
  die();
}
else {
  echo "parsed data out of date, refreshing...".PHP_EOL.PHP_EOL;
}
$jwt=file_get_contents("mds.jwt");
$mds=parse_jwt($jwt);
echo "local jwt next update: ";
var_dump($mds->nextUpdate);
if(time()>strtotime($mds->nextUpdate)) {
  echo "JWT out of date, downloading...".PHP_EOL.PHP_EOL;
  $jwt=file_get_contents("https://mds.fidoalliance.org/");
  $mds=parse_jwt($jwt,"mds.jwt");
}
else {
  echo "Local JWT Up to Date. No need for download.".PHP_EOL.PHP_EOL;
}


echo "MDS List Number: ".$mds->no.PHP_EOL;

$total=count($mds->entries);
echo "$total entries found.".PHP_EOL;

$f2list=new StdClass();
$f2list->no=$mds->no;
$f2list->type="fido2";
$f2list->entries=new StdClass();

$u2flist=new StdClass();
$u2flist->no=$mds->no;
$u2flist->type="u2f";
$u2flist->entries=new StdClass();


//die();


for($i=0;$i<$total;$i++) {
  //create index by AAGUID
  if($mds->entries[$i]->metadataStatement->protocolFamily === "fido2") {
    $f2list->entries->{$mds->entries[$i]->metadataStatement->aaguid} = new StdClass();
    $f2list->entries->{$mds->entries[$i]->metadataStatement->aaguid}->name = $mds->entries[$i]->metadataStatement->description;
    $f2list->entries->{$mds->entries[$i]->metadataStatement->aaguid}->entry = $i;
  }
  //create index by Attestation Certificate Key Identifier
  elseif($mds->entries[$i]->metadataStatement->protocolFamily === "u2f") {
      foreach($mds->entries[$i]->attestationCertificateKeyIdentifiers as $certkeyid) {
        $u2flist->entries->$certkeyid = new StdClass();
        //var_dump($u2flist);
        $u2flist->entries->$certkeyid->name= $mds->entries[$i]->metadataStatement->description;
        $u2flist->entries->$certkeyid->entry = $i;
      }
  }
}

//create blank index entry for Empty AAGUID
$f2list->entries->{"00000000-0000-0000-0000-000000000000"} = new StdClass();
$f2list->entries->{"00000000-0000-0000-0000-000000000000"}->name="unspecified or U2F";
$f2list->entries->{"00000000-0000-0000-0000-000000000000"}->entry=null;

$f2cnt=count((array)$f2list->entries);
$u2fcnt=count((array)$u2flist->entries);

var_dump($u2flist);

echo "Parsing complete. Found $u2fcnt U2F Entries and $f2cnt FIDO2 Entries.".PHP_EOL;

//*
file_put_contents("mds.json",json_encode($mds));
file_put_contents("mds_fido2.json",json_encode($f2list));
file_put_contents("mds_u2f.json",json_encode($u2flist));
//*/


//var_dump($f2list);

function base64url_encode($data, $pad = null) {
    $data = str_replace(array('+', '/'), array('-', '_'), base64_encode($data));
    if (!$pad) {
        $data = rtrim($data, '=');
    }
    return $data;
}
function base64url_decode($data) {
    return base64_decode(str_replace(array('-', '_'), array('+', '/'), $data));
}

function get_mds_root() {
  return "-----BEGIN CERTIFICATE-----
MIIDXzCCAkegAwIBAgILBAAAAAABIVhTCKIwDQYJKoZIhvcNAQELBQAwTDEgMB4G
A1UECxMXR2xvYmFsU2lnbiBSb290IENBIC0gUjMxEzARBgNVBAoTCkdsb2JhbFNp
Z24xEzARBgNVBAMTCkdsb2JhbFNpZ24wHhcNMDkwMzE4MTAwMDAwWhcNMjkwMzE4
MTAwMDAwWjBMMSAwHgYDVQQLExdHbG9iYWxTaWduIFJvb3QgQ0EgLSBSMzETMBEG
A1UEChMKR2xvYmFsU2lnbjETMBEGA1UEAxMKR2xvYmFsU2lnbjCCASIwDQYJKoZI
hvcNAQEBBQADggEPADCCAQoCggEBAMwldpB5BngiFvXAg7aEyiie/QV2EcWtiHL8
RgJDx7KKnQRfJMsuS+FggkbhUqsMgUdwbN1k0ev1LKMPgj0MK66X17YUhhB5uzsT
gHeMCOFJ0mpiLx9e+pZo34knlTifBtc+ycsmWQ1z3rDI6SYOgxXG71uL0gRgykmm
KPZpO/bLyCiR5Z2KYVc3rHQU3HTgOu5yLy6c+9C7v/U9AOEGM+iCK65TpjoWc4zd
QQ4gOsC0p6Hpsk+QLjJg6VfLuQSSaGjlOCZgdbKfd/+RFO+uIEn8rUAVSNECMWEZ
XriX7613t2Saer9fwRPvm2L7DWzgVGkWqQPabumDk3F2xmmFghcCAwEAAaNCMEAw
DgYDVR0PAQH/BAQDAgEGMA8GA1UdEwEB/wQFMAMBAf8wHQYDVR0OBBYEFI/wS3+o
LkUkrk1Q+mOai97i3Ru8MA0GCSqGSIb3DQEBCwUAA4IBAQBLQNvAUKr+yAzv95ZU
RUm7lgAJQayzE4aGKAczymvmdLm6AC2upArT9fHxD4q/c2dKg8dEe3jgr25sbwMp
jjM5RcOO5LlXbKr8EpbsU8Yt5CRsuZRj+9xTaGdWPoO4zzUhw8lo/s7awlOqzJCK
6fBdRoyV3XpYKBovHd7NADdBj+1EbddTKJd+82cEHhXXipa0095MJ6RMG3NzdvQX
mcIfeg7jLQitChws/zyrVQ4PkX4268NXSb7hLi18YIvDQVETI53O9zJrlAGomecs
Mx86OyXShkDOOyyGeMlhLxS67ttVb9+E7gUJTb0o2HLO02JQZR7rkpeDMdmztcpH
WD9f
-----END CERTIFICATE-----";
}

function b64certtopem($mincert) {
 $pem = chunk_split($mincert, 64, "\n");
 $pem = "-----BEGIN CERTIFICATE-----\n".$pem."-----END CERTIFICATE-----\n";
 return $pem;
}

//verify jwt, save if needed then return decoded data
function parse_jwt($jwt,$savefile=false) {
  echo "-----".PHP_EOL;
  echo "Beginning JWT parsing routine".PHP_EOL;
  //get head data
  $mds_head=json_decode(base64url_decode(explode(".",$jwt)[0]));

  //extract signature cert
  $sig_cert=b64certtopem($mds_head->x5c[0]);
  $sig_cert_obj = openssl_x509_read( $sig_cert );
  $sig_cert_data = openssl_x509_parse( $sig_cert_obj );

  if($sig_cert_data["subject"]["CN"]!=="mds.fidoalliance.org") {
    die("invalid JWT Signing Cert");
  }
  
  echo "JWT Signing Cert Matches mds.fidoalliance.org.".PHP_EOL;

  //get algo string and signature, then verify
  switch($mds_head->alg) {
    case "RS256": $jwt_sig_algo=OPENSSL_ALGO_SHA256;break;
    case "RS384": $jwt_sig_algo=OPENSSL_ALGO_SHA384;break;
    case "RS512": $jwt_sig_algo=OPENSSL_ALGO_SHA512;break;
  }
  echo "JWT Signing Algorithm {$mds_head->alg} found.".PHP_EOL;
  $sigstring=explode(".",$jwt)[0].".".explode(".",$jwt)[1];
  $jwt_sig=base64url_decode(explode(".",$jwt)[2]);
  $jwt_sig_valid=openssl_verify($sigstring,$jwt_sig,$sig_cert_obj,$jwt_sig_algo);

  if($jwt_sig_valid!==1) {
    die("jwt signature fail!");
  }
  echo "JWT signature matches Leaf Cert in JWT head.".PHP_EOL;
  $key_cnt=count($mds_head->x5c);
  echo "Found a total of $key_cnt keys within the JWT head.".PHP_EOL;
  for($i=0;$i<$key_cnt;$i++) {
    $sigcheck=null;
    if(isset($mds_head->x5c[$i+1])) { //if we still have higher objects, use parent
      $parent_cert_obj=openssl_x509_read(b64certtopem($mds_head->x5c[$i+1]));
      $final_check=false;
    }
    else { //else, get the root cert
      echo "Final Intermediate found. Obtaining pinned Root Certificate...".PHP_EOL;
      $parent_cert_obj=openssl_x509_read(get_mds_root());
      $root_data = openssl_x509_parse($parent_cert_obj);
      echo "Root Certificate Data:".PHP_EOL."Subject: ".print_r($root_data["subject"],true)."Fingerprints:\n  SHA-1:   ".openssl_x509_fingerprint($parent_cert_obj,"sha256")."\n  SHA-256: ".openssl_x509_fingerprint($parent_cert_obj,"sha1").PHP_EOL;
      $final_check=true;
    }
    $target_cert_obj=openssl_x509_read(b64certtopem($mds_head->x5c[$i]));
    $sigcheck=openssl_x509_verify($target_cert_obj,$parent_cert_obj);
    //var_dump($sigcheck);
    if($sigcheck !== 1) {
      die ("Cert check failed at $i");
    }
    echo ($i?"Intermediate":"Leaf")." Certificate".($i>0?" $i":"")." is corectly signed by ".($final_check?"pinned Root Certificate":"Intermediate Certificate ".($i+1)).PHP_EOL;
  }
  //if Sig okay and save requested make sure to save
  if(!empty($savefile) && is_string($savefile)) {
    echo "Saving as $savefile".PHP_EOL;
    file_put_contents($savefile,$jwt);
  }
  $data=json_decode(base64url_decode(explode(".",$jwt)[1]));
  //just return data
  echo "JWT Parsing complete.".PHP_EOL."-----".PHP_EOL.PHP_EOL;
  return $data;
}
