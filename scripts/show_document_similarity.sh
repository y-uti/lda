#!/bin/bash

BASEDIR=$(cd $(dirname $0)/.. && pwd)
SCRIPTDIR=$BASEDIR/scripts

titlefile=$1
article=$2
datafile=$3

(head -n $article $datafile | tail -n 1; cat $datafile) | $SCRIPTDIR/calc_js_divergence.awk | paste - $titlefile | cat -n | LC_ALL=C sort -gsk2,2
