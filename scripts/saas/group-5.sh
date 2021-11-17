#!/bin/bash
./migrate-to-preview.sh utqs-office-365 utexas-office-365 https://office365.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-datacenters utexas-datacenters https://datacenters.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-webpublishing utexas-webpublishing https://webpublishing.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-kbh utexas-kbh https://kbhenergycenter.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-lbj-50 utexas-lbj-50 https://lbj50.org >> migration-report.txt 2>&1
