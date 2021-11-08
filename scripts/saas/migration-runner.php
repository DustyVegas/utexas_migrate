<?php

$sites = [];

$sites[] = ["utexas-offcampus"];
$sites[] = ["utexas-parents-assoc"];
$sites[] = ["utexas-faculty-council"];
$sites[] = ["utexas-budget"];
$sites[] = ["utexas-business-contracts"];
$sites[] = ["utexas-payroll"];
$sites[] = ["utexas-purchasing"];
$sites[] = ["utexas-gradschool-utdk2"];
$sites[] = ["utexas-dobie-paisano"];
$sites[] = ["utexas-zoom"];
$sites[] = ["utexas-itlc"];
$sites[] = ["utexas-office-365"];
$sites[] = ["utexas-datacenters"];
$sites[] = ["utexas-webpublishing"];
$sites[] = ["utexas-kbh"];
$sites[] = ["utexas-lbj-50"];
$sites[] = ["utexas-chasp"];
$sites[] = ["utexas-csrd"];
$sites[] = ["utexas-child-family-research"];
$sites[] = ["utexas-impact-factory"];
$sites[] = ["utexas-urban-lab"];
$sites[] = ["utexas-lbj-wcs"];
$sites[] = ["utexas-commcouncil"];
$sites[] = ["utexas-trademarks"];
$sites[] = ["utexas-internal-audits"];
$sites[] = ["utexas-ombuds"];
$sites[] = ["utexas-legal"];
$sites[] = ["utexas-staff-council"];
$sites[] = ["utexas-texas-memorial-museum"];
$sites[] = ["utexas-texas-metro-observatory"];
$sites[] = ["utexas-ttap"];
$sites[] = ["utexas-compliance"];
$sites[] = ["utexas-title-ix"];
$sites[] = ["utexas-student-affairs"];
$sites[] = ["utexas-waggoner"];
$sites[] = ["utexas-whole-health"];
$sites[] = ["utexas-texcep"];
$sites[] = ["utexas-mesoamerica"];

$sites = [];
$sites[] = ['utqs-zoom', 'utexas-zoom', 'https://zoom.its.utexas.edu'];

foreach ($sites as $site) {
  echo "Attempting migration from $site[0] to $site[1] as $site[2]... " . PHP_EOL;
  $source = escapeshellarg($site[0]);
  $destination = escapeshellarg($site[1]);
  $domain = escapeshellarg($site[2]);
  $output = exec("./migrate-to-preview.sh $source $destination $domain");
  echo $output . PHP_EOL;
}
