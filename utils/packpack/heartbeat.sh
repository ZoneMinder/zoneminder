#!/bin/bash
# A script to provide background noise so Travis doesn't kill us due to inactivity
# written by Andrew Bauer

while true; do
    echo "$(date) - Please don't kill us Mr. Travis, we are still running!"
    sleep 30s
done

