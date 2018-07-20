#!/bin/sh

comments=$1

#echo $comments
git add -A
git commit -m $comments
git push origin master
