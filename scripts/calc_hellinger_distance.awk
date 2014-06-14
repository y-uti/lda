#!/usr/bin/awk -f

(NR == 1) {
  for (i = 1; i <= NF; i++) base[i - 1] = $i;
}
(NR > 1) {
  distance = 0;
  for (i = 1; i <= NF; i++) distance += (sqrt($i) - sqrt(base[i - 1]))^2;
  print distance;
}
