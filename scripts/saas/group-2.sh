#!/bin/bash
./migrate-to-preview.sh utqs-legal-affairs utexas-legal https://legal.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-staff-council utexas-staff-council https://staffcouncil.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-texas-memorial-museum utexas-texas-memorial-museum https://tmm.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-texas-metro-observatory utexas-texas-metro-observatory https://tmo.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-ttap utexas-ttap https://ttap.disabilitystudies.utexas.edu >> migration-report.txt 2>&1 
