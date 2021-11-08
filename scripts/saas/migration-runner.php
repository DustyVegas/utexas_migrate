<?php

$sites = [];

$sites[] = ["utqs-baitlc", "utexas-baitlc", "https://baitlc.utexas.edu"];
$sites[] = ["utqs-mrsec", "utexas-mrsec", "https://mrsec.utexas.edu"];
$sites[] = ["utqs-health-ipe", "utexas-health-ipe", "https://healthipe.utexas.edu"];
$sites[] = ["utqs-msims", "utexas-msisp", "https://msisp.ischool.utexas.edu"];
$sites[] = ["utqs-texcep", "utexas-texcep", "https://texcep.education.utexas.edu"];
$sites[] = ["utqs-mesoamerica", "utexas-mesoamerica", "https://utmesoamerica.org"];
$sites[] = ["utqs-offcampus", "utexas-offcampus", "https://offcampus.utexas.edu"];
$sites[] = ["utqs-parents-association", "utexas-parents-assoc", "https://parents.utexas.edu"];
$sites[] = ["utqs-faculty-council", "utexas-faculty-council", "https://facultycouncil.utexas.edu"];
$sites[] = ["utqs-budget", "utexas-budget", "https://budget.utexas.edu"];
$sites[] = ["utqs-business-contracts", "utexas-business-contracts", "https://businesscontracts.utexas.edu"];
$sites[] = ["utqs-payroll", "utexas-payroll", "https://payroll.utexas.edu"];
$sites[] = ["utqs-purchasing", "utexas-purchasing", "https://purchasing.utexas.edu"];
$sites[] = ["utqs-gradschool", "utexas-gradschool-utdk2", "https://gradschool.utexas.edu"];
$sites[] = ["utqs-dobie-paisano", "utexas-dobie-paisano", "https://dobiepaisano.utexas.edu"];
$sites[] = ["utqs-zoom", "utexas-zoom", "https://zoom.its.utexas.edu"];
$sites[] = ["utqs-it-governance", "utexas-itlc", "https://itlc.utexas.edu"];
$sites[] = ["utqs-office-365", "utexas-office-365", "https://office365.utexas.edu"];
$sites[] = ["utqs-datacenters", "utexas-datacenters", "https://datacenters.utexas.edu"];
$sites[] = ["utqs-webpublishing", "utexas-webpublishing", "https://webpublishing.utexas.edu"];
$sites[] = ["utqs-kbh", "utexas-kbh", "https://kbhenergycenter.utexas.edu"];
$sites[] = ["utqs-lbj-50", "utexas-lbj-50", "https://lbj50.org"];
$sites[] = ["utqs-chasp", "utexas-chasp", "https://chasp.lbj.utexas.edu"];
$sites[] = ["utqs-csrd", "utexas-csrd", "https://csrd.lbj.utexas.edu"];
$sites[] = ["utqs-child-family-research", "utexas-child-family-research", "https://childandfamilyresearch.utexas.edu"];
$sites[] = ["utqs-impact-factory", "utexas-impact-factory", "https://theimpactfactory.org"];
$sites[] = ["utqs-urban-lab", "utexas-urban-lab", "https://urbanlab.lbj.utexas.edu"];
$sites[] = ["utqs-lbj-wcs", "utexas-lbj-wcs", "https://lbjwcs.lbj.utexas.edu"];
$sites[] = ["utqs-commcouncil", "utexas-commcouncil", "https://commcouncil.utexas.org"];
$sites[] = ["utqs-trademarks", "utexas-trademarks", "https://trademarks.utexas.edu"];
$sites[] = ["utqs-internal-audits", "utexas-internal-audits", "https://audit.utexas.edu"];
$sites[] = ["utqs-ombuds", "utexas-ombuds", "https://ombuds.utexas.edu"];
$sites[] = ["utqs-legal-affairs", "utexas-legal", "https://legal.utexas.edu"];
$sites[] = ["utqs-staff-council", "utexas-staff-council", "https://staffcouncil.utexas.edu"];
$sites[] = ["utqs-texas-memorial-museum", "utexas-texas-memorial-museum", "https://tmm.utexas.edu"];
$sites[] = ["utqs-texas-metro-observatory", "utexas-texas-metro-observatory", "https://tmo.utexas.edu"];
$sites[] = ["utqs-ttap", "utexas-ttap", "https://ttap.disabilitystudies.utexas.edu"];
$sites[] = ["utqs-compliance", "utexas-compliance", "https://compliance.utexas.edu"];
$sites[] = ["utqs-title-ix", "utexas-title-ix", "https://titleix.utexas.edu"];
$sites[] = ["utqs-student-affairs", "utexas-student-affairs", "https://studentaffairs.utexas.edu"];
$sites[] = ["utqs-waggoner-center", "utexas-waggoner", "https://waggonercenter.utexas.edu"];
$sites[] = ["utqs-covid-resource-guide", "utexas-whole-health", "https://covid.wholecommunities.utexas.edu"];

// $sites = [];
// $sites[] = ['utqs-zoom', 'utexas-zoom', 'https://zoom.its.utexas.edu'];

foreach ($sites as $site) {
  echo "Attempting migration from $site[0] to $site[1] as $site[2]... " . PHP_EOL;
  $source = escapeshellarg($site[0]);
  $destination = escapeshellarg($site[1]);
  $domain = escapeshellarg($site[2]);
  $output = exec("./migrate-to-preview.sh $source $destination $domain >> migration-report.txt 2>&1 ");
  echo $output . PHP_EOL;
  file_put_contents('./migration-report.txt', $output, FILE_APPEND);
}
