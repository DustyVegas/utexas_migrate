#!/bin/bash
./migrate-to-preview.sh utqs-lbj-wcs utexas-lbj-wcs https://lbjwcs.lbj.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-commcouncil utexas-commcouncil https://commcouncil.utexas.org >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-trademarks utexas-trademarks https://trademarks.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-internal-audits utexas-internal-audits https://audit.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-ombuds utexas-ombuds https://ombuds.utexas.edu >> migration-report.txt 2>&1 

