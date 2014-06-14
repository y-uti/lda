#!/bin/bash

BASEDIR=$(cd $(dirname $0)/.. && pwd)
SCRIPTDIR=$BASEDIR/scripts

datafile=$1
n=$2

topics=$(head -n 1 $datafile | awk '{ print NF - 1 }')

for i in $(seq 1 $topics); do
  echo == Topic $i ==
  k=$(($i + 1))
  sort -grsk$k,$k $datafile | awk -vk=$k '{ print $k, $1 }' | head -n $n
done
