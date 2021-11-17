#!/bin/bash
./migrate-to-preview.sh utqs-compliance utexas-compliance https://compliance.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-title-ix utexas-title-ix https://titleix.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-student-affairs utexas-student-affairs https://studentaffairs.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-waggoner-center utexas-waggoner https://waggonercenter.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-covid-resource-guide utexas-whole-health https://covid.wholecommunities.utexas.edu >> migration-report.txt 2>&1
