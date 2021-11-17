#!/bin/bash
./migrate-to-preview.sh utqs-baitlc utexas-baitlc https://baitlc.utexas.edu >> migration-report.txt 2>&1 
./migrate-to-preview.sh utqs-mrsec utexas-mrsec https://mrsec.utexas.edu >> migration-report.txt 2>&1
