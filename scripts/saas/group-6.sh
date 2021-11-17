#!/bin/bash
./migrate-to-preview.sh utqs-purchasing utexas-purchasing https://purchasing.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-gradschool utexas-gradschool-utdk2 https://gradschool.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-dobie-paisano utexas-dobie-paisano https://dobiepaisano.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-zoom utexas-zoom https://zoom.its.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-it-governance utexas-itlc https://itlc.utexas.edu >> migration-report.txt 2>&1 
