#!/bin/bash
cat finances.txt | column -t -s $'\t' | less
