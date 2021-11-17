#!/bin/bash
./migrate-to-preview.sh utqs-chasp utexas-chasp https://chasp.lbj.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-csrd utexas-csrd https://csrd.lbj.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-child-family-research utexas-child-family-research https://childandfamilyresearch.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-impact-factory utexas-impact-factory https://theimpactfactory.org >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-urban-lab utexas-urban-lab https://urbanlab.lbj.utexas.edu >> migration-report.txt 2>&1 
