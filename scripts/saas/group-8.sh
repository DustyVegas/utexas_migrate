#!/bin/bash
./migrate-to-preview.sh utqs-health-ipe utexas-health-ipe https://healthipe.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-msims utexas-msisp https://msisp.ischool.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-texcep utexas-texcep https://texcep.education.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-mesoamerica utexas-mesoamerica https://utmesoamerica.org >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-offcampus utexas-offcampus https://offcampus.utexas.edu >> migration-report.txt 2>&1
