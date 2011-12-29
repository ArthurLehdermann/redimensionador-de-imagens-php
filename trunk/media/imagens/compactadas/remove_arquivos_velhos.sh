#!/bin/bash
find -name "*.zip" -mtime +1 -exec rm -Rf {} \;
